<?php

declare(strict_types=1);

namespace App\Services\ActivityLog;

use App\ActivityLog\ActivityManager;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\LogActionTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\BusinessActivity;
use App\Models\Deposit;
use App\Models\ItcPackage;
use App\Models\Transaction;
use App\Models\Withdraw;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ActivityFeedService
{
    public function __construct(
        private readonly ActivityManager $activityManager,
    ) {}

    /**
     * @return \Illuminate\Support\Collection<int, array{date:string,event:string}>
     */
    public function packageFeed(int $userId, int $limit = 200): Collection
    {
        return $this->baseFeedQuery($userId, ActivityFeedTypeEnum::Packages, $limit)
            ->get()
            ->map(fn (BusinessActivity $activity): array => [
                'date' => $activity->created_at?->format('d.m.Y, H:i') ?? '',
                'event' => $this->activityManager->resolve($activity),
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{date:string,user:string,level:string|int,event:string}>
     */
    public function partnerFeed(int $userId, int $limit = 200): Collection
    {
        return $this->baseFeedQuery($userId, ActivityFeedTypeEnum::Partners, $limit)
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
     * @return \Illuminate\Support\Collection<int, array{created_at:mixed,amount:mixed,arrow:string,type:string,status:string}>
     */
    public function financeFeed(int $userId, int $limit = 200): Collection
    {
        return $this->baseFeedQuery($userId, ActivityFeedTypeEnum::Finance, $limit)
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
     * @return array<int, array{action:string,type:string,operation_amount:string,from_user:string,date:string}>
     */
    public function userDetailUserFeed(
        int $userId,
        ?int $limit = 200,
        ?CarbonInterface $dateFrom = null,
        ?CarbonInterface $dateTo = null,
    ): array {
        return $this->userDetailBaseQuery($userId, ActivityFeedTypeEnum::UserDetailUser, $limit)
            ->when($dateFrom !== null, fn (Builder $query) => $query->where('created_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn (Builder $query) => $query->where('created_at', '<=', $dateTo))
            ->get()
            ->map(function (BusinessActivity $activity): array {
                $amount = $activity->getExtraProperty('amount');

                return [
                    'action' => $this->userDetailAction($activity),
                    'type' => $this->activityManager->resolve($activity),
                    'operation_amount' => $amount === null ? '' : $this->formatAmount((string) $amount),
                    'from_user' => (string) ($activity->getExtraProperty('from_username')
                    ?? $activity->getExtraProperty('username')
                    ?? $activity->getExtraProperty('to_username')
                    ?? ''),
                    'date' => $activity->created_at?->format('d.m.Y H:i') ?? '',
                ];
            })
            ->toArray();
    }

    /**
     * @return array<int, array{action:string,old_values:string,new_values:string,operation_amount:string,date:string}>
     */
    public function userDetailAdminFeed(int $userId, int $limit = 200): array
    {
        return $this->userDetailBaseQuery($userId, ActivityFeedTypeEnum::UserDetailAdmin, $limit)
            ->where('description', '!=', LogActionTypeEnum::UPDATE_ITC_PACKAGE_AMOUNT->value)
            ->where('description', '!=', LogActionTypeEnum::WITHDRAW_PACKAGE_REINVEST_PROFIT->value)
            ->get()
            ->map(function (BusinessActivity $activity): array {
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
            })
            ->toArray();
    }

    /**
     * @return array<int, array{action:string,admin_login:string,old_values:string,new_values:string,date:string}>
     */
    public function globalAdminFeed(?int $limit = 200): array
    {
        $query = BusinessActivity::query()
            ->forFeed(ActivityFeedTypeEnum::GlobalAdmin)
            ->with('causer')
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

    private function baseFeedQuery(int $userId, ActivityFeedTypeEnum $feed, ?int $limit): Builder
    {
        $query = BusinessActivity::query()
            ->forUser($userId)
            ->forFeed($feed)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return $limit === null ? $query : $query->limit($limit);
    }

    private function userDetailBaseQuery(int $userId, ActivityFeedTypeEnum $feed, ?int $limit): Builder
    {
        return $this->baseFeedQuery($userId, $feed, $limit)
            ->where(function (Builder $query): void {
                $query
                    ->where('properties->package_type', '!=', PackageTypeEnum::STAKING->value)
                    ->orWhereNull('properties->package_type');
            })
            ->whereNotIn('description', [
                ActivityEventTypeEnum::StakingPackagePurchased->value,
                ActivityEventTypeEnum::StakingPackageToppedUp->value,
                ActivityEventTypeEnum::StakingProfitAccrued->value,
                ActivityEventTypeEnum::StakingRegularBonusReceived->value,
                ActivityEventTypeEnum::StakingStartBonusReceived->value,
            ])
            ->where(function (Builder $query): void {
                $query
                    ->where('subject_type', '!=', ItcPackage::class)
                    ->orWhereNotIn('description', [
                        'top_up_package',
                        'profit_accrued',
                        'start_bonus_package',
                        'regular_premium_package',
                        'admin_package_purchased',
                        'admin_package_changed_amount',
                        'admin_package_changed_percentage',
                        'admin_package_added_manual_profit',
                        'admin_package_staking_changed_percentage',
                    ]);
            });
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
        ];
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
