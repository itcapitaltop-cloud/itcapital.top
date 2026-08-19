<?php

declare(strict_types=1);

namespace App\Services\ActivityLog;

use App\ActivityLog\ActivityManager;
use App\Dto\Activity\JournalFilterData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Activity\ActivityJournalCategoryEnum;
use App\Enums\LogActionTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\BusinessActivity;
use App\Models\Deposit;
use App\Models\ItcPackage;
use App\Models\Transaction;
use App\Models\Withdraw;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ActivityFeedService
{
    public function __construct(
        private readonly ActivityManager $activityManager,
    ) {}

    /**
     * @return Collection<int, array{date:string,event:string}>
     */
    public function packageFeed(int $userId, int $limit = 200): Collection
    {
        return $this->baseFeedQuery($userId, ActivityFeedTypeEnum::Packages)
            ->limit($limit)
            ->get()
            ->map(fn (BusinessActivity $activity): array => [
                'date' => $activity->created_at?->format('d.m.Y, H:i') ?? '',
                'event' => $this->activityManager->resolve($activity),
            ]);
    }

    /**
     * @return Collection<int, array{date:string,user:string,level:string|int,event:string}>
     */
    public function partnerFeed(int $userId, int $limit = 200): Collection
    {
        return $this->baseFeedQuery($userId, ActivityFeedTypeEnum::Partners)
            ->limit($limit)
            ->get()
            ->map(fn (BusinessActivity $activity): array => [
                'date' => $activity->created_at?->format('d.m.Y, H:i') ?? '',
                'user' => (string) ($activity->getExtraProperty('from_username')
                    ?? $activity->getExtraProperty('username')
                    ?? $activity->getExtraProperty('to_username')
                    ?? '—'),
                'level' => $activity->getExtraProperty('line', '—'),
                'event' => $this->activityManager->resolve($activity),
            ]);
    }

    /**
     * @return Collection<int, array{created_at:mixed,amount:mixed,arrow:string,type:string,status:string}>
     */
    public function financeFeed(int $userId, int $limit = 200): Collection
    {
        return $this->baseFeedQuery($userId, ActivityFeedTypeEnum::Finance)
            ->limit($limit)
            ->get()
            ->map(fn (BusinessActivity $activity): array => [
                'created_at' => $activity->created_at,
                'amount' => $activity->getExtraProperty('amount', 0),
                'arrow' => $this->financeArrow($activity),
                'type' => $this->activityManager->resolve($activity),
                'status' => $this->financeStatus($activity),
            ]);
    }

    /**
     * Страница пользовательских логов карточки. Полный paginate (с COUNT) нужен,
     * чтобы пагинация показывала сводку «Показано от X до Y из Z» и число страниц —
     * единое оформление журналов админки.
     *
     * @return LengthAwarePaginator<int, array{action:string,type:string,operation_amount:string,from_user:string,date:string}>
     */
    public function userDetailUserFeed(int $userId, int $perPage = 50, ?JournalFilterData $filter = null): LengthAwarePaginator
    {
        return $this->userDetailBaseQuery($userId, ActivityFeedTypeEnum::UserDetailUser, $filter)
            ->paginate($perPage, pageName: 'user_logs_page')
            ->withQueryString()
            ->appends(['journal_tab' => 'user'])
            ->through(function (BusinessActivity $activity): array {
                return [
                    'action' => $this->userDetailAction($activity),
                    'type' => $this->activityManager->resolve($activity),
                    'operation_amount' => $this->operationAmount($activity),
                    'from_user' => (string) ($activity->getExtraProperty('from_username')
                    ?? $activity->getExtraProperty('username')
                    ?? $activity->getExtraProperty('to_username')
                    ?? ''),
                    'date' => $activity->created_at?->format('d.m.Y H:i') ?? '',
                ];
            });
    }

    /**
     * Полная (без пагинации) выборка пользовательского журнала за период — для экспорта в файл.
     *
     * @return Collection<int, array{type:string,operation_amount:string,from_user:string,date:string}>
     */
    public function userDetailUserFeedForExport(int $userId, ?Carbon $dateFrom = null, ?Carbon $dateTo = null): Collection
    {
        $query = $this->userDetailBaseQuery($userId, ActivityFeedTypeEnum::UserDetailUser);

        if ($dateFrom !== null) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->where('created_at', '<=', $dateTo);
        }

        return $query
            ->get()
            ->map(function (BusinessActivity $activity): array {
                return [
                    'type' => $this->activityManager->resolve($activity),
                    'operation_amount' => $this->operationAmount($activity),
                    'from_user' => (string) ($activity->getExtraProperty('from_username')
                    ?? $activity->getExtraProperty('username')
                    ?? $activity->getExtraProperty('to_username')
                    ?? ''),
                    'date' => $activity->created_at?->format('d.m.Y H:i') ?? '',
                ];
            });
    }

    /**
     * Страница админских логов карточки. Полный paginate (с COUNT) нужен,
     * чтобы пагинация показывала сводку «Показано от X до Y из Z» и число страниц —
     * единое оформление журналов админки.
     *
     * @return LengthAwarePaginator<int, array{action:string,old_values:string,new_values:string,operation_amount:string,date:string}>
     */
    public function userDetailAdminFeed(int $userId, int $perPage = 50, ?JournalFilterData $filter = null): LengthAwarePaginator
    {
        return $this->userDetailBaseQuery($userId, ActivityFeedTypeEnum::UserDetailAdmin, $filter)
            ->where('description', '!=', LogActionTypeEnum::UPDATE_ITC_PACKAGE_AMOUNT->value)
            ->where('description', '!=', LogActionTypeEnum::WITHDRAW_PACKAGE_REINVEST_PROFIT->value)
            ->paginate($perPage, pageName: 'admin_logs_page')
            ->withQueryString()
            ->appends(['journal_tab' => 'admin'])
            ->through(function (BusinessActivity $activity): array {
                $oldValues = collect((array) $activity->getExtraProperty('old_values', []));
                $newValues = collect((array) $activity->getExtraProperty('new_values', []));
                $actionType = LogActionTypeEnum::tryFrom($activity->description);

                if (in_array($actionType, [
                    LogActionTypeEnum::APPROVE_TRANSACTION,
                    LogActionTypeEnum::REJECT_TRANSACTION,
                    LogActionTypeEnum::MODERATE_TRANSACTION,
                ], true)) {
                    return $this->transactionAdminFeedRow($activity);
                }

                if ($actionType === LogActionTypeEnum::CLOSE_ITC_PACKAGE) {
                    return $this->closePackageAdminFeedRow($activity);
                }

                if (in_array($actionType, [
                    LogActionTypeEnum::UPDATE_BENEFICIARY,
                    LogActionTypeEnum::DELETE_BENEFICIARY,
                ], true)) {
                    return $this->beneficiaryAdminFeedRow($activity);
                }

                if ($oldValues->isEmpty() && $newValues->isEmpty()) {
                    return $this->stakingAdminFeedRow($activity);
                }

                return [
                    'action' => $this->adminActionLabel($activity),
                    'old_values' => $oldValues->map(fn (mixed $value): string => $this->formatDisplayValue($value))->implode("\n"),
                    'new_values' => $newValues->map(fn (mixed $value): string => $this->formatDisplayValue($value))->implode("\n"),
                    'operation_amount' => $oldValues
                        ->map(function (mixed $oldValue, string|int $key) use ($newValues): string {
                            $newValue = $newValues->get((string) $key);

                            if (is_numeric($oldValue) && is_numeric($newValue)) {
                                $diff = (float) $newValue - (float) $oldValue;

                                if ($diff > 0) {
                                    return '+' . $this->formatAmount((string) $diff);
                                }

                                if ($diff < 0) {
                                    return $this->formatAmount((string) $diff);
                                }
                            }

                            return '';
                        })
                        ->filter()
                        ->implode("\n"),
                    'date' => $activity->created_at?->format('d.m.Y H:i') ?? '',
                ];
            });
    }

    /**
     * @return array<int, array{action:string,admin_login:string,old_values:string,new_values:string,date:string}>
     */
    public function globalAdminFeed(?int $limit = 200): array
    {
        $query = BusinessActivity::query()
            ->forFeed(ActivityFeedTypeEnum::GlobalAdmin)
            ->with(['causer', 'subject'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get()
            ->map(fn (BusinessActivity $activity): array => $this->globalAdminFeedRow($activity))
            ->toArray();
    }

    /**
     * @return array{action:string,admin_login:string,old_values:string,new_values:string,date:string}
     */
    public function globalAdminFeedRow(BusinessActivity $activity): array
    {
        return [
            'action' => $this->adminActionLabel($activity),
            'admin_login' => $this->activityAdminLogin($activity),
            'old_values' => $this->formatActivityValues((array) $activity->getExtraProperty('old_values', [])),
            'new_values' => $this->formatActivityValues((array) $activity->getExtraProperty('new_values', [])),
            'date' => $activity->created_at?->format('d.m.Y H:i') ?? '',
        ];
    }

    /**
     * Начисления и правки по стейкингу пишутся историческими вызовами activity(): вместо
     * old_values/new_values у них плоские amount/old_amount, поэтому обе колонки значений
     * остались бы пустыми.
     *
     * @return array{action:string,old_values:string,new_values:string,operation_amount:string,date:string}
     */
    private function stakingAdminFeedRow(BusinessActivity $activity): array
    {
        $oldAmount = $activity->getExtraProperty('old_amount');
        $newAmount = $activity->getExtraProperty('amount');

        return [
            'action' => $this->adminActionLabel($activity),
            'old_values' => $oldAmount === null ? '' : $this->formatAmount((string) $oldAmount),
            'new_values' => $oldAmount === null || $newAmount === null ? '' : $this->formatAmount((string) $newAmount),
            'operation_amount' => $newAmount === null ? '' : $this->formatAmount((string) $newAmount),
            'date' => $activity->created_at?->format('d.m.Y H:i') ?? '',
        ];
    }

    /**
     * @return array{action:string,old_values:string,new_values:string,operation_amount:string,date:string}
     */
    private function closePackageAdminFeedRow(BusinessActivity $activity): array
    {
        $oldValues = collect((array) $activity->getExtraProperty('old_values', []));
        $newValues = collect((array) $activity->getExtraProperty('new_values', []));

        return [
            'action' => $this->adminActionLabel($activity),
            'old_values' => (string) $oldValues->get('old_type', ''),
            'new_values' => (string) $newValues->get('new_type', ''),
            'operation_amount' => $this->formatAmount((string) ($oldValues->get('old_amount') ?? $newValues->get('new_amount') ?? '')),
            'date' => $activity->created_at?->format('d.m.Y H:i') ?? '',
        ];
    }

    /**
     * @return array{action:string,old_values:string,new_values:string,operation_amount:string,date:string}
     */
    private function beneficiaryAdminFeedRow(BusinessActivity $activity): array
    {
        $beneficiaryName = (string) ($activity->getExtraProperty('beneficiary_full_name')
            ?? data_get($activity->subject, 'full_name', ''));
        $action = $this->adminActionLabel($activity);

        if ($beneficiaryName !== '') {
            $action .= ': ' . $beneficiaryName;
        }

        return [
            'action' => $action,
            'old_values' => $this->formatBeneficiaryValues((array) $activity->getExtraProperty('old_values', [])),
            'new_values' => $this->formatBeneficiaryValues((array) $activity->getExtraProperty('new_values', [])),
            'operation_amount' => '',
            'date' => $activity->created_at?->format('d.m.Y H:i') ?? '',
        ];
    }

    /**
     * @param array<string, mixed> $values
     */
    private function formatBeneficiaryValues(array $values): string
    {
        if ($values === []) {
            return '—';
        }

        $labels = [
            'full_name' => 'ФИО',
            'phone' => 'Телефон',
            'social_url' => 'Социальные сети',
        ];

        return collect($values)
            ->map(fn (mixed $value, string $key): string => ($labels[$key] ?? $key) . ': ' . (string) $value)
            ->implode("\n");
    }

    /**
     * @return array{action:string,old_values:string,new_values:string,operation_amount:string,date:string}
     */
    private function transactionAdminFeedRow(BusinessActivity $activity): array
    {
        $transaction = $activity->subject;

        if (! $transaction instanceof Transaction) {
            return [
                'action' => $this->adminActionLabel($activity),
                'old_values' => '',
                'new_values' => '',
                'operation_amount' => '',
                'date' => $activity->created_at?->format('d.m.Y H:i') ?? '',
            ];
        }

        $actionType = LogActionTypeEnum::tryFrom($activity->description);
        $channel = $this->transactionChannelLabel($transaction);
        $oldStatus = $this->transactionAdminOldStatus($actionType);
        $newStatus = $this->transactionAdminNewStatus($actionType, $channel);

        return [
            'action' => $this->adminActionLabel($activity),
            'old_values' => $oldStatus,
            'new_values' => $newStatus,
            'operation_amount' => $this->formatAmount((string) $transaction->amount),
            'date' => $activity->created_at?->format('d.m.Y H:i') ?? '',
        ];
    }

    private function baseFeedQuery(int $userId, ActivityFeedTypeEnum $feed): Builder
    {
        return BusinessActivity::query()
            ->forUser($userId)
            ->forFeed($feed)
            ->with('subject')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * Журнал карточки собирается из двух источников.
     *
     * Основной — записи с проставленными user_id и feeds. Но события по стейкингу
     * пишутся историческими вызовами activity() без user_id и без feeds, поэтому по
     * первому пути они недостижимы: владелец у них определяется только через
     * subject → transaction → user_id. Второй источник повторяет ровно ту выборку,
     * которой пользуется журнал страницы «Стейкинг», чтобы обе страницы показывали
     * одинаковый набор токеновых событий.
     */
    private function userDetailBaseQuery(
        int $userId,
        ActivityFeedTypeEnum $feed,
        ?JournalFilterData $filter = null,
    ): Builder {
        $filter ??= new JournalFilterData();
        $category = $filter->category;

        $query = BusinessActivity::query()
            ->with('subject')
            ->where(function (Builder $query) use ($userId, $feed, $category): void {
                if ($category !== ActivityJournalCategoryEnum::Staking) {
                    $query->where(fn (Builder $feedQuery) => $this->feedSourceQuery($feedQuery, $userId, $feed, $category));
                }

                if ($category === null || $category === ActivityJournalCategoryEnum::Staking) {
                    $query->orWhere(fn (Builder $stakingQuery) => $this->stakingSourceQuery($stakingQuery, $userId, $feed));
                }
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($filter->dateFrom !== null) {
            $query->where('created_at', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo !== null) {
            $query->where('created_at', '<=', $filter->dateTo);
        }

        return $query;
    }

    /**
     * Записи с проставленными user_id и feeds — основной источник журнала.
     */
    private function feedSourceQuery(
        Builder $query,
        int $userId,
        ActivityFeedTypeEnum $feed,
        ?ActivityJournalCategoryEnum $category,
    ): void {
        $query
            ->forUser($userId)
            ->forFeed($feed)
            ->where(function (Builder $legacyQuery): void {
                $legacyQuery
                    ->where('subject_type', '!=', ItcPackage::class)
                    ->orWhereNotIn('description', $this->legacyPackageEvents());
            });

        if ($category !== null) {
            $query->forFeed($category->value);
        }
    }

    /**
     * Токеновые события. Исторические вызовы activity() по стейкингу не проставляют
     * ни user_id, ни feeds, поэтому владелец у них ищется через subject → transaction;
     * вторая ветка добирает business-события стейкинга, которые лежат в чужих фидах
     * (например, партнёрские премии по стейкингу помечены фидом «partners»).
     */
    private function stakingSourceQuery(Builder $query, int $userId, ActivityFeedTypeEnum $feed): void
    {
        $query
            ->where(function (Builder $ownedQuery) use ($userId, $feed): void {
                $feed === ActivityFeedTypeEnum::UserDetailAdmin
                    ? $ownedQuery->packagesStakingWithAdmin($userId)
                    : $ownedQuery->packagesStaking($userId);
            })
            ->orWhere(function (Builder $businessQuery) use ($userId, $feed): void {
                $businessQuery
                    ->forUser($userId)
                    ->forFeed($feed)
                    ->whereIn('description', $this->stakingBusinessEvents());
            });
    }

    /**
     * @return array<int, string>
     */
    private function stakingBusinessEvents(): array
    {
        return [
            ActivityEventTypeEnum::StakingPackagePurchased->value,
            ActivityEventTypeEnum::StakingPackageToppedUp->value,
            ActivityEventTypeEnum::StakingProfitAccrued->value,
            ActivityEventTypeEnum::StakingRegularBonusReceived->value,
            ActivityEventTypeEnum::StakingStartBonusReceived->value,
            ActivityEventTypeEnum::StakingProfitPercentChanged->value,
            ActivityEventTypeEnum::StakingStartBonusPercentChanged->value,
        ];
    }

    /**
     * Исторические события по пакетам, продублированные новыми business-событиями:
     * в основной выборке они лишние, но через staking-ветку приходят как единственный
     * источник по токенам.
     *
     * @return array<int, string>
     */
    private function legacyPackageEvents(): array
    {
        return [
            'top_up_package',
            'profit_accrued',
            'start_bonus_package',
            'regular_premium_package',
            'admin_package_purchased',
            'admin_package_changed_amount',
            'admin_package_changed_percentage',
            'admin_package_added_manual_profit',
            'admin_package_staking_changed_percentage',
        ];
    }

    private function financeArrow(BusinessActivity $activity): string
    {
        return match ($activity->description) {
            ActivityEventTypeEnum::DepositRequested->value,
            ActivityEventTypeEnum::DepositApproved->value,
            ActivityEventTypeEnum::DepositRejected->value => 'down',
            default => 'up',
        };
    }

    private function financeStatus(BusinessActivity $activity): string
    {
        return match ($activity->description) {
            ActivityEventTypeEnum::DepositRequested->value,
            ActivityEventTypeEnum::WithdrawRequested->value => __('activity/feed.status.on_moderation'),
            ActivityEventTypeEnum::DepositApproved->value,
            ActivityEventTypeEnum::WithdrawApproved->value => __('activity/feed.status.approved'),
            ActivityEventTypeEnum::DepositRejected->value,
            ActivityEventTypeEnum::WithdrawRejected->value => __('activity/feed.status.rejected'),
            default => __('activity/feed.empty'),
        };
    }

    private function userDetailAction(BusinessActivity $activity): string
    {
        if ($activity->description === LogActionTypeEnum::UPDATE_ITC_PACKAGE_AMOUNT->value) {
            $oldAmount = (float) collect((array) $activity->getExtraProperty('old_values', []))->get('amount', 0);
            $newAmount = (float) collect((array) $activity->getExtraProperty('new_values', []))->get('amount', 0);

            if ($newAmount > $oldAmount) {
                return __('activity/feed.user_detail.balance_increase');
            }

            if ($newAmount < $oldAmount) {
                return __('activity/feed.user_detail.balance_decrease');
            }

            return __('activity/feed.user_detail.action');
        }

        if (in_array($activity->description, $this->neutralUserDetailEvents(), true)) {
            return __('activity/feed.user_detail.action');
        }

        return in_array($activity->description, $this->positiveUserDetailEvents(), true)
            ? __('activity/feed.user_detail.balance_increase')
            : __('activity/feed.user_detail.balance_decrease');
    }

    /**
     * @return array<int, string>
     */
    private function positiveUserDetailEvents(): array
    {
        return [
            ActivityEventTypeEnum::DepositRequested->value,
            ActivityEventTypeEnum::DepositApproved->value,
            ActivityEventTypeEnum::PackageClosed->value,
            ActivityEventTypeEnum::PackageProfitAccrued->value,
            ActivityEventTypeEnum::PackageProfitWithdrawn->value,
            ActivityEventTypeEnum::PackageAmountWithdrawnToBalance->value,
            ActivityEventTypeEnum::PackageReinvestWithdrawnToBalance->value,
            ActivityEventTypeEnum::PartnerRegularBonusReceived->value,
            ActivityEventTypeEnum::PartnerStartBonusReceived->value,
            ActivityEventTypeEnum::StakingRegularBonusReceived->value,
            ActivityEventTypeEnum::StakingStartBonusReceived->value,
            ActivityEventTypeEnum::PartnerToMainTransferred->value,
            ActivityEventTypeEnum::PartnerTransferReceived->value,
            ActivityEventTypeEnum::RegularBonusTransferredToPartner->value,
            ActivityEventTypeEnum::StakingProfitAccrued->value,
            'start_bonus_package',
            'regular_premium_package',
            'profit_accrued',
            'admin_package_added_manual_profit',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function neutralUserDetailEvents(): array
    {
        return [
            ActivityEventTypeEnum::ReferralAddedToLine->value,
            ActivityEventTypeEnum::BecameReferralOfUser->value,
            ActivityEventTypeEnum::PackageReinvested->value,
            'admin_package_purchased',
            'admin_package_changed_amount',
            'admin_package_changed_percentage',
            ActivityEventTypeEnum::StakingProfitPercentChanged->value,
            ActivityEventTypeEnum::StakingStartBonusPercentChanged->value,
        ];
    }

    /**
     * Историческое staking-событие profit_accrued держит в `amount` весь баланс пакета,
     * а сумму операции — в `profit`; иначе в колонке «Сумма операции» оказался бы баланс.
     */
    private function operationAmount(BusinessActivity $activity): string
    {
        $amount = $activity->description === 'profit_accrued'
            ? $activity->getExtraProperty('profit', $activity->getExtraProperty('amount'))
            : $activity->getExtraProperty('amount');

        return $amount === null ? '' : $this->formatAmount((string) $amount);
    }

    private function adminActionLabel(BusinessActivity $activity): string
    {
        $actionType = LogActionTypeEnum::tryFrom($activity->description);

        if ($actionType !== null) {
            if ($actionType === LogActionTypeEnum::UPSERT_TOKEN_RATE) {
                return $activity->getExtraProperty('old_values') === []
                    ? __('activity/log_action.create_token_rate')
                    : __('activity/log_action.update_token_rate');
            }

            $transactionActionLabel = $this->transactionAdminActionLabel($activity, $actionType);

            if ($transactionActionLabel !== null) {
                return $transactionActionLabel;
            }

            if ($actionType === LogActionTypeEnum::UPDATE_ITC_PACKAGE_AMOUNT) {
                $packageUuid = (string) $activity->getExtraProperty('package_uuid', '');
                $oldAmount = $this->formatAmount((string) collect((array) $activity->getExtraProperty('old_values', []))->get('amount', '0'));
                $newAmount = $this->formatAmount((string) collect((array) $activity->getExtraProperty('new_values', []))->get('amount', '0'));

                if ($packageUuid !== '') {
                    return __('activity/admin.admin_package_changed_amount_user_feed', [
                        'uuid' => $packageUuid,
                        'old_amount' => $oldAmount,
                        'amount' => $newAmount,
                    ]);
                }
            }

            if ($actionType === LogActionTypeEnum::CLOSE_ITC_PACKAGE) {
                $packageUuid = data_get($activity->subject, 'uuid');

                if (is_string($packageUuid) && $packageUuid !== '') {
                    return __('activity/feed.admin_action.close_package', ['uuid' => $packageUuid]);
                }
            }

            return $actionType->label();
        }

        return $this->activityManager->resolve($activity);
    }

    private function transactionAdminActionLabel(BusinessActivity $activity, LogActionTypeEnum $actionType): ?string
    {
        if (! $activity->subject instanceof Transaction) {
            return null;
        }

        $suffix = match ($activity->subject->trx_type) {
            TrxTypeEnum::DEPOSIT => __('activity/feed.transaction.deposit_request'),
            TrxTypeEnum::WITHDRAW => __('activity/feed.transaction.withdraw_request'),
            default => null,
        };

        if ($suffix === null) {
            return null;
        }

        return match ($actionType) {
            LogActionTypeEnum::APPROVE_TRANSACTION => __('activity/feed.admin_action.approve_transaction', ['suffix' => $suffix]),
            LogActionTypeEnum::REJECT_TRANSACTION => __('activity/feed.admin_action.reject_transaction', ['suffix' => $suffix]),
            LogActionTypeEnum::MODERATE_TRANSACTION => __('activity/feed.admin_action.moderate_transaction', ['suffix' => $suffix]),
            default => null,
        };
    }

    private function transactionChannelLabel(Transaction $transaction): string
    {
        if ($transaction->trx_type === TrxTypeEnum::WITHDRAW) {
            $withdraw = Withdraw::query()->with('fiatDetail')->where('uuid', $transaction->uuid)->first();

            if ($withdraw !== null) {
                return $withdraw->fiatDetail?->bank_name
                    ? __('activity/feed.channel.bank', ['bank' => $withdraw->fiatDetail->bank_name])
                    : __('activity/feed.channel.crypto', ['currency' => $withdraw->currency->value]);
            }
        }

        if ($transaction->trx_type === TrxTypeEnum::DEPOSIT) {
            $deposit = Deposit::query()->where('uuid', $transaction->uuid)->first();

            if ($deposit !== null) {
                return ($deposit->paymentSource?->source ?? null) === 'fiat'
                    ? __('activity/feed.channel.bank', ['bank' => $deposit->transaction_hash ?: __('livewire_finance_bank_not_specified')])
                    : __('activity/feed.channel.crypto', ['currency' => $deposit->currency->value]);
            }
        }

        return '';
    }

    private function transactionAdminOldStatus(?LogActionTypeEnum $actionType): string
    {
        return match ($actionType) {
            LogActionTypeEnum::APPROVE_TRANSACTION,
            LogActionTypeEnum::REJECT_TRANSACTION => __('activity/feed.status.on_moderation'),
            LogActionTypeEnum::MODERATE_TRANSACTION => __('activity/feed.status.approved_or_rejected'),
            default => '',
        };
    }

    private function transactionAdminNewStatus(?LogActionTypeEnum $actionType, string $channel): string
    {
        $status = match ($actionType) {
            LogActionTypeEnum::APPROVE_TRANSACTION => __('activity/feed.status.approved_feminine'),
            LogActionTypeEnum::REJECT_TRANSACTION => __('activity/feed.status.rejected_feminine'),
            LogActionTypeEnum::MODERATE_TRANSACTION => __('activity/feed.status.on_moderation'),
            default => '',
        };

        if ($status === '') {
            return '';
        }

        return $channel === '' ? $status : $status . ' (' . $channel . ')';
    }

    private function formatAmount(string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function formatDisplayValue(mixed $value): string
    {
        if (is_numeric($value)) {
            return $this->formatAmount((string) $value);
        }

        return (string) $value;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function formatActivityValues(array $values): string
    {
        if ($values === []) {
            return '—';
        }

        return collect($values)
            ->map(fn (mixed $value, string|int $key): string => $this->formatActivityKey($key) . ': ' . $this->formatActivityValue($value))
            ->implode("\n");
    }

    private function formatActivityKey(string|int $key): string
    {
        return match ($key) {
            'effective_from' => 'Дата',
            'rate' => 'Курс',
            default => (string) $key,
        };
    }

    private function formatActivityValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Да' : 'Нет';
        }

        if (is_numeric($value)) {
            return $this->formatAmount((string) $value);
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    private function activityAdminLogin(BusinessActivity $activity): string
    {
        $causer = $activity->causer;

        foreach (['username', 'login', 'email', 'name'] as $attribute) {
            $value = data_get($causer, $attribute);

            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return (string) ($activity->getExtraProperty('admin_login') ?? '');
    }
}
