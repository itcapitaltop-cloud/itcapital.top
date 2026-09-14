<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Dashboard\Index as DashboardIndex;
use App\Models\ItcPackage;
use App\Models\PackageBodyUnlock;
use App\Models\Transaction;
use App\Models\User;
use Brick\Math\BigDecimal;
use Livewire\Livewire;

/**
 * Стандартное правило проекта: "Сумма пакетов" на /account обязана совпадать с суммой
 * депозитов карточек на вкладке ITC Packages. Карточка вычитает ожидающие выплаты
 * разблокировки (они показаны отдельной строкой), значит дашборд обязан вычитать их тоже.
 */
function makeBodyUnlockPackage(User $user, string $body): ItcPackage
{
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => $body,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    return ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => PackageTypeEnum::STANDARD,
    ]);
}

/**
 * Депозит карточки ровно так, как его считает
 * resources/views/components/account/itc/package.blade.php.
 */
function cardDepositTotal(int $userId): float
{
    return ItcPackage::query()
        ->userPackagesWithFinancials($userId)
        ->notActive()
        ->get()
        ->sum(fn (ItcPackage $package): float => (float) (string) BigDecimal::of((string) $package->transaction->amount)
            ->plus((string) ($package->partner_transfers_sum_amount ?? '0'))
            ->plus((string) ($package->reinvest_to_body_sum_amount ?? '0'))
            ->minus((string) ($package->balance_withdraws_sum_amount ?? '0'))
            ->minus((string) ($package->pending_body_unlocks_sum_amount ?? '0')));
}

it('вычитает ожидающие разблокировки из "Суммы пакетов" на дашборде', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $package = makeBodyUnlockPackage($user, '1000');

    PackageBodyUnlock::factory()->create([
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);

    Livewire::test(DashboardIndex::class)
        ->assertViewHas('depositTotalAmount', fn (float $amount): bool => abs($amount - 700.0) < 0.01);
});

it('сходится с суммой депозитов карточек ITC при нескольких разблокировках', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $first = makeBodyUnlockPackage($user, '1000');
    $second = makeBodyUnlockPackage($user, '2500');
    makeBodyUnlockPackage($user, '400');

    PackageBodyUnlock::factory()->create([
        'package_uuid' => $first->uuid,
        'amount' => '300.00',
    ]);
    PackageBodyUnlock::factory()->create([
        'package_uuid' => $second->uuid,
        'amount' => '1200.50',
    ]);
    // Снятая закрытием пакета разблокировка деньги не забирает и из депозита не вычитается.
    PackageBodyUnlock::factory()->cancelled()->create([
        'package_uuid' => $second->uuid,
        'amount' => '500.00',
    ]);

    $expected = cardDepositTotal($user->id);

    expect($expected)->toEqualWithDelta(2399.5, 0.01);

    Livewire::test(DashboardIndex::class)
        ->assertViewHas('depositTotalAmount', fn (float $amount): bool => abs($amount - $expected) < 0.01);
});

it('возвращает деньги в "Сумму пакетов" не раньше, чем они уходят из ожидания выплаты', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $package = makeBodyUnlockPackage($user, '1000');

    PackageBodyUnlock::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);

    Livewire::test(DashboardIndex::class)
        ->assertViewHas('depositTotalAmount', fn (float $amount): bool => abs($amount - 700.0) < 0.01);

    // После выплаты ту же сумму вычитает уже package_balance_withdraws — цифра не меняется.
    $this->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();

    Livewire::test(DashboardIndex::class)
        ->assertViewHas('depositTotalAmount', fn (float $amount): bool => abs($amount - 700.0) < 0.01);

    expect(cardDepositTotal($user->id))->toEqualWithDelta(700.0, 0.01);
});
