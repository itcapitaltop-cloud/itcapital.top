<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageBalanceWithdraw;
use App\Models\PackageBodyUnlock;
use App\Models\Transaction;
use App\Models\User;
use App\Services\User\UserBalanceCalculator;

/**
 * @return array{0: User, 1: ItcPackage}
 */
function createBodyPayoutPackage(string $bodyAmount = '1000'): array
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
        'month_profit_percent' => 8.2,
    ]);

    return [$user, $package];
}

function bodyUnlockWithdrawTransactions(string $packageUuid): \Illuminate\Support\Collection
{
    $withdrawUuids = PackageBalanceWithdraw::query()
        ->where('package_uuid', $packageUuid)
        ->pluck('uuid');

    return Transaction::query()
        ->whereIn('uuid', $withdrawUuids)
        ->where('trx_type', TrxTypeEnum::WITHDRAW_PACKAGE_TO_BALANCE)
        ->get();
}

it('выплачивает созревшую разблокировку на основной баланс ровно одной транзакцией', function () {
    [$user, $package] = createBodyPayoutPackage();

    $unlock = PackageBodyUnlock::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);

    $calculator = app(UserBalanceCalculator::class);
    $balanceBefore = $calculator->balanceFor($user->id, BalanceTypeEnum::MAIN, forceFresh: true);

    $this->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();

    $balanceAfter = $calculator->balanceFor($user->id, BalanceTypeEnum::MAIN, forceFresh: true);

    expect(bodyUnlockWithdrawTransactions($package->uuid))->toHaveCount(1)
        ->and(PackageBalanceWithdraw::query()->where('package_uuid', $package->uuid)->count())->toBe(1)
        ->and($unlock->fresh()->payout_transaction_uuid)->not->toBeNull()
        ->and((float) $balanceAfter - (float) $balanceBefore)->toEqualWithDelta(300.0, 0.00000001);
});

it('не трогает разблокировку, у которой дата выплаты ещё не наступила', function () {
    [, $package] = createBodyPayoutPackage();

    $unlock = PackageBodyUnlock::factory()->create([
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);

    $this->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();

    expect($unlock->fresh()->payout_transaction_uuid)->toBeNull()
        ->and(PackageBalanceWithdraw::query()->where('package_uuid', $package->uuid)->count())->toBe(0);
});

it('не трогает разблокировку, снятую закрытием пакета', function () {
    [, $package] = createBodyPayoutPackage();

    $unlock = PackageBodyUnlock::factory()->duePayout()->cancelled()->create([
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);

    $this->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();

    expect($unlock->fresh()->payout_transaction_uuid)->toBeNull()
        ->and(PackageBalanceWithdraw::query()->where('package_uuid', $package->uuid)->count())->toBe(0);
});

it('не создаёт вторую выплату при повторном запуске команды', function () {
    [$user, $package] = createBodyPayoutPackage();

    PackageBodyUnlock::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);

    $calculator = app(UserBalanceCalculator::class);
    $balanceBefore = $calculator->balanceFor($user->id, BalanceTypeEnum::MAIN, forceFresh: true);

    $this->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();
    $this->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();

    $balanceAfter = $calculator->balanceFor($user->id, BalanceTypeEnum::MAIN, forceFresh: true);

    expect(bodyUnlockWithdrawTransactions($package->uuid))->toHaveCount(1)
        ->and(PackageBalanceWithdraw::query()->where('package_uuid', $package->uuid)->count())->toBe(1)
        ->and((float) $balanceAfter - (float) $balanceBefore)->toEqualWithDelta(300.0, 0.00000001);
});

it('в режиме dry-run ничего не пишет', function () {
    [, $package] = createBodyPayoutPackage();

    $unlock = PackageBodyUnlock::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);

    $this->artisan('packages:payout-unlocked-body-amounts', ['--dry-run' => true])->assertSuccessful();

    expect($unlock->fresh()->payout_transaction_uuid)->toBeNull()
        ->and(bodyUnlockWithdrawTransactions($package->uuid))->toHaveCount(0);
});

it('по --uuid выплачивает конкретную разблокировку до наступления даты выплаты', function () {
    [, $package] = createBodyPayoutPackage();

    $early = PackageBodyUnlock::factory()->create([
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);
    $untouched = PackageBodyUnlock::factory()->create([
        'package_uuid' => $package->uuid,
        'amount' => '200.00',
    ]);

    $this->artisan('packages:payout-unlocked-body-amounts', ['--uuid' => $early->uuid])->assertSuccessful();

    expect($early->fresh()->payout_transaction_uuid)->not->toBeNull()
        ->and($untouched->fresh()->payout_transaction_uuid)->toBeNull()
        ->and(bodyUnlockWithdrawTransactions($package->uuid))->toHaveCount(1);
});

it('по --uuid пропускает уже выплаченную разблокировку', function () {
    [, $package] = createBodyPayoutPackage();

    $unlock = PackageBodyUnlock::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);

    $this->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();
    $this->artisan('packages:payout-unlocked-body-amounts', ['--uuid' => $unlock->uuid])->assertSuccessful();

    expect(bodyUnlockWithdrawTransactions($package->uuid))->toHaveCount(1);
});

it('обнуляет доходность, когда после выплаты в теле осталось меньше 100 ITC', function () {
    [, $package] = createBodyPayoutPackage('100');

    PackageBodyUnlock::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '25.00',
    ]);

    expect((float) $package->month_profit_percent)->toBe(8.2);

    $this->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();

    // Остаток 75 ITC — тот же порог и то же поведение, что у мгновенного вывода тела.
    expect((float) $package->fresh()->month_profit_percent)->toBe(0.0);
});

it('обнуляет доходность, когда выплачено всё тело пакета', function () {
    [, $package] = createBodyPayoutPackage();

    PackageBodyUnlock::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '1000.00',
    ]);

    $this->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();

    expect((float) $package->fresh()->month_profit_percent)->toBe(0.0);
});

it('не обнуляет доходность, когда в теле остаётся не меньше 100 ITC', function () {
    [, $package] = createBodyPayoutPackage();

    PackageBodyUnlock::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);

    $this->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();

    expect((float) $package->fresh()->month_profit_percent)->toBe(8.2);
});
