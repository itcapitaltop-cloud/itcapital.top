<?php

declare(strict_types=1);

namespace App\Actions\PromoCodes;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\Package\PackageDefinition;
use App\Models\PromoCode;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LeMaX10\SimpleActions\Action;
use MoonShine\Permissions\Models\MoonshineUser;
use Throwable;

final class GeneratePromoCodeAction extends Action
{
    private const MAX_GENERATION_ATTEMPTS = 5;

    protected function handle(PackageDefinition $packageDefinition, string $reducedMinimumAmount, ?MoonshineUser $admin = null): PromoCode
    {
        Log::debug('[GeneratePromoCodeAction.handle] start', [
            'admin_id' => $admin?->id,
            'package_definition_id' => $packageDefinition->id,
            'package_slug' => $packageDefinition->slug,
            'reduced_minimum_amount' => $reducedMinimumAmount,
        ]);

        $this->assertPackageDefinitionCanUsePromoCodes($packageDefinition);
        $this->assertReducedMinimumAmount($reducedMinimumAmount, $packageDefinition->min_start_amount);

        try {
            $promoCode = PromoCode::query()->create([
                'code' => $this->generateUniqueCode(),
                'package_definition_id' => $packageDefinition->id,
                'reduced_minimum_amount' => $reducedMinimumAmount,
                'created_by_admin_id' => $admin?->id,
            ]);
        } catch (Throwable $throwable) {
            Log::error('[GeneratePromoCodeAction.handle] failed to persist promo code', [
                'admin_id' => $admin?->id,
                'package_definition_id' => $packageDefinition->id,
                'package_slug' => $packageDefinition->slug,
                'reduced_minimum_amount' => $reducedMinimumAmount,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }

        return $promoCode;
    }

    private function assertPackageDefinitionCanUsePromoCodes(PackageDefinition $packageDefinition): void
    {
        if (in_array($packageDefinition->type, [PackageTypeEnum::ARCHIVE, PackageTypeEnum::STAKING], true)) {
            Log::warning('[GeneratePromoCodeAction.assertPackageDefinitionCanUsePromoCodes] unsupported package definition', [
                'package_definition_id' => $packageDefinition->id,
                'package_slug' => $packageDefinition->slug,
                'package_type' => $packageDefinition->type->value,
            ]);

            throw new InvalidArgumentException('Promo codes are not supported for this package.');
        }
    }

    private function assertReducedMinimumAmount(string $reducedMinimumAmount, string $defaultMinimumAmount): void
    {
        $amount = BigDecimal::of($reducedMinimumAmount);
        $defaultMinimum = BigDecimal::of($defaultMinimumAmount);

        if ($amount->isLessThan(0) || $amount->isGreaterThanOrEqualTo($defaultMinimum)) {
            Log::warning('[GeneratePromoCodeAction.assertReducedMinimumAmount] invalid reduced threshold', [
                'reduced_minimum_amount' => $reducedMinimumAmount,
                'default_minimum' => $defaultMinimumAmount,
            ]);

            throw new InvalidArgumentException('Reduced minimum amount must be lower than the default package minimum.');
        }
    }

    private function generateUniqueCode(): string
    {
        for ($attempt = 1; $attempt <= self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $code = 'ITC-' . Str::upper(Str::random(8));

            if (! PromoCode::query()->where('code', $code)->exists()) {
                if ($attempt > 1) {
                    Log::warning('[GeneratePromoCodeAction.generateUniqueCode] generated after collision retry', [
                        'attempt' => $attempt,
                    ]);
                }

                return $code;
            }

            Log::warning('[GeneratePromoCodeAction.generateUniqueCode] promo code collision', [
                'attempt' => $attempt,
            ]);
        }

        throw new InvalidArgumentException('Unable to generate a unique promo code.');
    }
}
