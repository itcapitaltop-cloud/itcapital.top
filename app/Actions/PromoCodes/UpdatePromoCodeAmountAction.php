<?php

declare(strict_types=1);

namespace App\Actions\PromoCodes;

use App\Models\PromoCode;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use InvalidArgumentException;
use LeMaX10\SimpleActions\Action;
use RuntimeException;
use Throwable;

final class UpdatePromoCodeAmountAction extends Action
{
    /**
     * Update the reduced purchase threshold of a promo code.
     *
     * Editing is allowed even when the promo code has already been used: the new
     * threshold only affects future redemptions, existing usages are untouched.
     *
     * @param PromoCode $promoCode
     * @param string $reducedMinimumAmount
     * @return PromoCode
     *
     * @throws Throwable
     */
    protected function handle(PromoCode $promoCode, string $reducedMinimumAmount): PromoCode
    {
        $packageDefinition = $promoCode->packageDefinition;

        if ($packageDefinition === null) {
            throw new RuntimeException('Promo code has no associated package definition.');
        }

        $this->assertReducedMinimumAmount($reducedMinimumAmount, $packageDefinition->min_start_amount);

        $promoCode->reduced_minimum_amount = $reducedMinimumAmount;
        $promoCode->save();

        return $promoCode->refresh();
    }

    /**
     * @param string $reducedMinimumAmount
     * @param string $defaultMinimumAmount
     * @return void
     *
     * @throws DivisionByZeroException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    private function assertReducedMinimumAmount(string $reducedMinimumAmount, string $defaultMinimumAmount): void
    {
        $amount = BigDecimal::of($reducedMinimumAmount);
        $defaultMinimum = BigDecimal::of($defaultMinimumAmount);

        if ($amount->isLessThan(0) || $amount->isGreaterThanOrEqualTo($defaultMinimum)) {
            throw new InvalidArgumentException('Reduced minimum amount must be lower than the default package minimum.');
        }
    }
}
