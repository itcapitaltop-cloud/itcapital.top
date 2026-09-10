<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages;
use App\Models\ItcPackage;
use App\Models\PackageBalanceWithdraw;
use App\Models\PackageBodyUnlock;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

/**
 * @return array{0: User, 1: ItcPackage}
 */
function createDoubleSpendPackage(string $bodyAmount = '1000'): array
{
    $user = User::factory()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => $bodyAmount,
        'accepted_at' => now(),
    ]);

    $package = ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => PackageTypeEnum::STANDARD,
        // Мгновенный вывод доступен только после окончания срока работы пакета.
        'work_to' => now()->subDay(),
    ]);

    return [$user, $package];
}

it('не даёт мгновенно вывести сумму, которая уже ждёт выплаты по разблокировке', function () {
    [$user, $package] = createDoubleSpendPackage();
    $this->actingAs($user);

    PackageBodyUnlock::factory()->create([
        'package_uuid' => $package->uuid,
        'amount' => '600.00',
    ]);

    // В теле 1000, из них 600 уже разблокированы — мгновенно доступно только 400.
    Livewire::test(Packages::class)
        ->set('withdrawPackageAmount', '600')
        ->call('withdrawPackageBalance', $package->uuid)
        ->assertHasErrors('withdrawPackageAmount');

    expect(PackageBalanceWithdraw::query()->where('package_uuid', $package->uuid)->count())->toBe(0);

    Livewire::test(Packages::class)
        ->set('withdrawPackageAmount', '400')
        ->call('withdrawPackageBalance', $package->uuid)
        ->assertHasNoErrors();

    expect(PackageBalanceWithdraw::query()->where('package_uuid', $package->uuid)->count())->toBe(1);
});

it('запрещает мгновенный вывод с чужого пакета', function () {
    [, $package] = createDoubleSpendPackage();

    $stranger = User::factory()->create();
    $this->actingAs($stranger);

    Livewire::test(Packages::class)
        ->set('withdrawPackageAmount', '500')
        ->call('withdrawPackageBalance', $package->uuid)
        ->assertForbidden();

    expect(PackageBalanceWithdraw::query()->where('package_uuid', $package->uuid)->count())->toBe(0)
        ->and(Transaction::query()
            ->where('user_id', $stranger->id)
            ->where('trx_type', TrxTypeEnum::WITHDRAW_PACKAGE_TO_BALANCE)
            ->count())->toBe(0);
});
