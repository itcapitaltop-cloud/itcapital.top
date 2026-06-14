<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Actions\Staking\CreateStakingPackageAction;
use App\Actions\User\InvalidateSessionUserAction;
use App\Contracts\Logs\LogRepositoryContract;
use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Partner;
use App\Models\PartnerClosure;
use App\Models\PartnerLevel;
use App\Models\User;
use App\Models\UserLevelOverride;
use App\Models\UserLevelPercentOverride;
use App\Models\UserSummary;
use App\MoonShine\Handlers\GoogleSheetsExportIndexDataHandler;
use App\MoonShine\Pages\User\UserDetailPage;
use App\MoonShine\Pages\User\UserFormPage;
use App\MoonShine\Pages\User\UserIndexPage;
use App\Services\ActivityLog\PartnerReferralActivityService;
use App\Services\Package\PackageDefinitionResolver;
use App\Services\Package\Staking\StakingAccrualService;
use App\Services\User\UserRankServices;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\Enums\ToastType;
use MoonShine\Fields\Checkbox;
use MoonShine\Fields\Email;
use MoonShine\Fields\Range;
use MoonShine\Fields\Text;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineRequest;
use MoonShine\Pages\Page;
use MoonShine\Resources\ModelResource;
use Throwable;

/**
 * @extends ModelResource<User>
 */
class UserResource extends ModelResource
{
    protected string $model = User::class;

    protected string $title = 'Пользователи';

    protected bool $saveFilterState = false;

    protected array $with = ['summary'];

    private ?Paginator $cachedPaginator = null;

    /**
     * Мемоизируем paginate(), чтобы повторный вызов внутри страницы индекса
     * (явный в UserIndexPage::mainLayer() + внутренний в parent::mainLayer())
     * не выполнял запросы count(*) и eager-load summary дважды.
     *
     * @throws Throwable
     */
    public function paginate(): Paginator
    {
        return $this->cachedPaginator ??= parent::paginate();
    }

    /**
     * @return list<Page>
     */
    public function filters(): array
    {
        return [
            Range::make('Баланс', 'buy_packages_sum')
                ->onApply(function (Builder $q, array $value) {
                    // если ни from, ни to не заданы — ничего не делаем
                    if (is_null($value['from']) && is_null($value['to'])) {
                        return;
                    }

                    // сгруппировать AND (banned_at IS NULL AND диапазон)
                    $q->where(function (Builder $q2) use ($value) {
                        $q2->whereNull('users.banned_at');

                        if (! is_null($value['from'])) {
                            $q2->where('user_summary.buy_packages_sum', '>=', $value['from']);
                        }

                        if (! is_null($value['to'])) {
                            $q2->where('user_summary.buy_packages_sum', '<=', $value['to']);
                        }
                    });
                }),

            Range::make('Реинвесты', 'reinvests_sum')
                ->onApply(function (Builder $q, array $value) {
                    if (is_null($value['from']) && is_null($value['to'])) {
                        return;
                    }

                    $q->where(function (Builder $q2) use ($value) {
                        $q2->whereNull('users.banned_at');

                        if (! is_null($value['from'])) {
                            $q2->where('user_summary.reinvests_sum', '>=', $value['from']);
                        }

                        if (! is_null($value['to'])) {
                            $q2->where('user_summary.reinvests_sum', '<=', $value['to']);
                        }
                    });
                }),

            Range::make('Линий в партнёрке', 'partners_count')
                ->onApply(function (Builder $q, array $value) {
                    if (is_null($value['from']) && is_null($value['to'])) {
                        return;
                    }

                    $q->where(function (Builder $q2) use ($value) {
                        $q2->whereNull('users.banned_at');

                        if (! is_null($value['from'])) {
                            $q2->where('user_summary.partners_count', '>=', $value['from']);
                        }

                        if (! is_null($value['to'])) {
                            $q2->where('user_summary.partners_count', '<=', $value['to']);
                        }
                    });
                }),

            Checkbox::make('Показать пустых', 'show_empty')
                ->onApply(function (Builder $q, $value) {
                    if (! $value) {
                        return;
                    }

                    $q->withoutGlobalScope('notBanned')
                        ->orWhere('user_summary.buy_packages_sum', 0)
                        ->whereNull('users.banned_at');
                }),

            Checkbox::make('Показать забаненных', 'show_banned')
                ->onApply(function (Builder $q, $value) {
                    if ($value) {
                        // убираем скрытие по умолчанию и добавляем всех, у кого banned_at НЕ NULL
                        $q->withoutGlobalScope('notBanned')
                            ->orWhereNotNull('users.banned_at');
                    }
                }),
        ];
    }

