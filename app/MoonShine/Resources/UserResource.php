<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Contracts\Logs\LogRepositoryContract;
use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\LogAdminAction;
use App\Models\Partner;
use App\Models\PartnerClosure;
use App\Models\PartnerLevel;
use App\Models\Transaction;
use App\Models\UserLevelOverride;
use App\Models\UserLevelPercentOverride;
use App\Models\UserSummary;
use App\MoonShine\Handlers\GoogleSheetsExportIndexDataHandler;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\MoonShine\Pages\User\UserIndexPage;
use App\MoonShine\Pages\User\UserFormPage;
use App\MoonShine\Pages\User\UserDetailPage;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\Fields\Checkbox;
use MoonShine\Fields\Email;
use MoonShine\Fields\Text;
use MoonShine\Fields\Range;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineRequest;
use MoonShine\Resources\ModelResource;
use MoonShine\Pages\Page;
use Throwable;

/**
 * @extends ModelResource<User>
 */
class UserResource extends ModelResource
{
    protected string $model = User::class;

    protected string $title = 'Пользователи';

    protected bool $saveFilterState = false;


    /**
     * @return list<Page>
     */


    public function filters(): array
    {
        return [

            Range::make('Баланс', 'buy_packages_sum')
                ->onApply(function(Builder $q, array $value) {
                    // если ни from, ни to не заданы — ничего не делаем
                    if (is_null($value['from']) && is_null($value['to'])) {
                        return;
                    }

                    // сгруппировать AND (banned_at IS NULL AND диапазон)
                    $q->where(function(Builder $q2) use ($value) {
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
                ->onApply(function(Builder $q, array $value) {
                    if (is_null($value['from']) && is_null($value['to'])) {
                        return;
                    }

                    $q->where(function(Builder $q2) use ($value) {
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
                ->onApply(function(Builder $q, array $value) {
                    if (is_null($value['from']) && is_null($value['to'])) {
                        return;
                    }

                    $q->where(function(Builder $q2) use ($value) {
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
                ->onApply(function(Builder $q, $value) {
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
     *
     * @return array<string, string[]|string>
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

    public function query(): Builder
    {
        $q = parent::query()
            ->leftJoin('user_summary', 'user_summary.user_id', 'users.id')
            ->select([
                'users.*',
                'user_summary.buy_packages_sum   as buy_packages_sum',
                'user_summary.reinvests_sum     as reinvests_sum',
                'user_summary.investments_sum     as investments_sum',
                'user_summary.partner_balance   as partner_balance',
                'user_summary.partners_count    as partners_count',
                'user_summary.first_package_at  as first_package_at',
            ]);
        return $q;
    }

    public function resolveItemQuery(): Builder
    {
        return parent::resolveItemQuery()
            ->withoutGlobalScope('notBanned');
    }

    public function ban(): MoonShineJsonResponse
    {
        $user = User::query()->find($this->getItemID());
        $user->banned_at = now();
        $user->save();

        $url = to_page(
            page:     new UserDetailPage,
            resource: new UserResource,
            params:   ['resourceItem' => $user->id],
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
            page:     new UserDetailPage,
            resource: new UserResource,
            params:   ['resourceItem' => $user->id],
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

        $url = to_page(
            page:     new UserDetailPage,
            resource: new UserResource,
            params:   ['resourceItem' => $user->id],
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
                page:     new UserDetailPage,
                resource: new UserResource,
                params:   ['resourceItem' => $item->id],
            );
            $attr->setAttributes([
                'onclick' => "window.location='{$url}'",
                'style'   => 'cursor: pointer;',
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
            if (!$item) {
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
                            $userId       = (int) $item->id;
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
                                        'ancestor_id'   => (int) $na->ancestor_id,
                                        'descendant_id' => (int) $sd->descendant_id,
                                        'depth'         => (int) $na->depth + 1 + (int) $sd->depth,
                                    ];
                                    if (count($bulk) >= 1000) {
                                        PartnerClosure::insert($bulk);
                                        $bulk = [];
                                    }
                                }
                            }
                            if (!empty($bulk)) {
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
                    }
                    Artisan::call("users:recalc-rank --no-bonus");
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
                page:     new UserDetailPage,
                resource: new UserResource,
                params:   ['resourceItem' => $item->id],
            );

            return MoonShineJsonResponse::make()
                ->toast('Сохранено.')
                ->redirect($url);
        }
        catch (Throwable $e) {
            return MoonShineJsonResponse::make()
                ->toast('Ошибка: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * @throws ConnectionException
     */
    public function updateBalance(MoonShineRequest $request): MoonShineJsonResponse
    {
        $userId     = (int)   $request->input('user_id');
        $newInvest  = (float) $request->input('investments_sum');
        $newPartner = (float) $request->input('partner_balance');

        $summary    = UserSummary::firstWhere('user_id', $userId);
        $origInvest = (float) $summary->investments_sum;
        $origPartner= (float) $summary->partner_balance;

        $deltaInvest  = round($newInvest  - $origInvest, 2);
        $deltaPartner = round($newPartner - $origPartner, 2);
        $logRepo = app(LogRepositoryContract::class);
        // отправляем изменение инвестиций
        $transactionRepo = app(TransactionRepositoryContract::class);

        if ($deltaInvest != 0) {
            $txMain = $transactionRepo->commonStore(new CreateTransactionDto(
                userId: $userId,
                trxType: TrxTypeEnum::HIDDEN_DEPOSIT,
                balanceType: BalanceTypeEnum::MAIN,
                amount: (string)$deltaInvest,
                acceptedAt: Carbon::now()->toDateTimeString(),
                prefix: 'HD-'
            ));

            $logRepo->updated(
                $txMain,
                'update_investments_sum',
                ['investments_sum'  => $origInvest],
                ['investments_sum'  => $newInvest],
                $userId
            );
        }

        if ($deltaPartner != 0) {
            $txPartner = $transactionRepo->commonStore(new CreateTransactionDto(
                userId: $userId,
                trxType: TrxTypeEnum::HIDDEN_DEPOSIT,
                balanceType: BalanceTypeEnum::PARTNER,
                amount: (string)$deltaPartner,
                acceptedAt: Carbon::now()->toDateTimeString(),
                prefix: 'HD-'
            ));

            $logRepo->updated(
                $txPartner,
                'update_partner_balance',
                ['partner_balance'  => $origPartner],
                ['partner_balance'  => $newPartner],
                $userId
            );
        }

        $url = to_page(
            page:     new UserDetailPage,
            resource: new UserResource,
            params:   ['resourceItem' => $userId],
        );

        return MoonShineJsonResponse::make()
            ->toast('Сохранено')
            ->redirect($url);
    }

    public function createPackage(
        MoonShineRequest $request,
    ): MoonShineJsonResponse {

        $itcRepo        = app(ItcPackageRepositoryContract::class);
        $transactionRepo = app(TransactionRepositoryContract::class);

        $userId    = (int) $request->input('user_id');
        $isPresent = $request->input('packageType') === PackageTypeEnum::PRESENT->value;

        /* DTO транзакции */
        $dto = new CreateTransactionDto(
            userId:      $userId,
            trxType:     $isPresent ? TrxTypeEnum::PRESENT_PACKAGE : TrxTypeEnum::BUY_PACKAGE,
            balanceType: BalanceTypeEnum::MAIN,
            amount:      $request->input('amount'),
            acceptedAt:  Carbon::now(),
            prefix:      'ITC-',
        );

        /* Данные пакета */
        $packageData = [
            'type'                 => $request->input('packageType'),
            'month_profit_percent' => $request->input('percent'),
            'work_to'              => $isPresent
                ? now()->addMonths((int) $request->input('duration'))
                : now()->addWeeks(30),
            'duration_months'      => $request->input('duration'),
        ];

        /* Создание через репозиторий */
        $itcRepo->createPackage(
            dto:             $dto,
            packageData:     $packageData,
            transactionRepo: $transactionRepo,
            skipBalance:     $isPresent
        );

        $url = to_page(
            page:     new UserDetailPage,
            resource: new UserResource,
            params:   ['resourceItem' => $userId],
        );

        return MoonShineJsonResponse::make()
            ->toast('Пакет создан.')
            ->redirect($url);
    }

    public function saveLevelOverride(MoonShineRequest $request): MoonShineJsonResponse
    {
        try {
            Log::channel('source')->debug($request->session()->token());
            $data = $request->all();

            $userId = $data['user_id'] ?? null;
            $user = User::withoutGlobalScope('notBanned')->find($userId);

            if (! $user) {
                throw new Exception("Пользователь с ID $userId не найден");
            }

            // Статусы галочек
            $overrideEnabled = (bool)($data['override_enabled'] ?? false);
            $overriddenRank  = (bool)($data['overridden_rank'] ?? false);

            $url = to_page(
                page:     new UserDetailPage,
                resource: new UserResource,
                params:   ['resourceItem' => $user->id],
            );

            $rank = $overriddenRank
                ? $data['rank'] ?? $user->rank
                : $user->rank;

            $level = PartnerLevel::where('level', $rank)->first();
            if (! $level) {
                throw new Exception('Уровень не найден для ранга ' . $rank);
            }

            if (! $overriddenRank && $user->overridden_rank) {
                $user->overridden_rank = false;
                $user->overridden_rank_from = null;
                $user->save();
                Artisan::call('users:recalc-rank --no-bonus', ['--user' => $user->id]);
            }
            else {
                $user->rank = $rank;
                $user->overridden_rank = $overriddenRank;
                $user->overridden_rank_from = $overriddenRank ? now() : null;
                $user->save();
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
                    'active_from'      => now(),
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
                    if (!isset($row["line_$line"])) continue;
                    $percent = $row["line_$line"];
                    if ($percent === '' || $percent === false || $percent === null) {
                        continue;
                    }
                    $newRows[] = [
                        'user_id'           => $userId,
                        'partner_level_id'  => $row['partner_level_id'],
                        'bonus_type'        => $row['bonus_type'],
                        'line'              => $line,
                        'percent'           => $percent,
                        'created_at'        => now(),
                        'updated_at'        => now(),
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
            return MoonShineJsonResponse::make()
                ->toast('Ошибка: ' . $e->getMessage(), 'error');
        }
    }

    public function export(): ?ExportHandler
    {
        return GoogleSheetsExportIndexDataHandler::make('Экспортировать')
            ->spreadsheetId(env('USERS_EXPORT_FILE_KEY'))
            ->disk('public')
            ->filename('users-'.now()->format('Ymd-His'))
            ->withConfirm();
    }
}
