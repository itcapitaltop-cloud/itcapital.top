<?php

declare(strict_types=1);

namespace App\Services\PromoCodes;

use App\Dto\PromoCodes\PackagePromoCodeValidationResult;
use App\Enums\Itc\PackageTypeEnum;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

final class PackagePromoCodeService
{
    public const ERROR_INVALID = 'invalid';

    public const ERROR_USED = 'used';

    public const ERROR_PACKAGE_TYPE_MISMATCH = 'package_type_mismatch';

    public const ERROR_AMOUNT_BELOW_THRESHOLD = 'amount_below_threshold';

    public const ERROR_UNSUPPORTED_PACKAGE_TYPE = 'unsupported_package_type';

    private const DEFAULT_PACKAGE_MINIMUM = '100.00000000';

    public function validateForPurchase(
        User $user,
        PackageTypeEnum $packageType,
        string $amount,
        ?string $code,
        string $defaultMinimumAmount = self::DEFAULT_PACKAGE_MINIMUM,
    ): PackagePromoCodeValidationResult {
        $normalizedCode = $this->normalizeCode($code);

        Log::debug('[PackagePromoCodeService.validateForPurchase] start', [
            'user_id' => $user->id,
            'package_type' => $packageType->value,
            'amount' => $amount,
            'has_promo_code' => $normalizedCode !== null,
        ]);

        if (! $this->supportsPackageType($packageType)) {
            Log::warning('[PackagePromoCodeService.validateForPurchase] unsupported package type', [
                'user_id' => $user->id,
                'package_type' => $packageType->value,
            ]);

            return new PackagePromoCodeValidationResult(
                promoCode: null,
                effectiveMinimumAmount: $defaultMinimumAmount,
                errorCode: self::ERROR_UNSUPPORTED_PACKAGE_TYPE,
            );
        }

        if ($normalizedCode === null) {
            return $this->validateAmountThreshold(
                user: $user,
                packageType: $packageType,
                amount: $amount,
                effectiveMinimumAmount: $defaultMinimumAmount,
                promoCode: null,
            );
        }

        $promoCode = PromoCode::query()
            ->where('code', $normalizedCode)
            ->first();

        if ($promoCode === null) {
            Log::warning('[PackagePromoCodeService.validateForPurchase] invalid promo code', [
                'user_id' => $user->id,
                'package_type' => $packageType->value,
            ]);

            return new PackagePromoCodeValidationResult(
                promoCode: null,
                effectiveMinimumAmount: $defaultMinimumAmount,
                errorCode: self::ERROR_INVALID,
            );
        }

        if ($promoCode->isUsedByUser($user, $packageType)) {
            Log::warning('[PackagePromoCodeService.validateForPurchase] promo code already used by user for this package', [
                'user_id' => $user->id,
                'promo_code_id' => $promoCode->id,
                'package_type' => $packageType->value,
            ]);

            return new PackagePromoCodeValidationResult(
                promoCode: $promoCode,
                effectiveMinimumAmount: $defaultMinimumAmount,
                errorCode: self::ERROR_USED,
            );
        }

        if ($promoCode->package_type !== $packageType) {
            Log::warning('[PackagePromoCodeService.validateForPurchase] package type mismatch', [
                'user_id' => $user->id,
                'promo_code_id' => $promoCode->id,
                'expected_package_type' => $promoCode->package_type->value,
                'actual_package_type' => $packageType->value,
            ]);

            return new PackagePromoCodeValidationResult(
                promoCode: $promoCode,
                effectiveMinimumAmount: $defaultMinimumAmount,
                errorCode: self::ERROR_PACKAGE_TYPE_MISMATCH,
            );
        }

        return $this->validateAmountThreshold(
            user: $user,
            packageType: $packageType,
            amount: $amount,
            effectiveMinimumAmount: $promoCode->reduced_minimum_amount,
            promoCode: $promoCode,
        );
    }

