<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\BusinessActivity;
use App\Models\Deposit;
use App\Models\ItcPackage;
use App\Models\LogAdminAction;
use App\Models\PackageBalanceWithdraw;
use App\Models\PackagePartnerTransfer;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitReinvestWithdraw;
use App\Models\PackageProfitWithdraw;
use App\Models\PackageZeroing;
use App\Models\Partner;
use App\Models\PartnerClosure;
use App\Models\PartnerReward;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdraw;
use App\Services\ActivityLog\BusinessActivityLogger;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

final class BackfillActivityLogsCommand extends Command
{
    protected $signature = 'activity:backfill-business-logs {--only=}';

    protected $description = 'Backfill legacy journals into activity_log';

    public function handle(BusinessActivityLogger $logger): int
    {
        $only = (string) $this->option('only');

        if ($only === '' || $only === 'finance') {
            $this->backfillFinance($logger);
        }

        if ($only === '' || $only === 'packages') {
            $this->backfillPackages($logger);
        }

        if ($only === '' || $only === 'partners') {
            $this->backfillPartners($logger);
        }

        if ($only === '' || $only === 'admin') {
            $this->backfillAdmin($logger);
        }

        $this->info('Backfill completed.');

        return self::SUCCESS;
    }

    private function backfillFinance(BusinessActivityLogger $logger): void
    {
        Transaction::query()
            ->whereIn('trx_type', [TrxTypeEnum::DEPOSIT, TrxTypeEnum::WITHDRAW])
            ->chunkById(200, function ($transactions) use ($logger): void {
                foreach ($transactions as $transaction) {
                    $subject = Deposit::query()->where('uuid', $transaction->uuid)->first()
                        ?? Withdraw::query()->where('uuid', $transaction->uuid)->first()
                        ?? $transaction;

                    $requestedType = $transaction->trx_type === TrxTypeEnum::DEPOSIT
                        ? ActivityEventTypeEnum::DepositRequested
                        : ActivityEventTypeEnum::WithdrawRequested;

                    $approvedType = $transaction->trx_type === TrxTypeEnum::DEPOSIT
                        ? ActivityEventTypeEnum::DepositApproved
                        : ActivityEventTypeEnum::WithdrawApproved;

                    $rejectedType = $transaction->trx_type === TrxTypeEnum::DEPOSIT
                        ? ActivityEventTypeEnum::DepositRejected
                        : ActivityEventTypeEnum::WithdrawRejected;

                    $currency = $subject->currency->value ?? 'ITC';
                    $paymentSource = $subject->paymentSource?->source ?? null;
                    $bankName = $subject instanceof Withdraw
                        ? $subject->fiatDetail?->bank_name
                        : (($paymentSource === 'fiat') ? $subject->transaction_hash : null);

                    $this->writeIfMissing(
                        logger: $logger,
                        description: $requestedType->value,
                        source: 'transactions',
                        legacyId: $transaction->id,
                        userId: $transaction->user_id,
                        subject: $subject,
                        feeds: [ActivityFeedTypeEnum::Finance, ActivityFeedTypeEnum::UserDetailUser],
                        properties: [
                            'amount' => (string) $transaction->amount,
                            'currency' => $currency,
                            'payment_source' => $paymentSource,
                            'bank_name' => $bankName,
                        ],
                        logName: 'finance',
                        context: 'account',
                        occurredAt: $transaction->created_at,
                    );

                    if ($transaction->accepted_at !== null) {
                        $this->writeIfMissing(
                            logger: $logger,
                            description: $approvedType->value,
                            source: 'transactions',
                            legacyId: "{$transaction->id}:accepted",
                            userId: $transaction->user_id,
                            subject: $subject,
                            feeds: [ActivityFeedTypeEnum::Finance, ActivityFeedTypeEnum::UserDetailUser],
                            properties: [
                                'amount' => (string) $transaction->amount,
                                'currency' => $currency,
                                'payment_source' => $paymentSource,
                                'bank_name' => $bankName,
                            ],
                            logName: 'finance',
                            context: 'admin',
                            occurredAt: $transaction->accepted_at,
                        );
                    }

                    if ($transaction->rejected_at !== null) {
                        $this->writeIfMissing(
                            logger: $logger,
                            description: $rejectedType->value,
                            source: 'transactions',
                            legacyId: "{$transaction->id}:rejected",
                            userId: $transaction->user_id,
                            subject: $subject,
                            feeds: [ActivityFeedTypeEnum::Finance, ActivityFeedTypeEnum::UserDetailUser],
                            properties: [
                                'amount' => (string) $transaction->amount,
                                'currency' => $currency,
                                'payment_source' => $paymentSource,
                                'bank_name' => $bankName,
                            ],
                            logName: 'finance',
                            context: 'admin',
                            occurredAt: $transaction->rejected_at,
                        );
                    }
                }
            });
    }

