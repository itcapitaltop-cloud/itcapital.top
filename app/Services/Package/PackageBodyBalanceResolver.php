<?php

declare(strict_types=1);

namespace App\Services\Package;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Единственный источник истины для «сколько тела пакета можно разблокировать».
 *
 * Формула тела повторяется в проекте в шести местах; для разблокировки её нельзя
 * копировать седьмой раз: и валидатор новой разблокировки, и валидатор мгновенного
 * вывода `max_package_sum`, и карточка обязаны видеть одну и ту же цифру, иначе
 * ожидающая выплаты сумма будет потрачена дважды.
 */
final readonly class PackageBodyBalanceResolver
{
    /**
     * Порог, ниже которого тело пакета перестаёт приносить доходность.
     *
     * Разблокировать можно любую сумму в пределах доступного тела: ограничения на
     * остаток нет. Но когда деньги фактически уходят на баланс и тело оказывается
     * ниже этого порога, `month_profit_percent` обнуляется — ровно так же, как это
     * делает мгновенный вывод `Packages::withdrawPackageBalance()`.
     */
    public const MIN_BODY_REMAINDER = '100';

    /**
     * Типы пакетов, тело которых принадлежит пользователю и может быть разблокировано.
     * PRESENT исключён: подарочное тело — не деньги пользователя.
     *
     * @var array<int, PackageTypeEnum>
     */
    public const UNLOCKABLE_TYPES = [
        PackageTypeEnum::STANDARD,
        PackageTypeEnum::PRIVILEGE,
        PackageTypeEnum::VIP,
    ];

    /**
     * Тело пакета за вычетом уже разблокированных, но ещё не выплаченных сумм.
     *
     * Суммы всегда перечитываются одним запросом: метод вызывается и из Livewire-действия,
     * и из валидатора, где пакет приходит без eager-загруженных агрегатов, а ленивые
     * обращения к отношениям в цикле карточек дают N+1.
     */
    public function availableToUnlock(ItcPackage $package): BigDecimal
    {
        $row = ItcPackage::query()
            ->where('uuid', $package->uuid)
            ->with('transaction')
            ->withSum(['partnerTransfers' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestToBody' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['balanceWithdraws' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['pendingBodyUnlocks' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->first();

        if ($row === null) {
            Log::warning('[PackageBodyBalanceResolver.availableToUnlock] package not found', [
                'package_uuid' => $package->uuid,
            ]);

            return BigDecimal::zero();
        }

        $body = BigDecimal::of((string) ($row->transaction->amount ?? '0'))
            ->plus((string) ($row->partner_transfers_sum_amount ?? '0'))
            ->plus((string) ($row->reinvest_to_body_sum_amount ?? '0'))
            ->minus((string) ($row->balance_withdraws_sum_amount ?? '0'));

        $pending = BigDecimal::of((string) ($row->pending_body_unlocks_sum_amount ?? '0'));

        $available = $body->minus($pending);

        Log::debug('[PackageBodyBalanceResolver.availableToUnlock]', [
            'package_uuid' => $row->uuid,
            'body' => (string) $body,
            'pending_unlocks' => (string) $pending,
            'available' => (string) $available,
        ]);

        if ($available->isNegative()) {
            // Отрицательное тело — расхождение данных, а не штатная ситуация:
            // зажимаем в ноль, но обязательно оставляем след.
            Log::warning('[PackageBodyBalanceResolver.availableToUnlock] negative body clamped to zero', [
                'package_uuid' => $row->uuid,
                'body' => (string) $body,
                'pending_unlocks' => (string) $pending,
                'available' => (string) $available,
            ]);

            return BigDecimal::zero();
        }

        return $available;
    }

    /**
     * Разрешён ли тип пакета к разблокировке тела.
     *
     * Карточка проверяет тот же список через `self::UNLOCKABLE_TYPES`, а доступную
     * сумму считает из уже загруженных агрегатов — вызов резолвера на каждую карточку
     * стоил бы одного запроса на пакет.
     */
    public function isUnlockableType(ItcPackage $package): bool
    {
        return in_array($package->type, self::UNLOCKABLE_TYPES, true);
    }

    /**
     * Опустилось ли тело пакета ниже порога доходности. Тот же порог и то же
     * сравнение, что в мгновенном выводе тела, чтобы оба пути вели к одному состоянию.
     */
    public function isBelowMinimumRemainder(BigDecimal $body): bool
    {
        return $body->isLessThan(BigDecimal::of(self::MIN_BODY_REMAINDER));
    }
}
