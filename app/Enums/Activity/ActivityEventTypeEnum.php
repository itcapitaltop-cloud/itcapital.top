<?php

declare(strict_types=1);

namespace App\Enums\Activity;

enum ActivityEventTypeEnum: string
{
    case DepositRequested = 'deposit_requested';
    case DepositApproved = 'deposit_approved';
    case DepositRejected = 'deposit_rejected';

    case WithdrawRequested = 'withdraw_requested';
    case WithdrawApproved = 'withdraw_approved';
    case WithdrawRejected = 'withdraw_rejected';

    case PackagePurchased = 'package_purchased';
    case PackageClosed = 'package_closed';
    case PackageToppedUp = 'package_topped_up';
    case PackageProfitAccrued = 'package_profit_accrued';
    case PackageProfitWithdrawn = 'package_profit_withdrawn';
    case PackageReinvested = 'package_reinvested';
    case PackageAmountWithdrawnToBalance = 'package_amount_withdrawn_to_balance';
    case PackageReinvestWithdrawnToBalance = 'package_reinvest_withdrawn_to_balance';
    case PresentPackageZeroed = 'present_package_zeroed';

    case ReferralAddedToLine = 'referral_added_to_line';
    case BecameReferralOfUser = 'became_referral_of_user';
    case PartnerRegularBonusReceived = 'partner_regular_bonus_received';
    case PartnerStartBonusReceived = 'partner_start_bonus_received';
    case PartnerRankIncreased = 'partner_rank_increased';
    case StakingRegularBonusReceived = 'staking_regular_bonus_received';
    case StakingStartBonusReceived = 'staking_start_bonus_received';
    case PartnerToMainTransferred = 'partner_to_main_transferred';
    case PartnerTransferSent = 'partner_transfer_sent';
    case PartnerTransferReceived = 'partner_transfer_received';
    case RegularBonusTransferredToPartner = 'regular_bonus_transferred_to_partner';

    case StakingPackagePurchased = 'staking_package_purchased';
    case StakingPackageToppedUp = 'staking_package_topped_up';
    case StakingProfitAccrued = 'staking_profit_accrued';

    case PromoCodeApplied = 'promo_code_applied';
}