    private function backfillPackages(BusinessActivityLogger $logger): void
    {
        ItcPackage::query()
            ->with('transaction')
            ->chunkById(200, function ($packages) use ($logger): void {
                foreach ($packages as $package) {
                    if ($package->transaction === null) {
                        continue;
                    }

                    $description = $package->type === PackageTypeEnum::STAKING
                        ? ActivityEventTypeEnum::StakingPackagePurchased->value
                        : ActivityEventTypeEnum::PackagePurchased->value;

                    $feeds = $package->type === PackageTypeEnum::STAKING
                        ? [ActivityFeedTypeEnum::Staking, ActivityFeedTypeEnum::UserDetailUser]
                        : [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser];

                    $this->writeIfMissing(
                        logger: $logger,
                        description: $description,
                        source: 'itc_packages',
                        legacyId: $package->id,
                        userId: $package->transaction->user_id,
                        subject: $package,
                        feeds: $feeds,
                        properties: [
                            'amount' => (string) $package->transaction->amount,
                            'package_uuid' => $package->uuid,
                            'package_type' => $package->type->value,
                        ],
                        logName: 'packages',
                        context: 'system',
                        occurredAt: $package->transaction->accepted_at ?? $package->created_at,
                    );
                }
            });

        PackageProfit::query()->with('package.transaction')->chunkById(200, function ($profits) use ($logger): void {
            foreach ($profits as $profit) {
                $package = $profit->package;

                if ($package === null || $package->transaction === null) {
                    continue;
                }

                $this->writeIfMissing(
                    logger: $logger,
                    description: ActivityEventTypeEnum::PackageProfitAccrued->value,
                    source: 'package_profits',
                    legacyId: $profit->id,
                    userId: $package->transaction->user_id,
                    subject: $package,
                    feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                    properties: [
                        'amount' => (string) $profit->amount,
                        'package_uuid' => $package->uuid,
                    ],
                    logName: 'packages',
                    context: 'system',
                    occurredAt: $profit->created_at,
                );
            }
        });

        PackageProfitReinvest::query()->with('package.transaction')->chunkById(200, function ($reinvests) use ($logger): void {
            foreach ($reinvests as $reinvest) {
                $package = $reinvest->package;

                if ($package === null || $package->transaction === null) {
                    continue;
                }

                $this->writeIfMissing(
                    logger: $logger,
                    description: ActivityEventTypeEnum::PackageReinvested->value,
                    source: 'package_profit_reinvests',
                    legacyId: $reinvest->id,
                    userId: $package->transaction->user_id,
                    subject: $package,
                    feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                    properties: [
                        'amount' => (string) $reinvest->amount,
                        'package_uuid' => $package->uuid,
                    ],
                    logName: 'packages',
                    context: 'system',
                    occurredAt: $reinvest->created_at,
                );
            }
        });

        PackageProfitWithdraw::query()->with(['package.transaction', 'transaction'])->chunkById(200, function ($withdraws) use ($logger): void {
            foreach ($withdraws as $withdraw) {
                $package = $withdraw->package;
                $transaction = $withdraw->transaction;

                if ($package === null || $package->transaction === null || $transaction === null) {
                    continue;
                }

                $this->writeIfMissing(
                    logger: $logger,
                    description: ActivityEventTypeEnum::PackageProfitWithdrawn->value,
                    source: 'package_profit_withdraws',
                    legacyId: $withdraw->id,
                    userId: $package->transaction->user_id,
                    subject: $package,
                    feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                    properties: [
                        'amount' => (string) $transaction->amount,
                        'package_uuid' => $package->uuid,
                    ],
                    logName: 'packages',
                    context: 'system',
                    occurredAt: $transaction->created_at,
                );
            }
        });

        PackageBalanceWithdraw::query()->with(['package.transaction', 'transaction'])->chunkById(200, function ($withdraws) use ($logger): void {
            foreach ($withdraws as $withdraw) {
                $package = $withdraw->package;
                $transaction = $withdraw->transaction;

                if ($package === null || $package->transaction === null || $transaction === null) {
                    continue;
                }

                $this->writeIfMissing(
                    logger: $logger,
                    description: ActivityEventTypeEnum::PackageAmountWithdrawnToBalance->value,
                    source: 'package_balance_withdraws',
                    legacyId: $withdraw->id,
                    userId: $package->transaction->user_id,
                    subject: $package,
                    feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                    properties: [
                        'amount' => (string) $transaction->amount,
                        'package_uuid' => $package->uuid,
                    ],
                    logName: 'packages',
                    context: 'system',
                    occurredAt: $transaction->created_at,
                );
            }
        });

        PackageProfitReinvestWithdraw::query()->with('reinvest.package.transaction')->chunkById(200, function ($withdraws) use ($logger): void {
            foreach ($withdraws as $withdraw) {
                $reinvest = $withdraw->reinvest;
                $package = $reinvest?->package;
                $transaction = Transaction::query()->where('uuid', $withdraw->uuid)->first();

                if ($package === null || $package->transaction === null || $transaction === null) {
                    continue;
                }

                $this->writeIfMissing(
                    logger: $logger,
                    description: ActivityEventTypeEnum::PackageReinvestWithdrawnToBalance->value,
                    source: 'package_profit_reinvest_withdraws',
                    legacyId: $withdraw->id,
                    userId: $package->transaction->user_id,
                    subject: $package,
                    feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                    properties: [
                        'amount' => (string) $transaction->amount,
                        'package_uuid' => $package->uuid,
                    ],
                    logName: 'packages',
                    context: 'system',
                    occurredAt: $transaction->created_at,
                );
            }
        });

        PackagePartnerTransfer::query()->with(['package.transaction', 'transaction'])->chunkById(200, function ($transfers) use ($logger): void {
            foreach ($transfers as $transfer) {
                $package = $transfer->package;
                $transaction = $transfer->transaction;

                if ($package === null || $package->transaction === null || $transaction === null) {
                    continue;
                }

                $this->writeIfMissing(
                    logger: $logger,
                    description: ActivityEventTypeEnum::PackageToppedUp->value,
                    source: 'package_partner_transfers',
                    legacyId: $transfer->id,
                    userId: $package->transaction->user_id,
                    subject: $package,
                    feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                    properties: [
                        'amount' => (string) $transaction->amount,
                        'package_uuid' => $package->uuid,
                        'source_balance' => 'partner',
                    ],
                    logName: 'packages',
                    context: 'system',
                    occurredAt: $transaction->created_at,
                );
            }
        });

        PackageZeroing::query()->with(['package.transaction', 'transaction'])->chunkById(200, function ($zeroings) use ($logger): void {
            foreach ($zeroings as $zeroing) {
                $package = $zeroing->package;
                $transaction = $zeroing->transaction;

                if ($package === null || $transaction === null || $package->transaction === null) {
                    continue;
                }

                $this->writeIfMissing(
                    logger: $logger,
                    description: ActivityEventTypeEnum::PresentPackageZeroed->value,
                    source: 'package_zeroings',
                    legacyId: $zeroing->id,
                    userId: $package->transaction->user_id,
                    subject: $package,
                    feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                    properties: [
                        'amount' => (string) abs((float) $transaction->amount),
                        'package_uuid' => $package->uuid,
                        'transaction_uuid' => $transaction->uuid,
                    ],
                    logName: 'packages',
                    context: 'system',
                    occurredAt: $transaction->accepted_at ?? $transaction->created_at,
                );
            }
        });
    }