    public function fields(): array
    {
        return [

            Email::make('E‑mail', 'email')
                ->showOnExport(),

            Text::make('Имя пользователя', 'username')
                ->showOnExport(),
        ];
    }

    public function pages(): array
    {
        return [
            UserIndexPage::make($this->title()),
            UserFormPage::make(
                $this->getItemID()
                    ? __('moonshine::ui.edit')
                    : __('moonshine::ui.add')
            ),
            UserDetailPage::make(__('moonshine::ui.show')),
        ];
    }

    /**
     * @param User $item
     * @return array<string, string[]|string>
     *
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }

    public function indexButtons(): array
    {
        return [];
    }

    public function resolveItemQuery(): Builder
    {
        return parent::resolveItemQuery()
            ->withoutGlobalScope('notBanned')
            ->with(['summary', 'referrer', 'levelOverride']);
    }

    public function ban(): MoonShineJsonResponse
    {
        $user = User::query()->find($this->getItemID());
        $user->banned_at = now();
        $user->save();

        $url = to_page(
            page: new UserDetailPage(),
            resource: new UserResource(),
            params: ['resourceItem' => $user->id],
        );

        return MoonShineJsonResponse::make()
            ->toast('Пользователь забанен.')
            ->redirect($url);
    }

    public function unban(): MoonShineJsonResponse
    {
        $user = User::withoutGlobalScope('notBanned')->findOrFail($this->getItemID());
        $user->banned_at = null;
        $user->save();

        $url = to_page(
            page: new UserDetailPage(),
            resource: new UserResource(),
            params: ['resourceItem' => $user->id],
        );

        return MoonShineJsonResponse::make()
            ->toast('Пользователь разбанен.')
            ->redirect($url);
    }

    public function changePassword(MoonShineRequest $request): MoonShineJsonResponse
    {
        $user = User::withoutGlobalScope('notBanned')->findOrFail($request->input('user_id'));
        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        new InvalidateSessionUserAction($user)->execute();

        $url = to_page(
            page: new UserDetailPage(),
            resource: new UserResource(),
            params: ['resourceItem' => $user->id],
        );

        return MoonShineJsonResponse::make()
            ->toast('Пароль успешно изменён.')
            ->redirect($url);
    }

    public function getActiveActions(): array
    {
        return ['view'];
    }

    public function trAttributes(): Closure
    {
        return function (User $item, int $row, ComponentAttributeBag $attr): ComponentAttributeBag {
            $url = to_page(
                page: new UserDetailPage(),
                resource: new UserResource(),
                params: ['resourceItem' => $item->id],
            );
            $attr->setAttributes([
                'onclick' => "window.location='{$url}'",
                'style' => 'cursor: pointer;',
            ]);

            return $attr;
        };
    }

    public function search(): array
    {
        return ['email', 'username', 'first_name', 'last_name'];
    }

    /**
     * @throws Throwable
     */
    public function update(MoonShineRequest $request): MoonShineJsonResponse
    {
        try {
            $data = $request->all();
            $item = User::withoutGlobalScope('notBanned')->find($data['id']);

            if (! $item) {
                throw new ModelNotFoundException("Пользователь с ID {$data['id']} не найден");
            }

            $old = $item->only(['username', 'email', 'rank']);

            unset($data['resourceItem'], $data['method'], $data['_token'], $data['id']);
            $referrerUsername = $data['referrer_username'] ?? null;

            $oldReferrerId = Partner::where('user_id', $item->id)->value('partner_id');
            $logRepo = app(LogRepositoryContract::class);
            unset($data['referrer_username']);

            Log::channel('source')->debug($data);

            $item->fill($data);
            $item->save();

            $new = $item->only(['username', 'email', 'rank']);

            $changes = array_diff_assoc($new, $old);

            if ($referrerUsername) {
                $referrer = User::where('username', $referrerUsername)->first();

                if ($referrer) {
                    [$partner, $newReferrerId] = DB::transaction(function () use ($item, $referrer, $oldReferrerId) {
                        $partner = Partner::updateOrCreate(
                            ['user_id' => $item->id],
                            ['partner_id' => $referrer->id]
                        );
                        $newReferrerId = $referrer->id;

                        if ($oldReferrerId !== $newReferrerId) {
                            $userId = (int) $item->id;
                            $newPartnerId = (int) $newReferrerId;

                            // Поддерево пользователя (сам + все его потомки), depth относительно $userId
                            $subtree = PartnerClosure::query()
                                ->where('ancestor_id', $userId)
                                ->get(['descendant_id', 'depth']);

                            $descendantIds = $subtree->pluck('descendant_id')->all();

                            // Старые предки пользователя (кроме самого пользователя)
                            $oldAncestorIds = PartnerClosure::query()
                                ->where('descendant_id', $userId)
                                ->where('depth', '>', 0)
                                ->pluck('ancestor_id')
                                ->all();

                            // Удаляем все старые пути: старые предки -> любой узел поддерева
                            if ($oldAncestorIds && $descendantIds) {
                                PartnerClosure::query()
                                    ->whereIn('ancestor_id', $oldAncestorIds)
                                    ->whereIn('descendant_id', $descendantIds)
                                    ->delete();
                            }

                            // Новые предки: все предки нового партнёра (включая его самого с depth=0)
                            $newAncestors = PartnerClosure::query()
                                ->where('descendant_id', $newPartnerId)
                                ->get(['ancestor_id', 'depth']);

                            // Вставляем новые пути:
                            // depth = depth(ancestor -> newPartner) + 1 + depth(user -> descendant)
                            $bulk = [];

                            foreach ($newAncestors as $na) {
                                foreach ($subtree as $sd) {
                                    $bulk[] = [
                                        'ancestor_id' => (int) $na->ancestor_id,
                                        'descendant_id' => (int) $sd->descendant_id,
                                        'depth' => (int) $na->depth + 1 + (int) $sd->depth,
                                    ];

                                    if (count($bulk) >= 1000) {
                                        PartnerClosure::insert($bulk);
                                        $bulk = [];
                                    }
                                }
                            }

                            if (! empty($bulk)) {
                                PartnerClosure::insert($bulk);
                            }
                        }

                        return [$partner, $newReferrerId];
                    });

                    // Логируем смену реферера только если он изменился
                    if ($oldReferrerId !== $newReferrerId) {
                        $logRepo->updated(
                            $partner,
                            'update_referrer',
                            ['partner_id' => $oldReferrerId],
                            ['partner_id' => $newReferrerId],
                            $item->id
                        );

                        app(PartnerReferralActivityService::class)->logAttached($item, $referrer, 'admin');
                    }
                    Artisan::call('user:use-rank --no-bonus');
                }
            }

            foreach ($changes as $field => $newValue) {
                $logRepo->updated(
                    $item,
                    "update_user_{$field}",
                    [$field => $old[$field]],
                    [$field => $newValue],
                    $item->id
                );
            }

            $url = to_page(
                page: new UserDetailPage(),
                resource: new UserResource(),
                params: ['resourceItem' => $item->id],
            );

            return MoonShineJsonResponse::make()
                ->toast('Сохранено.')
                ->redirect($url);
        } catch (Throwable $e) {
            return MoonShineJsonResponse::make()
                ->toast('Ошибка: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * @throws ConnectionException
     */
    public function updateBalance(MoonShineRequest $request): MoonShineJsonResponse
    {
        $transactionRepo = app(TransactionRepositoryContract::class);

        $userId = (int) $request->input('user_id');
        $newInvest = (float) $request->input('investments_sum');
        $newPartner = (float) $request->input('partner_balance');
        $newRegular = (float) $request->input('regular_bonus');

        $summary = UserSummary::firstWhere('user_id', $userId);
        $origInvest = (float) $transactionRepo->getBalanceAmountByUserIdAndType($userId, BalanceTypeEnum::MAIN);
        $origPartner = (float) $transactionRepo->getBalanceAmountByUserIdAndType($userId, BalanceTypeEnum::PARTNER);
        $origRegular = (float) $transactionRepo->getRegularBonus($userId);

        $deltaInvest = round($newInvest - $origInvest, 2);
        $deltaPartner = round($newPartner - $origPartner, 2);
        $deltaRegular = round($newRegular - $origRegular, 2);

        $logRepo = app(LogRepositoryContract::class);
        // отправляем изменение инвестиций

        if ($deltaRegular != 0) {
            $txRegular = $transactionRepo->commonStore(
                new CreateTransactionDto(
                    userId: $userId,
                    trxType: TrxTypeEnum::HIDDEN_DEPOSIT,
                    balanceType: BalanceTypeEnum::REGULAR_PREMIUM,
                    amount: (string) $deltaRegular,
                    acceptedAt: Carbon::now()->toDateTimeString(),
                    prefix: 'HD-'
                )
            );

            $logRepo->updated(
                $txRegular,
                'update_regular_bonus',
                ['regular_bonus' => $origRegular],
                ['regular_bonus' => $newRegular],
                $userId
            );
        }

        if ($deltaInvest != 0) {
            $txMain = $transactionRepo->commonStore(new CreateTransactionDto(
                userId: $userId,
                trxType: TrxTypeEnum::HIDDEN_DEPOSIT,
                balanceType: BalanceTypeEnum::MAIN,
                amount: (string) $deltaInvest,
                acceptedAt: Carbon::now()->toDateTimeString(),
                prefix: 'HD-'
            ));

            $logRepo->updated(
                $txMain,
                'update_investments_sum',
                ['investments_sum' => $origInvest],
                ['investments_sum' => $newInvest],
                $userId
            );
        }

        if ($deltaPartner != 0) {
            $txPartner = $transactionRepo->commonStore(new CreateTransactionDto(
                userId: $userId,
                trxType: TrxTypeEnum::HIDDEN_DEPOSIT,
                balanceType: BalanceTypeEnum::PARTNER,
                amount: (string) $deltaPartner,
                acceptedAt: Carbon::now()->toDateTimeString(),
                prefix: 'HD-'
            ));

            $logRepo->updated(
                $txPartner,
                'update_partner_balance',
                ['partner_balance' => $origPartner],
                ['partner_balance' => $newPartner],
                $userId
            );
        }

        $url = to_page(
            page: new UserDetailPage(),
            resource: new UserResource(),
            params: ['resourceItem' => $userId],
        );

        return MoonShineJsonResponse::make()
            ->toast('Сохранено')
            ->redirect($url);
    }

    /**
     * @throws \LeMaX10\SimpleActions\Exceptions\ActionHandlerMethodNotFoundException
     * @throws \Throwable
     */
    public function createPackage(
        MoonShineRequest $request,
    ): MoonShineJsonResponse {
        $itcRepo = app(ItcPackageRepositoryContract::class);
        $transactionRepo = app(TransactionRepositoryContract::class);
        $packageDefinitionResolver = app(PackageDefinitionResolver::class);

        $userId = (int) $request->input('user_id');
        $packageDefinition = $packageDefinitionResolver->resolveById((int) $request->input('package_definition_id'));
        $packageType = $packageDefinition->type;

        Log::debug('[UserResource.createPackage] start', [
            'admin_id' => auth()->id(),
            'target_user_id' => $userId,
            'package_definition_id' => $packageDefinition->id,
            'package_slug' => $packageDefinition->slug,
            'package_type' => $packageType->value,
            'requested_amount' => $request->input('amount'),
            'requested_percent' => $request->input('percent'),
            'requested_duration' => $request->input('duration'),
        ]);

        $shouldRecalculateRanks = false;

        if ($packageType === PackageTypeEnum::STAKING) {
            $package = CreateStakingPackageAction::make()
                ->run($userId, (float) $request->input('amount'), (float) $request->input('percent'), $packageDefinition->id);

            new StakingAccrualService()
                ->accrueAdminTopUpBonus($package, (float) $request->input('amount'), $userId);

            $shouldRecalculateRanks = true;
        } else {
            $isPresent = $packageType === PackageTypeEnum::PRESENT;
            $durationMonths = $isPresent
                ? (int) $request->input('duration')
                : $packageDefinition->duration_months;
            $profitPercent = $request->input('percent') ?: $packageDefinition->default_profit_percent;

            Log::debug('[UserResource.createPackage] resolved package definition', [
                'admin_id' => auth()->id(),
                'target_user_id' => $userId,
                'package_type' => $packageType->value,
                'package_definition_id' => $packageDefinition->id,
                'default_profit_percent' => $packageDefinition->default_profit_percent,
                'selected_profit_percent' => $profitPercent,
                'definition_duration_months' => $packageDefinition->duration_months,
                'selected_duration_months' => $durationMonths,
            ]);

            /* DTO транзакции */
            $dto = new CreateTransactionDto(
                userId: $userId,
                trxType: $isPresent ? TrxTypeEnum::PRESENT_PACKAGE : TrxTypeEnum::BUY_PACKAGE,
                balanceType: BalanceTypeEnum::MAIN,
                amount: $request->input('amount'),
                acceptedAt: Carbon::now(),
                prefix: 'ITC-',
            );

            /* Данные пакета */
            $packageData = [
                'package_definition_id' => $packageDefinition->id,
                'type' => $packageType,
                'month_profit_percent' => $profitPercent,
                'work_to' => $isPresent
                    ? now()->addMonths($durationMonths)
                    : now()->addMonths($durationMonths ?? 0),
                'duration_months' => $durationMonths,
            ];

            /* Создание через репозиторий */
            $itcRepo->createPackage(
                dto: $dto,
                packageData: $packageData,
                transactionRepo: $transactionRepo,
                skipBalance: $isPresent
            );

            $shouldRecalculateRanks = ! $isPresent;

            Log::info('[UserResource.createPackage] package creation completed', [
                'admin_id' => auth()->id(),
                'target_user_id' => $userId,
                'package_type' => $packageType->value,
                'package_definition_id' => $packageDefinition->id,
            ]);
        }

        if ($shouldRecalculateRanks) {
            Artisan::call('user:use-rank');
        }

        $url = to_page(
            page: new UserDetailPage(),
            resource: new UserResource(),
            params: ['resourceItem' => $userId],
        );

        return MoonShineJsonResponse::make()
            ->toast('Пакет создан.')
            ->redirect($url);
    }

    public function saveLevelOverride(MoonShineRequest $request): MoonShineJsonResponse
    {
        $data = [];

        try {
            $data = $request->all();

            $userId = $data['user_id'] ?? null;
            $user = User::withoutGlobalScope('notBanned')->find($userId);

            if (! $user) {
                throw new Exception("Пользователь с ID $userId не найден");
            }

            // Статусы галочек
            $overrideEnabled = (bool) ($data['override_enabled'] ?? false);
            $overriddenRank = (bool) ($data['overridden_rank'] ?? false);

            Log::debug('[UserResource.saveLevelOverride] start', [
                'user_id' => $user->id,
                'old_rank' => $user->rank,
                'requested_rank' => $data['rank'] ?? null,
                'old_overridden_rank' => (bool) $user->overridden_rank,
                'requested_overridden_rank' => $overriddenRank,
                'old_overridden_rank_from' => $user->overridden_rank_from?->toDateTimeString(),
                'override_enabled' => $overrideEnabled,
            ]);

            $url = to_page(
                page: new UserDetailPage(),
                resource: new UserResource(),
                params: ['resourceItem' => $user->id],
            );

            $rank = $overriddenRank
                ? $data['rank'] ?? $user->rank
                : $user->rank;

            $level = PartnerLevel::where('level', $rank)->first();

            if (! $level) {
                throw new Exception('Уровень не найден для ранга ' . $rank);
            }

            if (! $overriddenRank && $user->overridden_rank) {
                $oldRank = (int) $user->rank;

                $user->forceFill([
                    'overridden_rank' => false,
                    'overridden_rank_from' => null,
                ])->save();

                $user->refresh();
                app(UserRankServices::class)->syncRankToNatural($user);
                $user->refresh();

                Log::info('[UserResource.saveLevelOverride] manual rank disabled and natural rank restored', [
                    'user_id' => $user->id,
                    'old_rank' => $oldRank,
                    'natural_rank' => $user->rank,
                    'overridden_rank' => (bool) $user->overridden_rank,
                    'overridden_rank_from' => $user->overridden_rank_from?->toDateTimeString(),
                ]);
            } else {
                $resetOverrideDate = $overriddenRank
                    && (! $user->overridden_rank || (int) $user->rank !== (int) $rank);

                $user->rank = $rank;
                $user->overridden_rank = $overriddenRank;
                $user->overridden_rank_from = $overriddenRank
                    ? ($resetOverrideDate ? now() : $user->overridden_rank_from)
                    : null;
                $user->save();

                Log::debug('[UserResource.saveLevelOverride] manual rank state saved', [
                    'user_id' => $user->id,
                    'rank' => $user->rank,
                    'overridden_rank' => (bool) $user->overridden_rank,
                    'overridden_rank_from' => $user->overridden_rank_from?->toDateTimeString(),
                    'reset_override_date' => $resetOverrideDate,
                ]);
            }

            if (! $overrideEnabled) {

                $hasOverride = UserLevelOverride::where('user_id', $userId)->exists();

                // Удаляем все оверрайды
                if ($hasOverride) {
                    UserLevelOverride::where('user_id', $userId)->delete();
                    UserLevelPercentOverride::where('user_id', $userId)->delete();

                    return MoonShineJsonResponse::make()
                        ->toast('Настройки процентов сброшены на общие')
                        ->redirect($url);
                }

                return MoonShineJsonResponse::make()
                    ->toast('Сохранено')
                    ->redirect($url);
            }

            $ulo = UserLevelOverride::updateOrCreate(
                ['user_id' => $userId],
                [
                    'partner_level_id' => $level->id,
                    'active_from' => now(),
                ]
            );

            // Забираем данные таблицы
            $rows = $data['percentsOverride'] ?? [];

            // Полностью удаляем старые оверрайды процентов
            UserLevelPercentOverride::where('user_id', $userId)->delete();

            // Заполняем новые значения
            $newRows = [];

            foreach ($rows as $row) {
                foreach (range(1, 5) as $line) {
                    if (! isset($row["line_$line"])) {
                        continue;
                    }
                    $percent = $row["line_$line"];

                    if ($percent === '' || $percent === false || $percent === null) {
                        continue;
                    }
                    $newRows[] = [
                        'user_id' => $userId,
                        'partner_level_id' => $row['partner_level_id'],
                        'bonus_type' => $row['bonus_type'],
                        'line' => $line,
                        'percent' => $percent,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if ($newRows) {
                UserLevelPercentOverride::insert($newRows);
            }

            return MoonShineJsonResponse::make()
                ->toast('Сохранено')
                ->redirect($url);
        } catch (Throwable $e) {
            Log::error('[UserResource.saveLevelOverride] failed', [
                'user_id' => $data['user_id'] ?? null,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return MoonShineJsonResponse::make()
                ->toast('Ошибка: ' . $e->getMessage(), 'error');
        }
    }

    public function export(): ?ExportHandler
    {
        return GoogleSheetsExportIndexDataHandler::make('Экспортировать')
            ->spreadsheetId(config('services.export_file.users'))
            ->disk('public')
            ->filename('users-' . now()->format('Ymd-His'))
            ->withConfirm();
    }

    public function enableTest(MoonShineRequest $request): MoonShineJsonResponse
    {
        $user = User::findOrFail($request->get('resourceItem'));

        $url = to_page(
            page: new UserDetailPage(),
            resource: new UserResource(),
            params: ['resourceItem' => $user->id],
        );

        $user->update(['is_test' => true]);

        return MoonShineJsonResponse::make()
            ->toast('Пользователь стал тестовым', ToastType::SUCCESS)
            ->redirect($url);
    }

    public function disableTest(MoonShineRequest $request): MoonShineJsonResponse
    {
        $user = User::findOrFail($request->get('resourceItem'));

        $url = to_page(
            page: new UserDetailPage(),
            resource: new UserResource(),
            params: ['resourceItem' => $user->id],
        );

        $user->update(['is_test' => false]);

        return MoonShineJsonResponse::make()
            ->toast('Пользователь больше не тестовый', ToastType::SUCCESS)
            ->redirect($url);
    }
}
