<?php

declare(strict_types=1);

namespace App\Actions\Packages;

use App\Dto\Activity\WriteBusinessActivityData;
use App\Dto\Packages\UnlockMaturedReinvestsResult;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Exceptions\Domain\InvalidAmountException;
use App\Models\ItcPackage;
use App\Models\PackageProfitReinvest;
use App\Models\User;
use App\Services\ActivityLog\BusinessActivityLogger;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Снимает все созревшие реинвесты пакета одной операцией.
 *
 * Суммы сразу покидают базу начисления дивидендов (их перестаёт видеть
 * `ItcPackage::activeReinvestProfits()`) и попадают на основной баланс ровно через
 * календарный месяц силами `packages:payout-unlocked-reinvests`.
 * Операция необратима для пользователя.
 */
final readonly class UnlockMaturedReinvestsAction
{
    public function __construct(
        private BusinessActivityLogger $activityLogger,
    ) {}

    /**
     * Суммы аргументом не принимает: снимаются все созревшие реинвесты пакета.
     *
     * @throws InvalidAmountException when the package has no matured reinvests to unlock
     */
    public function execute(ItcPackage $package, ?User $causer = null): UnlockMaturedReinvestsResult
    {
        return DB::transaction(function () use ($package, $causer): UnlockMaturedReinvestsResult {
            $lockedPackage = ItcPackage::query()
                ->where('uuid', $package->uuid)
                ->with('transaction')
                ->lockForUpdate()
                ->firstOrFail();

            // whereNull('unlocked_at') внутри скоупа плюс блокировка строк — это то,
            // что превращает двойной клик в no-op, а не во вторую разблокировку.
            $reinvests = PackageProfitReinvest::query()
                ->where('package_uuid', $lockedPackage->uuid)
                ->unlockable()
                ->lockForUpdate()
                ->get();

            if ($reinvests->isEmpty()) {
                Log::warning('[UnlockMaturedReinvestsAction.execute] rejected: no matured reinvests', [
                    'package_uuid' => $lockedPackage->uuid,
                    'user_id' => $lockedPackage->transaction?->user_id,
                ]);

                throw new InvalidAmountException(__('livewire_itc_packages_no_matured_reinvests'));
            }

            $unlockedAt = Carbon::now();

            // Ровно календарный месяц: 31 января → 28/29 февраля. Той же формулой
            // окно предсказывает модалка подтверждения в package.blade.php.
            $payoutAt = $unlockedAt->copy()->addMonthNoOverflow();

            $amount = $reinvests->reduce(
                fn (BigDecimal $carry, PackageProfitReinvest $reinvest): BigDecimal => $carry->plus($reinvest->amount),
                BigDecimal::zero(),
            );

            PackageProfitReinvest::query()
                ->whereIn('uuid', $reinvests->pluck('uuid')->all())
                ->update([
                    'unlocked_at' => $unlockedAt,
                    'payout_at' => $payoutAt,
                ]);

            if ($lockedPackage->transaction !== null) {
                $this->activityLogger->write(new WriteBusinessActivityData(
                    type: ActivityEventTypeEnum::PackageReinvestUnlocked,
                    userId: $lockedPackage->transaction->user_id,
                    subject: $lockedPackage,
                    feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                    properties: [
                        'amount' => (string) $amount,
                        'package_uuid' => $lockedPackage->uuid,
                        'payout_at' => $payoutAt->toDateTimeString(),
                        'count' => $reinvests->count(),
                    ],
                    causer: $causer ?? auth()->user(),
                    logName: 'packages',
                    context: auth()->check() ? 'account' : 'system',
                ));
            }

            return new UnlockMaturedReinvestsResult(
                amount: $amount,
                payoutAt: $payoutAt,
                count: $reinvests->count(),
            );
        });
    }
}