    private function backfillPartners(BusinessActivityLogger $logger): void
    {
        Transaction::query()
            ->where('trx_type', TrxTypeEnum::PARTNER_TO_MAIN_SELF)
            ->chunkById(200, function ($transactions) use ($logger): void {
                foreach ($transactions as $transaction) {
                    $user = User::query()->find($transaction->user_id);

                    if ($user === null) {
                        continue;
                    }

                    $this->writeIfMissing(
                        logger: $logger,
                        description: ActivityEventTypeEnum::PartnerToMainTransferred->value,
                        source: 'transactions',
                        legacyId: $transaction->id,
                        userId: $transaction->user_id,
                        subject: $user,
                        feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                        properties: [
                            'amount' => (string) $transaction->amount,
                            'transaction_uuid' => $transaction->uuid,
                        ],
                        logName: 'partners',
                        context: 'account',
                        occurredAt: $transaction->accepted_at ?? $transaction->created_at,
                    );
                }
            });

        Transaction::query()
            ->where('trx_type', TrxTypeEnum::PARTNER_TRANSFER_OUT)
            ->chunkById(200, function ($transactions) use ($logger): void {
                foreach ($transactions as $transaction) {
                    $baseUuid = str_ends_with($transaction->uuid, '-O')
                        ? substr($transaction->uuid, 0, -2)
                        : $transaction->uuid;

                    $incoming = Transaction::query()
                        ->where('uuid', $baseUuid)
                        ->where('trx_type', TrxTypeEnum::PARTNER_TRANSFER_IN)
                        ->first();

                    $sender = User::query()->find($transaction->user_id);
                    $receiver = $incoming === null
                        ? null
                        : User::query()->find($incoming->user_id);

                    if ($sender === null || $receiver === null) {
                        continue;
                    }

                    $this->writeIfMissing(
                        logger: $logger,
                        description: ActivityEventTypeEnum::PartnerTransferSent->value,
                        source: 'transactions',
                        legacyId: "{$transaction->id}:sent",
                        userId: $sender->id,
                        subject: $receiver,
                        feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                        properties: [
                            'amount' => (string) $transaction->amount,
                            'username' => $receiver->username,
                            'transaction_uuid' => $baseUuid,
                        ],
                        logName: 'partners',
                        context: 'account',
                        occurredAt: $transaction->created_at,
                    );

                    $this->writeIfMissing(
                        logger: $logger,
                        description: ActivityEventTypeEnum::PartnerTransferReceived->value,
                        source: 'transactions',
                        legacyId: "{$transaction->id}:received",
                        userId: $receiver->id,
                        subject: $sender,
                        feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                        properties: [
                            'amount' => (string) $incoming->amount,
                            'username' => $sender->username,
                            'transaction_uuid' => $baseUuid,
                        ],
                        logName: 'partners',
                        context: 'account',
                        occurredAt: $incoming->accepted_at ?? $incoming->created_at,
                    );
                }
            });

        Transaction::query()
            ->where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_TO_PARTNER)
            ->chunkById(200, function ($transactions) use ($logger): void {
                foreach ($transactions as $transaction) {
                    $user = User::query()->find($transaction->user_id);

                    if ($user === null) {
                        continue;
                    }

                    $this->writeIfMissing(
                        logger: $logger,
                        description: ActivityEventTypeEnum::RegularBonusTransferredToPartner->value,
                        source: 'transactions',
                        legacyId: $transaction->id,
                        userId: $transaction->user_id,
                        subject: $user,
                        feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                        properties: [
                            'amount' => (string) $transaction->amount,
                            'transaction_uuid' => $transaction->uuid,
                        ],
                        logName: 'partners',
                        context: 'account',
                        occurredAt: $transaction->accepted_at ?? $transaction->created_at,
                    );
                }
            });

