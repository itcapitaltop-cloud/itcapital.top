<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\User;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\LogActionTypeEnum;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\LogAdminAction;
use App\Models\Partner;
use App\Models\PartnerLevelPercent;
use App\Models\PartnerReward;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserAuthLog;
use App\Models\UserLevelPercentOverride;
use App\MoonShine\Resources\UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\Alert;
use MoonShine\Components\FlexibleRender;
use MoonShine\Components\FormBuilder;
use MoonShine\Components\Modal;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Components\TableBuilder;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Divider;
use MoonShine\Decorations\Heading;
use MoonShine\Decorations\Tab;
use MoonShine\Decorations\Tabs;
use MoonShine\Fields\Date;
use MoonShine\Fields\Email;
use MoonShine\Fields\Enum;
use MoonShine\Fields\Field;
use MoonShine\Fields\Fields;
use MoonShine\Fields\Hidden;
use MoonShine\Fields\Number;
use MoonShine\Fields\Password;
use MoonShine\Fields\Preview;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Select;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Template;
use MoonShine\Fields\Text;
use MoonShine\Fields\Url;
use MoonShine\Http\Requests\MoonShineFormRequest;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\Pages\Crud\DetailPage;
use MoonShine\Pages\PageComponents;
use MoonShine\TypeCasts\ModelCast;
use Throwable;