    public function redeem(
        PromoCode $promoCode,
        User $user,
        PackageTypeEnum $packageType,
        string $defaultMinimumAmount = self::DEFAULT_PACKAGE_MINIMUM,
    ): PromoCodeUsage {
        Log::debug('[PackagePromoCodeService.redeem] start', [
            'promo_code_id' => $promoCode->id,
            'user_id' => $user->id,
            'package_type' => $packageType->value,
        ]);

        $usage = DB::transaction(function () use ($promoCode, $user, $packageType, $defaultMinimumAmount) {
            $lockedPromoCode = PromoCode::query()
                ->whereKey($promoCode->id)
                ->lockForUpdate()
                ->first();

            if ($lockedPromoCode === null) {
                Log::error('[PackagePromoCodeService.redeem] promo code disappeared during redemption', [
                    'promo_code_id' => $promoCode->id,
                    'user_id' => $user->id,
                ]);

                throw new RuntimeException('Promo code was not found during redemption.');
            }

            if ($lockedPromoCode->isUsedByUser($user, $packageType)) {
                Log::error('[PackagePromoCodeService.redeem] promo code already redeemed by user for this package', [
                    'promo_code_id' => $lockedPromoCode->id,
                    'user_id' => $user->id,
                    'package_type' => $packageType->value,
                ]);

                throw new RuntimeException('Promo code has already been used by this user for this package type.');
            }

            $usage = PromoCodeUsage::create([
                'promo_code_id' => $lockedPromoCode->id,
                'user_id' => $user->id,
                'package_type' => $packageType,
                'used_at' => now(),
            ]);

            Log::info('[PackagePromoCodeService.redeem] promo code redeemed', [
                'promo_code_id' => $lockedPromoCode->id,
                'user_id' => $user->id,
                'package_type' => $packageType->value,
                'usage_id' => $usage->id,
                'original_threshold' => $defaultMinimumAmount,
                'effective_threshold' => $lockedPromoCode->reduced_minimum_amount,
            ]);

            return $usage;
        });

        return $usage;
    }

    private function validateAmountThreshold(
        User $user,
        PackageTypeEnum $packageType,
        string $amount,
        string $effectiveMinimumAmount,
        ?PromoCode $promoCode,
    ): PackagePromoCodeValidationResult {
        if (BigDecimal::of($amount)->isLessThan(BigDecimal::of($effectiveMinimumAmount))) {
            Log::warning('[PackagePromoCodeService.validateAmountThreshold] amount below threshold', [
                'user_id' => $user->id,
                'promo_code_id' => $promoCode?->id,
                'package_type' => $packageType->value,
                'amount' => $amount,
                'effective_minimum_amount' => $effectiveMinimumAmount,
            ]);

            return new PackagePromoCodeValidationResult(
                promoCode: $promoCode,
                effectiveMinimumAmount: $effectiveMinimumAmount,
                errorCode: self::ERROR_AMOUNT_BELOW_THRESHOLD,
                context: ['effective_minimum_amount' => $effectiveMinimumAmount],
            );
        }

        Log::debug('[PackagePromoCodeService.validateAmountThreshold] validation passed', [
            'user_id' => $user->id,
            'promo_code_id' => $promoCode?->id,
            'package_type' => $packageType->value,
            'amount' => $amount,
            'effective_minimum_amount' => $effectiveMinimumAmount,
        ]);

        return new PackagePromoCodeValidationResult(
            promoCode: $promoCode,
            effectiveMinimumAmount: $effectiveMinimumAmount,
        );
    }

    private function normalizeCode(?string $code): ?string
    {
        $normalizedCode = Str::upper(trim((string) $code));

        return $normalizedCode === '' ? null : $normalizedCode;
    }

    private function supportsPackageType(PackageTypeEnum $packageType): bool
    {
        return ! in_array($packageType, [PackageTypeEnum::ARCHIVE, PackageTypeEnum::STAKING], true);
    }
}