        PartnerReward::query()->with(['transaction', 'from'])->chunkById(200, function ($rewards) use ($logger): void {
            foreach ($rewards as $reward) {
                $transaction = $reward->transaction;
                $from = $reward->from;

                if ($transaction === null || $from === null) {
                    continue;
                }

                $type = match ($reward->reward_type) {
                    PartnerRewardTypeEnum::START => ActivityEventTypeEnum::PartnerStartBonusReceived,
                    PartnerRewardTypeEnum::REGULAR => ActivityEventTypeEnum::PartnerRegularBonusReceived,
                    PartnerRewardTypeEnum::STAKING_START => ActivityEventTypeEnum::StakingStartBonusReceived,
                    PartnerRewardTypeEnum::STAKING_REGULAR => ActivityEventTypeEnum::StakingRegularBonusReceived,
                };

                $this->writeIfMissing(
                    logger: $logger,
                    description: $type->value,
                    source: 'partner_rewards',
                    legacyId: $reward->id,
                    userId: $transaction->user_id,
                    subject: $from,
                    feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                    properties: [
                        'amount' => (string) $reward->amount,
                        'line' => $reward->line,
                        'from_username' => $from->username,
                    ],
                    logName: 'partners',
                    context: 'system',
                    occurredAt: $reward->created_at,
                );
            }
        });

