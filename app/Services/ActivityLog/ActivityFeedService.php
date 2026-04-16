<?php

declare(strict_types=1);

namespace App\Services\ActivityLog;

use App\ActivityLog\ActivityManager;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\LogActionTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\BusinessActivity;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Withdraw;
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
    public function userDetailUserFeed(int $userId, int $limit = 200): array
    {
        return $this->baseFeedQuery($userId, ActivityFeedTypeEnum::UserDetailUser, $limit)
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
        return $this->baseFeedQuery($userId, ActivityFeedTypeEnum::UserDetailAdmin, $limit)
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
                    'old_values' => $oldValues->map(static fn (mixed $value): string => (string) $value)->implode("\n"),
                    'new_values' => $newValues->map(static fn (mixed $value): string => (string) $value)->implode("\n"),
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

    private function baseFeedQuery(int $userId, ActivityFeedTypeEnum $feed, int $limit): Builder
    {
        return BusinessActivity::query()
            ->forUser($userId)
            ->forFeed($feed)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit);
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
            ActivityEventTypeEnum::WithdrawRequested->value => 'На модерации',
            ActivityEventTypeEnum::DepositApproved->value,
            ActivityEventTypeEnum::WithdrawApproved->value => 'Одобрено',
            ActivityEventTypeEnum::DepositRejected->value,
            ActivityEventTypeEnum::WithdrawRejected->value => 'Отклонено',
            default => '—',
        };
    }

    private function userDetailAction(BusinessActivity $activity): string
    {
        if (in_array($activity->description, $this->neutralUserDetailEvents(), true)) {
            return 'Действие';
        }

        return in_array($activity->description, $this->positiveUserDetailEvents(), true)
            ? 'Увеличение баланса'
            : 'Уменьшение баланса';
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
            $transactionActionLabel = $this->transactionAdminActionLabel($activity, $actionType);

            if ($transactionActionLabel !== null) {
                return $transactionActionLabel;
            }

            if ($actionType === LogActionTypeEnum::CLOSE_ITC_PACKAGE) {
                $packageUuid = data_get($activity->subject, 'uuid');

                if (is_string($packageUuid) && $packageUuid !== '') {
                    return 'Закрытие пакета ' . $packageUuid;
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
            TrxTypeEnum::DEPOSIT => 'заявки на ввод',
            TrxTypeEnum::WITHDRAW => 'заявки на вывод',
            default => null,
        };

        if ($suffix === null) {
            return null;
        }

        return match ($actionType) {
            LogActionTypeEnum::APPROVE_TRANSACTION => 'Одобрение ' . $suffix,
            LogActionTypeEnum::REJECT_TRANSACTION => 'Отклонение ' . $suffix,
            LogActionTypeEnum::MODERATE_TRANSACTION => 'Перевод ' . $suffix . ' на модерацию',
            default => null,
        };
    }

    private function transactionChannelLabel(Transaction $transaction): string
    {
        if ($transaction->trx_type === TrxTypeEnum::WITHDRAW) {
            $withdraw = Withdraw::query()->with('fiatDetail')->where('uuid', $transaction->uuid)->first();

            if ($withdraw !== null) {
                return $withdraw->fiatDetail?->bank_name
                    ? 'банк: ' . $withdraw->fiatDetail->bank_name
                    : 'крипта: ' . $withdraw->currency->value;
            }
        }

        if ($transaction->trx_type === TrxTypeEnum::DEPOSIT) {
            $deposit = Deposit::query()->where('uuid', $transaction->uuid)->first();

            if ($deposit !== null) {
                return ($deposit->paymentSource?->source ?? null) === 'fiat'
                    ? 'банк: ' . ($deposit->transaction_hash ?: __('livewire_finance_bank_not_specified'))
                    : 'крипта: ' . $deposit->currency->value;
            }
        }

        return '';
    }

    private function transactionAdminOldStatus(?LogActionTypeEnum $actionType): string
    {
        return match ($actionType) {
            LogActionTypeEnum::APPROVE_TRANSACTION,
            LogActionTypeEnum::REJECT_TRANSACTION => 'На модерации',
            LogActionTypeEnum::MODERATE_TRANSACTION => 'Одобрена/Отклонена',
            default => '',
        };
    }

    private function transactionAdminNewStatus(?LogActionTypeEnum $actionType, string $channel): string
    {
        $status = match ($actionType) {
            LogActionTypeEnum::APPROVE_TRANSACTION => 'Одобрена',
            LogActionTypeEnum::REJECT_TRANSACTION => 'Отклонена',
            LogActionTypeEnum::MODERATE_TRANSACTION => 'На модерации',
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
}
