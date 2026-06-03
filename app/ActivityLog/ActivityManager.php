<?php

declare(strict_types=1);

namespace App\ActivityLog;

use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\LogActionTypeEnum;
use App\Models\Deposit;
use App\Models\Withdraw;
use Spatie\Activitylog\Contracts\Activity;

final class ActivityManager
{
    public function resolve(Activity $activity): string
    {
        $activityEventType = ActivityEventTypeEnum::tryFrom($activity->description);

        if ($activityEventType !== null) {
            return $this->resolveBusinessEvent($activity, $activityEventType);
        }

        $strategies = config('activity.strategies');
        $strategyClass = $strategies[$activity->description] ?? null;

        if ($strategyClass && class_exists($strategyClass)) {
            /** @var \App\Contracts\ActivityStrategyContract $strategy */
            $strategy = app($strategyClass);

            return $strategy->handle($activity);
        }

        $adminAction = LogActionTypeEnum::tryFrom($activity->description);

        if ($adminAction !== null) {
            if ($adminAction === LogActionTypeEnum::UPDATE_ITC_PACKAGE_AMOUNT) {
                $packageUuid = (string) $activity->getExtraProperty('package_uuid', '');
                $oldAmount = (string) collect((array) $activity->getExtraProperty('old_values', []))->get('amount', '0');
                $newAmount = (string) collect((array) $activity->getExtraProperty('new_values', []))->get('amount', '0');

                if ($packageUuid !== '') {
                    return __('activity/admin.admin_package_changed_amount_user_feed', [
                        'uuid' => $packageUuid,
                        'old_amount' => $this->formatAmount($oldAmount),
                        'amount' => $this->formatAmount($newAmount),
                    ]);
                }
            }

            return $adminAction->label();
        }

        return __('activity/feed.unknown_event');
    }