        Partner::query()->with(['user', 'referrer'])->chunkById(200, function ($partners) use ($logger): void {
            foreach ($partners as $partner) {
                if ($partner->user === null || $partner->referrer === null) {
                    continue;
                }

                $this->writeIfMissing(
                    logger: $logger,
                    description: ActivityEventTypeEnum::BecameReferralOfUser->value,
                    source: 'partners',
                    legacyId: $partner->id,
                    userId: $partner->user_id,
                    subject: $partner->user,
                    feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                    properties: [
                        'username' => $partner->referrer->username,
                        'line' => 1,
                    ],
                    logName: 'partners',
                    context: 'system',
                    occurredAt: $partner->created_at,
                );

                PartnerClosure::query()
                    ->where('descendant_id', $partner->user_id)
                    ->where('depth', '>', 0)
                    ->orderBy('depth')
                    ->get(['ancestor_id', 'depth'])
                    ->each(function ($ancestor) use ($logger, $partner): void {
                        $this->writeIfMissing(
                            logger: $logger,
                            description: ActivityEventTypeEnum::ReferralAddedToLine->value,
                            source: 'partners',
                            legacyId: "{$partner->id}:ancestor:{$ancestor->ancestor_id}",
                            userId: (int) $ancestor->ancestor_id,
                            subject: $partner->user,
                            feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                            properties: [
                                'username' => $partner->user->username,
                                'line' => (int) $ancestor->depth,
                            ],
                            logName: 'partners',
                            context: 'system',
                            occurredAt: $partner->created_at,
                        );
                    });
            }
        });
    }

    private function backfillAdmin(BusinessActivityLogger $logger): void
    {
        LogAdminAction::query()->chunkById(200, function ($logs) use ($logger): void {
            foreach ($logs as $log) {
                if ($log->target_user_id === null) {
                    continue;
                }

                $subject = $this->resolveSubject($log->model_type, (int) $log->model_id)
                    ?? User::query()->find($log->target_user_id);

                if ($subject === null) {
                    continue;
                }

                $this->writeIfMissing(
                    logger: $logger,
                    description: $log->action_type,
                    source: 'log_admin_actions',
                    legacyId: $log->id,
                    userId: (int) $log->target_user_id,
                    subject: $subject,
                    feeds: [ActivityFeedTypeEnum::UserDetailAdmin],
                    properties: [
                        'old_values' => (array) $log->old_values,
                        'new_values' => (array) $log->new_values,
                        'model_type' => $log->model_type,
                        'model_id' => $log->model_id,
                    ],
                    logName: 'admin',
                    context: 'admin',
                    occurredAt: $log->created_at,
                );
            }
        });
    }

    /**
     * @param array<int, \App\Enums\Activity\ActivityFeedTypeEnum|string> $feeds
     * @param array<string, mixed> $properties
     */
    private function writeIfMissing(
        BusinessActivityLogger $logger,
        string $description,
        string $source,
        int|string $legacyId,
        int|string $userId,
        Model $subject,
        array $feeds,
        array $properties,
        string $logName,
        string $context,
        ?CarbonInterface $occurredAt,
    ): void {
        if (filter_var($userId, FILTER_VALIDATE_INT) === false) {
            return;
        }

        $userId = (int) $userId;

        $exists = BusinessActivity::query()
            ->where('description', $description)
            ->where('user_id', $userId)
            ->where('properties->legacy_source', $source)
            ->where('properties->legacy_id', (string) $legacyId)
            ->exists();

        if ($exists) {
            return;
        }

        $logger->writeDescription(
            description: $description,
            userId: $userId,
            subject: $subject,
            feeds: $feeds,
            properties: $properties + [
                'legacy_source' => $source,
                'legacy_id' => (string) $legacyId,
            ],
            logName: $logName,
            context: $context,
            occurredAt: $occurredAt,
        );
    }

    private function resolveSubject(?string $modelType, int $modelId): ?Model
    {
        if ($modelType === null || ! class_exists($modelType)) {
            return null;
        }

        $model = new $modelType();

        if (! $model instanceof Model) {
            return null;
        }

        return $model->newQuery()->find($modelId);
    }
}
