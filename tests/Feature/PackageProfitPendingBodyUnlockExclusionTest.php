<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageBodyUnlock;
use App\Models\PackageProfit;
use App\Models\Transaction;
use App\Models\User;

/**
 * @return array{0: User, 1: ItcPackage}
 */
function createBodyExclusionPackage(User $user, string $bodyAmount = '1000'): array
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

function runBodyUnlockProfitAccrual(User $admin): void
{
    test()->withoutMiddleware();

    test()->actingAs($admin)
        ->post('/itcapitalmoonshineadminpanel/itc-packages/profits/mass', [
            'profit_percent' => 100,
        ]);
}

it('исключает разблокированную часть тела из базы начисления дивидендов', function () {
    $admin = User::factory()->create();

    // Пакет A: 500 из 1000 разблокировано — в базу начисления входит только 500.
    [, $unlockedPackage] = createBodyExclusionPackage(User::factory()->create());
    PackageBodyUnlock::factory()->create([
        'package_uuid' => $unlockedPackage->uuid,
        'amount' => '500.00',
    ]);

    // Пакет B: точно такой же, но тело целиком продолжает работать.
    [, $activePackage] = createBodyExclusionPackage(User::factory()->create());

    runBodyUnlockProfitAccrual($admin);

    $unlockedProfit = PackageProfit::query()->where('package_uuid', $unlockedPackage->uuid)->sole();
    $activeProfit = PackageProfit::query()->where('package_uuid', $activePackage->uuid)->sole();

    // База A = 500, база B = 1000 → отношение начислений равно отношению баз.
    expect((float) $activeProfit->amount / (float) $unlockedProfit->amount)
        ->toEqualWithDelta(2.0, 0.0001);
});

it('возвращает снятую закрытием пакета разблокировку в базу начисления', function () {
    $admin = User::factory()->create();

    [, $cancelledPackage] = createBodyExclusionPackage(User::factory()->create());
    PackageBodyUnlock::factory()->cancelled()->create([
        'package_uuid' => $cancelledPackage->uuid,
        'amount' => '500.00',
    ]);

    [, $activePackage] = createBodyExclusionPackage(User::factory()->create());

    runBodyUnlockProfitAccrual($admin);

    $cancelledProfit = PackageProfit::query()->where('package_uuid', $cancelledPackage->uuid)->sole();
    $activeProfit = PackageProfit::query()->where('package_uuid', $activePackage->uuid)->sole();

    expect((float) $cancelledProfit->amount)
        ->toEqualWithDelta((float) $activeProfit->amount, 0.00000001);
});

it('не двигает базу начисления в момент выплаты: ни разрыва, ни двойного вычета', function () {
    $admin = User::factory()->create();

    [, $package] = createBodyExclusionPackage(User::factory()->create());
    PackageBodyUnlock::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '500.00',
    ]);

    // Начисление, пока разблокировка ждёт выплаты: её вычитает pending_body_unlocks.
    runBodyUnlockProfitAccrual($admin);
    $beforePayout = (float) PackageProfit::query()->where('package_uuid', $package->uuid)->sole()->amount;

    test()->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();

    // Начисление после выплаты: ту же сумму теперь вычитает package_balance_withdraws.
    runBodyUnlockProfitAccrual($admin);
    $afterPayout = (float) PackageProfit::query()
        ->where('package_uuid', $package->uuid)
        ->orderByDesc('id')
        ->first()
        ->amount;

    expect($afterPayout)->toEqualWithDelta($beforePayout, 0.00000001);
});
