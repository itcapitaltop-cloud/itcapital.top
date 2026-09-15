<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitReinvestWithdraw;
use App\Models\Transaction;
use App\Models\User;

/**
 * @return array{0: User, 1: ItcPackage}
 */
function createReinvestPayoutPackage(string $bodyAmount = '1000'): array
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

function reinvestPayoutTransactions(int $userId)
{
    return Transaction::query()
        ->where('user_id', $userId)
        ->where('trx_type', TrxTypeEnum::WITHDRAW_PACKAGE_REINVEST_PROFIT)
        ->where('balance_type', BalanceTypeEnum::MAIN)
        ->get();
}

it('не выплачивает разлоченный реинвест до наступления даты выплаты', function () {
    [$user, $package] = createReinvestPayoutPackage();

    PackageProfitReinvest::factory()->unlocked()->create([
        'package_uuid' => $package->uuid,
        'amount' => '200',
    ]);

    $this->artisan('packages:payout-unlocked-reinvests')->assertSuccessful();

    expect(reinvestPayoutTransactions($user->id))->toHaveCount(0)
        ->and(PackageProfitReinvestWithdraw::query()->count())->toBe(0);
});

it('выплачивает созревшую разблокировку одной транзакцией на основной баланс', function () {
    [$user, $package] = createReinvestPayoutPackage();

    $reinvest = PackageProfitReinvest::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '200',
    ]);

    $this->artisan('packages:payout-unlocked-reinvests')->assertSuccessful();

    $transactions = reinvestPayoutTransactions($user->id);

    expect($transactions)->toHaveCount(1)
        ->and((float) $transactions->first()->amount)->toBe(200.0)
        ->and($transactions->first()->uuid)->toStartWith('WPRP-');

    $withdraw = PackageProfitReinvestWithdraw::query()->sole();

    expect($withdraw->reinvest_uuid)->toBe($reinvest->uuid)
        ->and($withdraw->uuid)->toBe($transactions->first()->uuid);
});

it('не выплачивает один и тот же реинвест дважды', function () {
    [$user, $package] = createReinvestPayoutPackage();

    PackageProfitReinvest::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '200',
    ]);

    $this->artisan('packages:payout-unlocked-reinvests')->assertSuccessful();
    $this->artisan('packages:payout-unlocked-reinvests')->assertSuccessful();

    expect(reinvestPayoutTransactions($user->id))->toHaveCount(1)
        ->and(PackageProfitReinvestWithdraw::query()->count())->toBe(1);
});

it('не трогает замороженные и активные реинвесты', function () {
    [$user, $package] = createReinvestPayoutPackage();

    PackageProfitReinvest::factory()->matured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '50',
    ]);
    PackageProfitReinvest::factory()->notMatured()->create([
        'package_uuid' => $package->uuid,
        'amount' => '40',
    ]);

    $this->artisan('packages:payout-unlocked-reinvests')->assertSuccessful();

    expect(reinvestPayoutTransactions($user->id))->toHaveCount(0);
});

it('в режиме dry-run ничего не пишет', function () {
    [$user, $package] = createReinvestPayoutPackage();

    PackageProfitReinvest::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '200',
    ]);

    $this->artisan('packages:payout-unlocked-reinvests', ['--dry-run' => true])->assertSuccessful();

    expect(reinvestPayoutTransactions($user->id))->toHaveCount(0)
        ->and(PackageProfitReinvestWithdraw::query()->count())->toBe(0);
});

it('выплачивает одну строку по --uuid, игнорируя дату выплаты', function () {
    [$user, $package] = createReinvestPayoutPackage();

    $pending = PackageProfitReinvest::factory()->unlocked()->create([
        'package_uuid' => $package->uuid,
        'amount' => '200',
    ]);

    $this->artisan('packages:payout-unlocked-reinvests', ['--uuid' => $pending->uuid])
        ->assertSuccessful();

    expect(reinvestPayoutTransactions($user->id))->toHaveCount(1)
        ->and(PackageProfitReinvestWithdraw::query()->sole()->reinvest_uuid)->toBe($pending->uuid);
});