class UserDetailPage extends DetailPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [

        ];
    }

    public function getBalanceAmountByUserIdAndType(int $userId, BalanceTypeEnum $balanceType): string
    {
        return app(TransactionRepositoryContract::class)->getBalanceAmountByUserIdAndType($userId, $balanceType);
    }

    public function components(): array
    {
        $resource = $this->getResource();
        $item = $resource->getItem();

        $activeTab = request('tab', 'main');
        $openPackage = request('openPackage');

        //        Log::channel('source')->debug($activeTab);
        if (! $item->relationLoaded('summary')) {
            $item->load('summary');
        }

        $actionButton = $item->banned_at
            ? ActionButton::make('Разбанить')
                ->icon('heroicons.lock-open')
                ->method(
                    'unban',
                    params: fn () => ['resourceItem' => $item->id]
                )
                ->primary()
            : ActionButton::make('Забанить')
                ->icon('heroicons.lock-closed')
                ->method(
                    'ban',
                    params: fn () => ['resourceItem' => $item->id]
                )
                ->error();

        $bonusForm = FormBuilder::make()
            ->action('/itcapitalmoonshineadminpanel/users/amount')
            ->fields([
                Alert::make(type: 'info')
                    ->content('Если указать отрицательное значение, то сумма вычтится из баланса!'),

                Block::make('Добавить бонус', [
                    Template::make('Тип баланса')
                        ->changeRender(fn () => view('admin.partials.balance-type-radios', [
                            'item' => $item,
                            'enum' => BalanceTypeEnum::cases(),
                        ])->render()),

                    Text::make('Сумма', 'amount'),

                    Hidden::make('user_id')
                        ->fill($item->id),
                ]),
            ])
            ->name('bonus-form')
            ->async(asyncEvents: [
                'table-updated-users',
                'form-reset-balance-form',
            ])
            ->submit('Отредактировать');

        $trigger = ActionButton::make('Редактировать')
            ->toggleModal('edit-balance-modal')
            ->primary()
            ->customAttributes(['class' => 'mb-6']);

        $formComponents = PageComponents::make([
            $bonusForm,
        ]);

        $balanceModal = Modal::make(
            title: 'Редактировать баланс',
            content: fn () => null,
            outer: $trigger,
            asyncUrl: null,
            components: $formComponents
        )->name('edit-balance-modal');

        $formChangePassword = FormBuilder::make()
            ->asyncMethod('changePassword')
            ->fields([
                Preview::make('Текущий пароль', formatted: fn () => $item->getAuthPassword()),
                Password::make('Новый пароль', 'new_password'),
                Hidden::make('user_id')->fill($item->id),
            ])
            ->submit('Подтвердить');

        $formComponents = PageComponents::make([
            $formChangePassword,
        ]);

        $trigger = ActionButton::make('Редактировать пароль', '#')
            ->icon('heroicons.key')
            ->toggleModal('edit-password-modal')
            ->primary();

        $passwordModal = Modal::make(
            title: 'Редактировать пароль',
            content: fn () => null,
            outer: $trigger,
            asyncUrl: null,
            components: $formComponents
        )->name('edit-password-modal');

        $createPackageForm = FormBuilder::make()
            ->asyncMethod('createPackage')
            ->customAttributes([
                'x-data' => "( () => {
                 const data = formBuilder(``, { whenFields: [], reactiveUrl: `` }, []);

                 Object.assign(data, {
                     packageType: '" . PackageTypeEnum::STANDARD->value . "',
                     onChangeFieldPackageForm(event) {
                         if (event.target.name === 'packageType') {
                             this.packageType = event.target.value;
                         }
                     }
                 });

                 return data;   // отдаём объединённый объект
            })()",
            ])
            ->fields([
                Hidden::make('user_id')->fill($item->id),

                /* Тип пакета */
                Select::make('Тип пакета', 'packageType')
                    ->options(
                        collect(PackageTypeEnum::cases())
                            ->reject(fn ($e) => $e === PackageTypeEnum::ARCHIVE)
                            ->mapWithKeys(fn ($e) => [$e->value => $e->getName()])
                            ->all()
                    )
                    ->customAttributes([
                        'x-model' => 'packageType',
                        'x-on:change' => 'onChangeFieldPackageForm($event)',
                    ])
                    ->required(),

                /* Срок работы – показываем только при Present */
                Select::make('Срок в месяцах', 'duration')
                    ->options([1 => '1', 3 => '3', 6 => '6', 12 => '12'])
                    ->customAttributes(['wire:model.defer' => 'duration'])
                    ->customWrapperAttributes([
                        'x-show' => "packageType === '" . PackageTypeEnum::PRESENT->value . "'",
                        'x-cloak' => '',
                    ]),

                Number::make('Доходность,%', 'percent')
                    ->fill(8.2)
                    ->customAttributes(
                        [
                            'wire:model.defer' => 'percent',
                            'step' => 'any',
                        ])
                    ->required(),

                Number::make('Сумма', 'amount')
                    ->customAttributes(['wire:model.defer' => 'amount'])
                    ->required(),
            ])
            ->submit('Создать');

        $packageComponents = PageComponents::make([$createPackageForm]);

        $createPackageTrigger = ActionButton::make('Создать пакет')
            ->icon('heroicons.document-plus')
            ->toggleModal('create-package-modal')
            ->success();

        $createPackageModal = Modal::make(
            title: 'Создание пакета',
            content: fn () => null,
            outer: $createPackageTrigger,
            asyncUrl: null,
            components: $packageComponents
        )->name('create-package-modal');

        $packages = ItcPackage::query()
            ->with([
                'transaction:id,uuid,amount,user_id',
                'reinvestProfits' => fn ($q) => $q
                    ->whereDoesntHave('withdraw')
                    ->select('id', 'uuid', 'package_uuid', 'amount', 'created_at', 'matured_at'),
            ])
            ->whereHas('transaction', fn ($q) => $q->where('user_id', $item->id))
            ->withSum([
                'reinvestProfits as reinvest_profits_sum_amount' => fn ($q) => $q->whereDoesntHave('withdraw'),
            ], 'amount')
            ->withSum([
                'profits as profits_sum_amount' => fn ($q) => $q   // profits — базовая связь
                    ->select(DB::raw('COALESCE(SUM(amount),0)')),
            ], 'amount')
            ->get()
            ->map(fn (ItcPackage $pkg) => [
                'uuid' => $pkg->transaction->uuid,
                'amount' => $pkg->transaction->amount,
                'type' => $pkg->type,
                'month_profit_percent' => $pkg->month_profit_percent,
                'reinvest_profits_sum_amount' => $pkg->reinvest_profits_sum_amount,
                'profits_sum_amount' => $pkg->profits_sum_amount,
                'itc_created_at' => $pkg->created_at,
                'reinvest_profits' => $pkg->reinvestProfits
                    ->map(fn ($r) => [
                        'uuid' => $r->uuid,
                        'package_uuid' => $r->package_uuid,
                        'amount' => $r->amount,
                        'matured_at' => $r->matured_at,
                        'created_at' => $r->created_at,
                    ])
                    ->toArray(),
            ])
            ->toArray();

        $reinvests = collect($packages)
            ->pluck('reinvest_profits')
            ->flatten(1)
            ->all();
        $partners = Partner::with('user')
            ->where('partner_id', $item->id)
            ->get()
            ->map(function (Partner $partner) {
                return [
                    'id' => $partner?->user_id,
                    'username' => $partner?->user?->username,
                    'email' => $partner?->user?->email,
                    'partner_id' => $partner?->partner_id,
                ];
            });

        $transactions = Transaction::query()
            ->where('user_id', $item->id)
            ->whereNot('trx_type', TrxTypeEnum::HIDDEN_DEPOSIT)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Transaction $tx) {
                return [
                    'action' => in_array($tx->trx_type, TrxTypeEnum::getDebits())
                        ? 'Увеличение баланса'
                        : 'Уменьшение баланса',
                    'type' => $tx->trx_type->getName(),
                    'operation_amount' => round((float) $tx->amount, 2),
                    'date' => $tx->created_at->format('d.m.Y'),
                ];
            })
            ->toArray();

        // Партнёрские начисления
        $partnerRewards = PartnerReward::query()
            ->whereHas('transaction', fn ($q) => $q->where('user_id', $item->id))
            ->with(['transaction', 'from'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (PartnerReward $reward) {
                $typeName = match ($reward->reward_type) {
                    PartnerRewardTypeEnum::START => 'Стартовая премия',
                    PartnerRewardTypeEnum::REGULAR => 'Регулярная премия',
                };

                return [
                    'action' => 'Увеличение баланса',
                    'type' => $typeName . ' (линия ' . $reward->line . ')',
                    'from_user' => $reward->from
                        ? $reward->from->username . ' (' . $reward->from->email . ')'
                        : 'Не указан',
                    'operation_amount' => round((float) $reward->amount, 2),
                    'date' => $reward->created_at->format('d.m.Y'),
                ];
            })
            ->toArray();

        // Объединяем и сортируем по дате
        $userLogs = collect([...$transactions, ...$partnerRewards])
            ->sortByDesc(function ($item) {
                return \Carbon\Carbon::createFromFormat('d.m.Y', $item['date']);
            })
            ->values()
            ->toArray();

        $adminLogs = LogAdminAction::query()
            ->where('target_user_id', $item->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (LogAdminAction $log) => [
                'action' => LogActionTypeEnum::from($log->action_type)->label(),
                'model' => class_basename($log->model_type),
                'old_values' => collect($log->old_values)
                    ->map(fn ($value) => $value)
                    ->implode("\n"),
                'new_values' => collect($log->new_values)
                    ->map(fn ($value) => $value)
                    ->implode("\n"),
                'operation_amount' => collect($log->old_values)
                    ->map(function ($oldValue, $key) use ($log) {
                        $newValue = $log->new_values[$key] ?? null;

                        if (is_numeric($oldValue) && is_numeric($newValue)) {
                            $diff = $newValue - $oldValue;

                            return $diff >= 0 ? "+{$diff}" : $diff;
                        }

                        return '';
                    })
                    ->filter()
                    ->implode("\n"),
                'date' => $log->created_at->format('d.m.Y H:i'),
            ])
            ->toArray();

        $referrerLink = $item->referrer ?
            Url::make(
                'Кто пригласил в систему',
                'referrer',
                fn ($value, int $rowIndex) => $item->referrer
                    ? to_page(
                        page: new UserDetailPage(),
                        resource: new UserResource(),
                        params: ['resourceItem' => $item->referrer->id]
                    )
                    : null
            )
                ->title(
                    fn ($href, Url $field) => $item->referrer?->username
                )
            : Text::make('Кто пригласил в систему', formatted: fn ($value, $row) => 'Нет реферера');

        $authLog = UserAuthLog::query()
            ->where('user_id', $item->id)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $lastAuth = $authLog->first();

        $fields = [];

        $fields[] = $lastAuth
            ? Text::make(
                'Последний вход',
                formatted: fn () => $lastAuth->created_at->format('d.m.Y H:i:s')
                    . ', IP: ' . $lastAuth->ip
                    . ', ' . $lastAuth->device
                    . ', ' . $lastAuth->browser
            )
            : Text::make('Последний вход', formatted: fn () => 'Нет данных о входах');

        foreach ($authLog as $i => $log) {
            $fields[] = Text::make(
                'Вход ' . ($i + 1),
                formatted: fn () => $log->created_at->format('d.m.Y H:i:s')
                    . ', IP: ' . $log->ip
                    . ', ' . $log->device
                    . ', ' . $log->browser
            );
        }

        $detailFields = $resource
            ->getDetailFields()
            ->except('id', 'referrer');

        $detailFields->prepend(
            Text::make('ID', 'id')->readonly()
        );

        $detailFields->splice(
            1,
            0,
            [
                Text::make('Кто пригласил в систему', 'referrer_username')
                    ->readonly(),
            ]
        );

        $detailForm = FormBuilder::make()
            ->name('user-detail-form')
            ->asyncMethod('update')
            ->fields([
                Text::make('ID', 'id')->readonly()->fill($item->id),
                Email::make('Email')->fill($item->email),
                Text::make('Имя пользователя', 'username')->fill($item->username),
                Switcher::make('Доступ к премиям с 20 линий', 'extended_lines')
                    ->fill((bool) ($item->extended_lines ?? false))
                    ->customAttributes([
                        'x-model' => 'extended_lines',
                    ]),
                Text::make('Ранг', 'rank')->fill($item->rank)->readonly(),
                Text::make('Кто пригласил в систему', 'referrer_username')->fill($item->referrer?->username),
            ])
            ->submit('Сохранить');

        $balanceFields = new Fields([
            Number::make('Инвестиции', 'investments_sum')
                ->fill($item->summary?->investments_sum ?? 0)
                ->step(0.01),

            Number::make('Партнёрский баланс', 'partner_balance')
                ->fill($item->summary?->partner_balance ?? 0)
                ->step(0.01),
            Hidden::make('user_id')->fill($item->id),
        ]);

        $makeItcPackageForm = static function (mixed $item) {
            $uuid = $item['uuid'];
            $package = ItcPackage::where('uuid', $uuid)->firstOrFail();

            $blockItems = [];

            if ($package->type !== PackageTypeEnum::ARCHIVE) {
                $blockItems[] = ActionButton::make('Закрыть пакет')
                    ->inModal(
                        title: 'Подтверждение',
                        content: function () use ($item) {
                            return Block::make([
                                Heading::make('Вы действительно хотите закрыть пакет?')->h(3),
                                Heading::make('Все средства будут выведены на основной баланс, а пакет станет архивным.')->h(6),
                                ActionButton::make('Подтвердить закрытие', fn () => route('itc-package-close', ['uuid' => $item['uuid']]))
                                    ->icon('heroicons.archive-box')
                                    ->async(method: 'POST')
                                    ->secondary(),
                            ]);
                        }
                    )
                    ->icon('heroicons.archive-box')
                    ->secondary();
            } else {
                $blockItems[] = Heading::make('В архиве')->h(5);
            }

            $blockItems[] = FormBuilder::make()
                ->action("/itcapitalmoonshineadminpanel/itc-packages/{$uuid}")
                ->method('POST')
                ->fields([
                    Date::make('Дата открытия', 'created_at')
                        ->fill($package->created_at),
                    Text::make('Процент прибыли', 'profit_percent')
                        ->fill($package->month_profit_percent),
                    Text::make('Депозит', 'amount')
                        ->fill(round((float) $item['amount'], 2)),
                    Enum::make('Тип пакета', 'type')
                        ->attach(PackageTypeEnum::class)
                        ->fill($package->type),
                ])
                ->async()
                ->submit('Подтвердить');

            return Block::make($blockItems);
        };

        $changeReferralForm = static function (mixed $partner): FormBuilder {
            return FormBuilder::make()
                ->action("/itcapitalmoonshineadminpanel/partners/{$partner['partner_id']}")
                ->method('POST')
                ->fillCast(
                    $partner,
                    ModelCast::make(Partner::class)
                )
                ->fields([
                    BelongsTo::make(
                        'Переназначить реферера',
                        'referrer',
                        formatted: fn (User $user) => "{$user->username}, {$user->email}",
                        resource: new UserResource()
                    )
                        ->searchable(),
                    Hidden::make('user_id')->fill($partner['id']),
                ])
                ->async()
                ->submit('Подтвердить');
        };

        $balanceForm = FormBuilder::make()
            ->asyncMethod('updateBalance')
            ->fields($balanceFields)
            ->name('balance-form')
            ->submit('Сохранить');

        $in = Transaction::query()
            ->where('user_id', $item->id)
            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
            ->whereNotNull('accepted_at')
            ->sum('amount');

        $out = Transaction::query()
            ->where('user_id', $item->id)
            ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
            ->whereNotNull('accepted_at')
            ->sum('amount');

        $balance = $in - $out;
        $inOutBlock = Block::make('IN / OUT', [
            TableBuilder::make()
                ->items([
                    [
                        'in' => $in,
                        'out' => $out,
                        'balance' => $balance,
                    ],
                ])
                ->fields([
                    Text::make('IN', 'in', formatted: fn ($v) => round((float) $v['in'], 2) . ' ITC'),
                    Text::make('OUT', 'out', formatted: fn ($v) => round((float) $v['out'], 2) . ' ITC'),
                    Text::make('Сальдо', 'balance', fn ($v) => round((float) $v['balance'], 2) . ' ITC'),
                ]),
        ]);

        $override = $item->levelOverride
            ? UserLevelPercentOverride::where('user_id', $item->id)->get()
            : null;
        $line = 0;

        return [
            FlexibleRender::make(
                fn () => '
                    <div class="flex flex-wrap gap-2 items-center">
                        ' . $actionButton . '
                        ' . $createPackageModal . '
                        ' . $passwordModal . '
                    </div>
                '
            ),
            Tabs::make([
                Tab::make(
                    'Основное',
                    [
                        Block::make('Информация о пользователе', [
                            $this->detailComponent($item, new Fields([
                                $referrerLink,
                                ...$fields, ])),
                            $detailForm,
                        ])
                            ->customAttributes([
                                'x-data' => '{
                                    extended_lines: ' . ($item->extended_lines ? 'true' : 'false') . '
                                }',
                            ]),
                        Divider::make(),
                        Block::make('Баланс', [
                            $balanceModal,
                            $balanceForm,
                        ]),
                        Divider::make(),
                        $inOutBlock,

                    ]
                )
                    ->name('main')

                    ->active(fn () => $activeTab === 'main'),
                Tab::make(
                    'Пакеты',
                    [
                        Heading::make("Пакеты {$item['username']}")->h(2),
                        TableBuilder::make()
                            ->withNotFound()
                            ->fields([
                                Date::make('Дата открытия', 'itc_created_at')->format('d.m.Y H:i:s')->showOnExport(),
                                Text::make('Сумма', 'amount', formatted: fn ($item) => round((float) $item['amount'], 2)),
                                Number::make('Сумма реинвеста', 'reinvest_profits_sum_amount', formatted: fn ($item) => round((float) $item['reinvest_profits_sum_amount'], 2)),
                                Number::make('Процент прибыли', 'month_profit_percent', formatted: fn ($item) => $item['month_profit_percent'] . '%'),
                                Number::make('Прибыль после реинвеста', 'profits_sum_amount', formatted: fn ($item) => round((float) $item['profits_sum_amount'], 2)
                                ),
                                Enum::make('Тип пакета', 'type')->attach(PackageTypeEnum::class),
                            ])
                            ->buttons([
                                ActionButton::make('')
                                    ->inModal(
                                        title: static fn ($item) => 'Редактирование пакета',
                                        content: $makeItcPackageForm,
                                        name: 'itc-package-modal'
                                    )
                                    ->icon('heroicons.pencil')
                                    ->primary()
                                    ->onClick(
                                        fn () => 'event.stopPropagation()',
                                        'stop'
                                    ),
                                ActionButton::make('')
                                    ->inModal(
                                        title: static fn ($item) => 'Реинвесты по пакету',
                                        content: static function ($item) {
                                            $reinvests = $item['reinvest_profits'];
                                            $blockItems = [];

                                            if (! empty($reinvests)) {
                                                $blockItems[] = ActionButton::make('Снять все реинвесты', function () use ($reinvests) {
                                                    $uuids = array_column($reinvests, 'uuid');

                                                    return route('reinvest-profit-withdraw-bulk', ['uuids' => implode(',', $uuids)]);
                                                })
                                                    ->icon('heroicons.arrow-down-tray')
                                                    ->async(method: 'POST')
                                                    ->secondary();
                                            }

                                            $blockItems[] = TableBuilder::make()
                                                ->withNotFound()
                                                ->fields([
                                                    Date::make('Дата реинвеста', 'created_at')->format('d.m.Y H:i:s')->showOnExport(),
                                                    Date::make('Дата разморозки', 'matured_at')->format('d.m.Y H:i:s')->showOnExport(),
                                                    Text::make('Размер реинвеста', 'amount', formatted: fn ($re) => round((float) $re['amount'], 2))->showOnExport(),
                                                ])
                                                ->buttons([
                                                    ActionButton::make('Снять на баланс', fn ($re) => route('reinvest-profit-withdraw', ['uuid' => $re['uuid']]))
                                                        ->async(method: 'POST')
                                                        ->showInDropdown(),
                                                    //                                                    ActionButton::make('Отменить реинвесты', fn(array $re) => route('reinvest-profit-delete', ['uuid' => $re['uuid']]))
                                                    //                                                        ->async(method: 'DELETE')
                                                    //                                                        ->showInDropdown(),
                                                    ActionButton::make('Удалить реинвесты', fn (array $re) => route('reinvest-profit-remove-all', ['uuid' => $re['uuid']]))
                                                        ->async(method: 'POST')
                                                        ->showInDropdown(),
                                                    ActionButton::make('Добавить срок работы', fn (array $re) => route('reinvest-profit-extend', ['uuid' => $re['uuid']]))
                                                        ->async('POST')
                                                        ->showInDropdown(),
                                                ])
                                                ->items($reinvests);

                                            return Block::make($blockItems);
                                        },
                                        name: 'package'
                                    )
                                    ->icon('heroicons.currency-dollar')
                                    ->secondary()
                                    ->onClick(
                                        fn () => 'event.stopPropagation()',
                                        'stop'
                                    ),

                            ])
                            ->items($packages)
                            ->trAttributes(
                                function (mixed $data, int $row, ComponentAttributeBag $attr): ComponentAttributeBag {
                                    $attr->setAttributes([
                                        'data-row-key' => $data['uuid'],
                                        '@click.stop.prevent' => '$event.currentTarget.querySelector(\'.btn.btn-primary\')?.click()',
                                        'style' => 'cursor: pointer;',
                                    ]);

                                    return $attr;
                                }
                            ),
                    ]
                )->name('packages')
                    ->active(fn () => $activeTab === 'packages'),
                Tab::make(
                    'Рефералы',
                    [
                        FormBuilder::make()
                            ->action('/itcapitalmoonshineadminpanel/partners')
                            ->fields([
                                Text::make('Добавить реферала', 'user'),
                                Hidden::make('user_id')->fill($item->id),
                            ])
                            ->async(asyncEvents: [
                                'table-updated-users',
                                'form-reset-add-referral',
                            ])
                            ->name('add-referral')
                            ->submit('Добавить партнера'),
                        TableBuilder::make()
                            ->withNotFound()
                            ->fields([
                                Text::make('ID'),
                                Url::make('Имя пользователя',
                                    'username',
                                    fn ($partner) => to_page(
                                        page: new UserDetailPage(),
                                        resource: new UserResource(),
                                        params: ['resourceItem' => $partner['id']]
                                    )
                                )
                                    ->title(
                                        fn ($href, Url $field) => $field->getData()['username']),
                                Text::make('Адрес электронной почты', 'email'),

                            ])
                            ->items($partners)
                            ->buttons([
                                ActionButton::make('')
                                    ->inModal(
                                        title: static fn ($partner) => 'Переназначить реферера',
                                        content: $changeReferralForm,
                                        name: 'itc-package-modal'
                                    )
                                    ->icon('heroicons.pencil')
                                    ->primary()
                                    ->onClick(
                                        fn () => 'event.stopPropagation()',
                                        'stop'
                                    ),
                            ])
                            ->trAttributes(
                                function (mixed $data, int $row, ComponentAttributeBag $attr) {

                                    $url = to_page(
                                        page: new UserDetailPage(),
                                        resource: new UserResource(),
                                        params: ['resourceItem' => $data['id']],
                                    );
                                    $attr->setAttributes([
                                        'onclick' => "window.location='{$url}'",
                                        'style' => 'cursor: pointer;',
                                    ]);

                                    return $attr;
                                }
                            ),
                    ]
                )->name('referrals')
                    ->active(fn () => $activeTab === 'referrals'),
                Tab::make('Настройка рангов', [
                    // Форма с чекбоксом и полем "Ранг"
                    Block::make([
                        FormBuilder::make()
                            ->name('user-level-override-form')
                            ->asyncMethod('saveLevelOverride')
                            ->fields([
                                Switcher::make('Проценты премии переопределены', 'override_enabled')
                                    ->customAttributes([
                                        'x-model' => 'override_enabled',
                                    ])
                                    ->fill($item->levelOverride ? '1' : '0'),
                                Switcher::make('Ранг установлен вручную', 'overridden_rank')
                                    ->customAttributes([
                                        'x-model' => 'overridden_rank',
                                    ])
                                    ->fill($item->overridden_rank ? '1' : '0'),
                                Number::make('Ранг', 'rank')
                                    ->min(1)->max(8)
                                    ->fill($item->rank)
                                    ->customAttributes([
                                        'x-bind:disabled' => '!overridden_rank',
                                    ]),
                                Hidden::make('user_id')
                                    ->fill($item->id),
                                TableBuilder::make()
                                    ->editable()
                                    ->fields([
                                        Number::make('Ранг', 'partner_level_id')
                                            ->customAttributes(['class' => 'input-invisible rank-width'])->readonly(),
                                        Text::make('Тип премии', 'bonus_type')
                                            ->customAttributes(['class' => 'input-invisible'])
                                            ->readonly(),
                                        Number::make('Линия 1', 'line_1')
                                            ->step(0.01)
                                            ->customAttributes([
                                                'x-bind:disabled' => '!override_enabled',
                                            ]),
                                        Number::make('Линия 2', 'line_2')
                                            ->step(0.01)
                                            ->customAttributes([
                                                'x-bind:disabled' => '!override_enabled',
                                            ]),
                                        Number::make('Линия 3', 'line_3')
                                            ->step(0.01)
                                            ->customAttributes([
                                                'x-bind:disabled' => '!override_enabled',
                                            ]),
                                        Number::make('Линия 4', 'line_4')
                                            ->step(0.01)
                                            ->customAttributes([
                                                'x-bind:disabled' => '!override_enabled',
                                            ]),
                                        Number::make('Линия 5', 'line_5')
                                            ->step(0.01)
                                            ->customAttributes([
                                                'x-bind:disabled' => '!override_enabled',
                                            ]),
                                    ])
                                    ->items(
                                        PartnerLevelPercent::asGridRows(override: $override)
                                    )
                                    ->customAttributes([
                                        'x-bind:class' => "!override_enabled ? 'mns-disabled-edit' : ''",
                                        'class' => 'table-override-percents',
                                    ])
                                    ->tdAttributes(
                                        function (mixed $data, int $row, int $cell, ComponentAttributeBag $attr) {
                                            if ($cell === 0) {
                                                $existing = $attr->get('class', '');
                                                $attr->setAttributes([
                                                    'class' => trim($existing),
                                                    'style' => 'position:sticky;left:0;background:#fff;min-width:100px;max-width:100px;width:100px;',
                                                ]);
                                            }

                                            if ($cell === 1) {
                                                $existing = $attr->get('class', '');
                                                $attr->setAttributes([
                                                    'class' => trim($existing),
                                                    'style' => 'position:sticky;left:80px;min-width:100px;max-width:100px;width:100px;',
                                                ]);
                                            }

                                            if ($cell >= 2) {
                                                $existing = $attr->get('class', '');
                                                $attr->setAttributes([
                                                    'class' => trim($existing),
                                                    'style' => 'min-width:120px;max-width:120px;width:120px;',
                                                ]);
                                            }

                                            //                                            if ($cell >= 0) {
                                            //                                                $existing = $attr->get('class', '');
                                            //                                                $attr->setAttributes([
                                            //                                                    'class' => trim($existing),
                                            //                                                    'style' => 'height: 40px;line-height: 40px;',
                                            //                                                ]);
                                            //                                            }
                                            return $attr;
                                        }
                                    )
                                    ->sticky()
                                    ->name('percentsOverride'),
                            ])
                            ->submit('Сохранить'),
                    ])
                        ->customAttributes([
                            'x-data' => '{
                                override_enabled: ' . ($item->levelOverride ? 'true' : 'false') . ',
                                overridden_rank: ' . (($item->overridden_rank) ? 'true' : 'false') . '
                            }',
                        ]),
                ])
                    ->name('level_settings'),
                Tab::make(
                    'Журнал',
                    [
                        Tabs::make([
                            Tab::make(
                                'Администратор',
                                [
                                    TableBuilder::make()
                                        ->withNotFound()
                                        ->fields([
                                            Text::make('Действие', 'action'),
                                            Text::make('Старые значения', 'old_values'),
                                            Text::make('Новые значения', 'new_values'),
                                            Text::make('Сумма операции', 'operation_amount'),
                                            Text::make('Дата', 'date'),
                                        ])
                                        ->items($adminLogs),
                                ]
                            ),
                            Tab::make('Пользователь', [
                                TableBuilder::make()
                                    ->withNotFound()
                                    ->fields([
                                        Text::make('Действие', 'action'),
                                        Text::make('Тип', 'type'),
                                        Text::make('Сумма операции', 'operation_amount'),
                                        Text::make('Пользователь', 'from_user'),
                                        Text::make('Дата', 'date'),
                                    ])
                                    ->items($userLogs)
                                    ->trAttributes(function (array $data, int $row, ComponentAttributeBag $attributes): ComponentAttributeBag {
                                        // в $data['action'] лежит «Увеличение баланса» или «Уменьшение баланса»
                                        $color = $data['action'] === 'Увеличение баланса' ? 'green' : 'red';

                                        return $attributes->merge([
                                            'style' => "color: {$color};",
                                        ]);
                                    }),
                            ]),
                        ]),
                    ]
                )
                    ->name('logs')
                    ->active(fn () => $activeTab === 'logs'),
            ]),
        ];
    }

    public function giveGift(MoonShineFormRequest $request): MoonShineJsonResponse
    {
        return MoonShineJsonResponse::make();
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer(),
        ];
    }

    public function breadcrumbs(): array
    {
        $user = $this->getResource()->getItem();

        return [
            // 1) Ссылка на список
            to_page(
                page: new UserIndexPage(),
                resource: new UserResource()
            ) => __('Пользователи'),

            // 2) Текущий пункт — имя пользователя, без ссылки
            '#' => $user->username,
        ];
    }

    public function title(): string
    {
        // получаем модель из ресурса
        $user = $this->getResource()->getItem();

        // возвращаем username вместо статичного «Пользователи»
        return $user->username;
    }
}
