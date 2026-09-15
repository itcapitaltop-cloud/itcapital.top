<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\Transaction;
use App\Models\User;

/**
 * @return array{0: User, 1: ItcPackage}
 */
function createReinvestExclusionPackage(User $user, string $bodyAmount = '1000'): array
{
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

function runReinvestUnlockProfitAccrual(User $admin): void
{
    test()->withoutMiddleware();

    test()->actingAs($admin)
        ->post('/itcapitalmoonshineadminpanel/itc-packages/profits/mass', [
            'profit_percent' => 100,
        ]);
}

function latestProfitAmount(string $packageUuid): float
{
    return (float) PackageProfit::query()
        ->where('package_uuid', $packageUuid)
        ->orderByDesc('id')
        ->firstOrFail()
        ->amount;
}

/**
 * Начисление считает base * (month_profit_percent / 3100) * (profit_percent / 100) * 7
 * с делением на scale 8 и HALF_EVEN, поэтому ожидаемые суммы здесь выписаны явно.
 */
it('исключает разлоченный реинвест из базы начисления дивидендов', function () {
    $admin = User::factory()->create();

    [, $package] = createReinvestExclusionPackage(User::factory()->create());

    $reinvest = PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '200',
    ]);

    // До снятия база = тело 1000 + реинвест 200 = 1200.
    runReinvestUnlockProfitAccrual($admin);

    expect(latestProfitAmount($package->uuid))->toEqualWithDelta(22.219344, 0.0001);

    // Пользователь снял реинвест: он сразу покидает базу, не дожидаясь выплаты.
    $reinvest->update([
        'unlocked_at' => now(),
        'payout_at' => now()->addMonthNoOverflow(),
    ]);

    // После снятия база = только тело 1000.
    runReinvestUnlockProfitAccrual($admin);

    expect(latestProfitAmount($package->uuid))->toEqualWithDelta(18.51612, 0.0001);
});

it('держит активный реинвест в базе начисления', function () {
    $admin = User::factory()->create();

    [, $package] = createReinvestExclusionPackage(User::factory()->create());

    PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '200',
    ]);

    runReinvestUnlockProfitAccrual($admin);

    // Созревший, но не снятый реинвест продолжает приносить дивиденды.
    expect(latestProfitAmount($package->uuid))->toEqualWithDelta(22.219344, 0.0001);
});

it('не вычитает разлоченный реинвест повторно после выплаты', function () {
    $admin = User::factory()->create();

    [, $package] = createReinvestExclusionPackage(User::factory()->create());

    PackageProfitReinvest::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '200',
    ]);

    // Начисление, пока выплата в ожидании: реинвест уже вне базы из-за unlocked_at.
    runReinvestUnlockProfitAccrual($admin);
    $beforePayout = latestProfitAmount($package->uuid);

    test()->artisan('packages:payout-unlocked-reinvests')->assertSuccessful();

    // После выплаты реинвест просто остаётся вне базы: второй раз она не проседает.
    runReinvestUnlockProfitAccrual($admin);
    $afterPayout = latestProfitAmount($package->uuid);

    expect($beforePayout)->toEqualWithDelta(18.51612, 0.0001)
        ->and($afterPayout)->toEqualWithDelta($beforePayout, 0.00000001);
});
