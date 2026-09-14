<?php

declare(strict_types=1);

namespace App\Actions\Packages;

use App\Dto\Activity\WriteBusinessActivityData;
use App\Dto\Packages\UnlockPackageBodyResult;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Exceptions\Domain\InvalidAmountException;
use App\Models\ItcPackage;
use App\Models\PackageBodyUnlock;
use App\Models\User;
use App\Services\ActivityLog\BusinessActivityLogger;
use App\Services\Package\PackageBodyBalanceResolver;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Разблокирует указанную сумму из тела пакета.
 *
 * Сумма сразу покидает базу начисления дивидендов и попадает на основной баланс
 * ровно через календарный месяц силами `packages:payout-unlocked-body-amounts`.
 * Операция необратима для пользователя.
 */
final readonly class UnlockPackageBodyAmountAction
{
    public function __construct(
        private BusinessActivityLogger $activityLogger,
        private PackageBodyBalanceResolver $balanceResolver,
    ) {}

    /**
     * @throws InvalidAmountException when the amount, the package type or the remaining body is invalid
     */
    public function execute(ItcPackage $package, string $amount, ?User $causer = null): UnlockPackageBodyResult
    {
        return DB::transaction(function () use ($package, $amount, $causer): UnlockPackageBodyResult {
            $lockedPackage = ItcPackage::query()
                ->where('uuid', $package->uuid)
                ->with('transaction')
                ->lockForUpdate()
                ->firstOrFail();

            Log::debug('[UnlockPackageBodyAmountAction.execute] start', [
                'package_uuid' => $lockedPackage->uuid,
                'user_id' => $lockedPackage->transaction?->user_id,
                'requested_amount' => $amount,
            ]);

            // Ожидающие разблокировки тоже блокируем: без этого две параллельные
            // разблокировки увидят одно и то же «доступно» и вместе перерасходуют тело.
            PackageBodyUnlock::query()
                ->where('package_uuid', $lockedPackage->uuid)
                ->pending()
                ->lockForUpdate()
                ->get();

            $requested = $this->parseAmount($amount, $lockedPackage);

            if (! $this->balanceResolver->isUnlockableType($lockedPackage)) {
                Log::warning('[UnlockPackageBodyAmountAction.execute] rejected: package type is not unlockable', [
                    'package_uuid' => $lockedPackage->uuid,
                    'package_type' => $lockedPackage->type->value,
                ]);

                throw new InvalidAmountException(__('livewire_itc_packages_body_unlock_wrong_type'));
            }

            $available = $this->balanceResolver->availableToUnlock($lockedPackage);

            Log::debug('[UnlockPackageBodyAmountAction.execute] available', [
                'package_uuid' => $lockedPackage->uuid,
                'available' => (string) $available,
                'requested' => (string) $requested,
            ]);

            if ($available->isZero()) {
                Log::warning('[UnlockPackageBodyAmountAction.execute] rejected: nothing available to unlock', [
                    'package_uuid' => $lockedPackage->uuid,
                ]);

                throw new InvalidAmountException(__('livewire_itc_packages_body_unlock_not_available'));
            }

            if ($requested->isGreaterThan($available)) {
                Log::warning('[UnlockPackageBodyAmountAction.execute] rejected: amount above the available body', [
                    'package_uuid' => $lockedPackage->uuid,
                    'available' => (string) $available,
                    'requested' => (string) $requested,
                ]);

                throw new InvalidAmountException(__('livewire_itc_packages_body_unlock_too_large'));
            }

            $unlockedAt = Carbon::now();
            $payoutAt = $unlockedAt->copy()->addMonthNoOverflow();

            $unlock = PackageBodyUnlock::query()->create([
                'uuid' => 'PBU-' . Str::random(10),
                'package_uuid' => $lockedPackage->uuid,
                'amount' => (string) $requested,
                'unlocked_at' => $unlockedAt,
                'payout_at' => $payoutAt,
            ]);

            $remainingBody = $available->minus($requested);

            Log::info('[UnlockPackageBodyAmountAction.execute] unlocked', [
                'unlock_uuid' => $unlock->uuid,
                'package_uuid' => $lockedPackage->uuid,
                'amount' => (string) $requested,
                'payout_at' => $payoutAt->toDateTimeString(),
                'remaining_body' => (string) $remainingBody,
            ]);

            if ($lockedPackage->transaction !== null) {
                $this->activityLogger->write(new WriteBusinessActivityData(
                    type: ActivityEventTypeEnum::PackageBodyUnlocked,
                    userId: $lockedPackage->transaction->user_id,
                    subject: $lockedPackage,
                    feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                    properties: [
                        'amount' => (string) $requested,
                        'package_uuid' => $lockedPackage->uuid,
                        'payout_at' => $payoutAt->toDateTimeString(),
                        'remaining_body' => (string) $remainingBody,
                        'unlock_uuid' => $unlock->uuid,
                    ],
                    causer: $causer ?? auth()->user(),
                    logName: 'packages',
                    context: auth()->check() ? 'account' : 'system',
                ));
            }

            return new UnlockPackageBodyResult(
                uuid: $unlock->uuid,
                amount: $requested,
                payoutAt: $payoutAt,
                remainingBody: $remainingBody,
            );
        });
    }

    /**
     * @throws InvalidAmountException when the input is not a positive number
     */
    private function parseAmount(string $amount, ItcPackage $package): BigDecimal
    {
        try {
            $requested = BigDecimal::of(str_replace([' ', ','], ['', '.'], $amount));
        } catch (MathException) {
            Log::warning('[UnlockPackageBodyAmountAction.execute] rejected: amount is not a number', [
                'package_uuid' => $package->uuid,
                'requested_amount' => $amount,
            ]);

            throw new InvalidAmountException(__('livewire_itc_packages_body_unlock_too_large'));
        }

        if ($requested->isNegativeOrZero()) {
            Log::warning('[UnlockPackageBodyAmountAction.execute] rejected: non-positive amount', [
                'package_uuid' => $package->uuid,
                'requested' => (string) $requested,
            ]);

            throw new InvalidAmountException(__('livewire_itc_packages_body_unlock_too_large'));
        }

        return $requested;
    }
}