    private function resolveBusinessEvent(Activity $activity, ActivityEventTypeEnum $type): string
    {
        $amount = $this->formatAmount($activity->getExtraProperty('amount'));
        $currency = (string) ($activity->getExtraProperty('currency') ?? 'ITC');
        $financeDetails = $this->resolveFinanceDetails($activity, $currency);
        $packageUuid = (string) ($activity->getExtraProperty('package_uuid')
            ?? $activity->getExtraProperty('uuid')
            ?? '');
        $username = (string) ($activity->getExtraProperty('from_username')
            ?? $activity->getExtraProperty('username')
            ?? $activity->getExtraProperty('to_username')
            ?? '');
        $line = $activity->getExtraProperty('line');
        $profit = $this->formatAmount($activity->getExtraProperty('profit', $activity->getExtraProperty('amount')));
        $stakingTokenAmount = $this->formatAmount(
            $activity->getExtraProperty('token_amount', $activity->getExtraProperty('amount'))
        );
        $stakingPurchaseRate = $this->formatRate($activity->getExtraProperty('purchase_rate'));
        $stakingExchangeRate = $this->formatRate($activity->getExtraProperty('exchange_rate'));
        $oldRank = (string) ($activity->getExtraProperty('old_rank') ?? '');
        $newRank = (string) ($activity->getExtraProperty('new_rank') ?? '');

        return match ($type) {
            ActivityEventTypeEnum::DepositRequested => __('activity/feed.business.deposit_requested', ['details' => $financeDetails, 'amount' => $amount]),
            ActivityEventTypeEnum::DepositApproved => __('activity/feed.business.deposit_approved', ['details' => $financeDetails, 'amount' => $amount]),
            ActivityEventTypeEnum::DepositRejected => __('activity/feed.business.deposit_rejected', ['details' => $financeDetails, 'amount' => $amount]),
            ActivityEventTypeEnum::WithdrawRequested => __('activity/feed.business.withdraw_requested', ['details' => $financeDetails, 'amount' => $amount]),
            ActivityEventTypeEnum::WithdrawApproved => __('activity/feed.business.withdraw_approved', ['details' => $financeDetails, 'amount' => $amount]),
            ActivityEventTypeEnum::WithdrawRejected => __('activity/feed.business.withdraw_rejected', ['details' => $financeDetails, 'amount' => $amount]),
            ActivityEventTypeEnum::PackagePurchased => __('activity/feed.business.package_purchased', ['uuid' => $packageUuid, 'amount' => $amount]),
            ActivityEventTypeEnum::PackageClosed => $activity->getExtraProperty('package_type') === 'staking'
                ? __('activity/feed.business.staking_package_closed', ['uuid' => $packageUuid, 'amount' => $amount])
                : __('activity/feed.business.package_closed', ['uuid' => $packageUuid]),
            ActivityEventTypeEnum::PackageToppedUp => __('activity/feed.business.package_topped_up', ['uuid' => $packageUuid, 'amount' => $amount]),
            ActivityEventTypeEnum::PackageProfitAccrued => __('activity/feed.business.package_profit_accrued', ['uuid' => $packageUuid, 'amount' => $amount]),
            ActivityEventTypeEnum::PackageProfitWithdrawn => __('activity/feed.business.package_profit_withdrawn', ['uuid' => $packageUuid, 'amount' => $amount]),
            ActivityEventTypeEnum::PackageReinvested => __('activity/feed.business.package_reinvested', ['uuid' => $packageUuid, 'amount' => $amount]),
            ActivityEventTypeEnum::PackageAmountWithdrawnToBalance => __('activity/feed.business.package_amount_withdrawn_to_balance', ['uuid' => $packageUuid, 'amount' => $amount]),
            ActivityEventTypeEnum::PackageReinvestWithdrawnToBalance => __('activity/feed.business.package_reinvest_withdrawn_to_balance', ['uuid' => $packageUuid, 'amount' => $amount]),
            ActivityEventTypeEnum::PresentPackageZeroed => __('activity/feed.business.present_package_zeroed', ['uuid' => $packageUuid, 'amount' => $amount]),
            ActivityEventTypeEnum::ReferralAddedToLine => __('activity/feed.business.referral_added_to_line', ['line' => $line, 'username' => $username]),
            ActivityEventTypeEnum::BecameReferralOfUser => __('activity/feed.business.became_referral_of_user', ['username' => $username]),
            ActivityEventTypeEnum::PartnerRegularBonusReceived => __('activity/feed.business.partner_regular_bonus_received', ['amount' => $amount, 'username' => $username, 'line' => $line]),
            ActivityEventTypeEnum::PartnerStartBonusReceived => __('activity/feed.business.partner_start_bonus_received', ['amount' => $amount, 'username' => $username, 'line' => $line]),
            ActivityEventTypeEnum::PartnerRankIncreased => __('activity/feed.business.partner_rank_increased', ['old_rank' => $oldRank, 'new_rank' => $newRank]),
            ActivityEventTypeEnum::PartnerRankDecreased => __('activity/feed.business.partner_rank_decreased', ['old_rank' => $oldRank, 'new_rank' => $newRank]),
            ActivityEventTypeEnum::StakingRegularBonusReceived => __('activity/feed.business.staking_regular_bonus_received', ['amount' => $amount, 'username' => $username]),
            ActivityEventTypeEnum::StakingStartBonusReceived => __('activity/feed.business.staking_start_bonus_received', ['amount' => $amount, 'username' => $username]),
            ActivityEventTypeEnum::PartnerToMainTransferred => __('activity/feed.business.partner_to_main_transferred', ['amount' => $amount]),
            ActivityEventTypeEnum::PartnerTransferSent => __('activity/feed.business.partner_transfer_sent', ['amount' => $amount, 'username' => $username]),
            ActivityEventTypeEnum::PartnerTransferReceived => __('activity/feed.business.partner_transfer_received', ['amount' => $amount, 'username' => $username]),
            ActivityEventTypeEnum::RegularBonusTransferredToPartner => __('activity/feed.business.regular_bonus_transferred_to_partner', ['amount' => $amount]),
            ActivityEventTypeEnum::StakingPackagePurchased => __('activity/feed.business.staking_package_purchased', ['uuid' => $packageUuid, 'amount' => $stakingTokenAmount, 'rate' => $stakingPurchaseRate]),
            ActivityEventTypeEnum::StakingPackageToppedUp => __('activity/feed.business.staking_package_topped_up', ['uuid' => $packageUuid, 'amount' => $stakingTokenAmount, 'rate' => $stakingPurchaseRate]),
            ActivityEventTypeEnum::StakingProfitAccrued => __('activity/feed.business.staking_profit_accrued', ['uuid' => $packageUuid, 'profit' => $profit, 'rate' => $stakingExchangeRate]),
            ActivityEventTypeEnum::PromoCodeApplied => __('activity/feed.business.promo_code_applied', [
                'promo_code' => (string) ($activity->getExtraProperty('promo_code') ?? ''),
                'original_threshold' => $this->formatAmount($activity->getExtraProperty('original_threshold', '0')),
                'effective_threshold' => $this->formatAmount($activity->getExtraProperty('effective_threshold', '0')),
            ]),
        };
    }

    private function formatAmount(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function formatRate(mixed $rate): string
    {
        if (! is_numeric($rate)) {
            return '';
        }

        $formattedRate = rtrim(rtrim(number_format((float) $rate, 6, '.', ''), '0'), '.');

        return __('activity/feed.rate_suffix', ['rate' => $formattedRate]);
    }

    private function resolveFinanceDetails(Activity $activity, string $currency): string
    {
        $subject = $activity->subject;
        $paymentSource = (string) ($activity->getExtraProperty('payment_source') ?? '');
        $bankName = (string) ($activity->getExtraProperty('bank_name') ?? '');

        if ($subject instanceof Withdraw) {
            $paymentSource = $paymentSource !== '' ? $paymentSource : (string) ($subject->paymentSource?->source ?? '');
            $bankName = $bankName !== '' ? $bankName : (string) ($subject->fiatDetail?->bank_name ?? '');
        }

        if ($subject instanceof Deposit) {
            $paymentSource = $paymentSource !== '' ? $paymentSource : (string) ($subject->paymentSource?->source ?? '');
            $bankName = $bankName !== '' ? $bankName : (string) $subject->transaction_hash;
        }

        if ($paymentSource === 'fiat') {
            return __('activity/feed.finance.bank_account', ['bank' => $bankName]);
        }

        return __('activity/feed.finance.crypto', ['currency' => $currency]);
    }
}
