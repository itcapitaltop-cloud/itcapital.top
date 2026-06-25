<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Dashboard\Index as DashboardIndex;
use App\Models\ItcPackage;
use App\Models\PackageBalanceWithdraw;
use App\Models\PackagePartnerTransfer;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitReinvestWithdraw;
use App\Models\PackageZeroing;
use App\Models\ReinvestToPackageBody;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Guards the "Investment Overview" dashboard widget so it reconciles with the ITC Packages
 * tab (/account/itc), the customer's reference "activity log":
 *   - PRESENT packages are counted (only ARCHIVE/STAKING are excluded, like ItcPackage::notActive());
 *   - active (not-yet-withdrawn) reinvest is added to the deposit total, mirroring each card's
 *     "+Реинвестировано" badge;
 *   - zeroed PRESENT packages contribute 0 body (but still count their active reinvest);
 *   - partner "yield" stats report gross accrual, matching the Partner Program page.
 */
function makePackage(User $user, PackageTypeEnum $type, float $body): ItcPackage
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
        'type' => $type,
    ]);
}

function addActiveReinvest(ItcPackage $package, float $amount): PackageProfitReinvest
{
    return PackageProfitReinvest::create([
        'uuid' => 'PPR-' . Str::random(10),
        'package_uuid' => $package->uuid,
        'amount' => $amount,
        'matured_at' => now()->addDays(180),
    ]);
}

function addWithdrawnReinvest(ItcPackage $package, float $amount): void
{
    $reinvest = addActiveReinvest($package, $amount);

    PackageProfitReinvestWithdraw::create([
        'uuid' => (string) Str::uuid(),
        'reinvest_uuid' => $reinvest->uuid,
    ]);
}

/**
 * Replicates the ITC Packages tab deposit total (the source of truth): per notActive package,
 * the card body (0 for zeroed PRESENT) plus its active "+Реинвестировано" amount.
 */
function itcTabDepositTotal(User $user): float
{
    return (float) ItcPackage::query()
        ->notActive()
        ->userPackagesWithFinancials($user->id)
        ->get()
        ->sum(function (ItcPackage $package): float {
            $body = $package->type === PackageTypeEnum::PRESENT && $package->zeroing
                ? 0.0
                : (float) $package->total_amount;

            return $body + (float) $package->reinvest_profits_sum_amount;
        });
}

it('includes active reinvest in depositTotalAmount alongside reinvestToBody/partnerTransfers/balanceWithdraws', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $package = makePackage($user, PackageTypeEnum::STANDARD, 1000);

    // Active (not yet withdrawn) reinvested dividend — now counted (matches the card's "+Реинвестировано").
    addActiveReinvest($package, 250);

    // Folded into the package body on renewal — must count.
    ReinvestToPackageBody::create([
        'uuid' => (string) Str::uuid(),
        'package_uuid' => $package->uuid,
        'amount' => 100,
    ]);

    // Partner transfer into the package — must count.
    $partnerTransferTrx = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::PARTNER_TO_PACKAGE,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'amount' => 50,
        'accepted_at' => now(),
    ]);
    PackagePartnerTransfer::create([
        'uuid' => $partnerTransferTrx->uuid,
        'package_uuid' => $package->uuid,
    ]);

    // Withdrawn from the body to the main balance — must be subtracted.
    $withdrawTrx = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::WITHDRAW_PACKAGE_TO_BALANCE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 30,
        'accepted_at' => now(),
    ]);
    PackageBalanceWithdraw::create([
        'uuid' => $withdrawTrx->uuid,
        'package_uuid' => $package->uuid,
    ]);

    // Expected: 1000 + 50 (partnerTransfers) + 100 (reinvestToBody) - 30 (balanceWithdraws)
    //         + 250 (active reinvestProfits) = 1370.
    Livewire::test(DashboardIndex::class)
        ->assertViewHas('depositTotalAmount', fn (float $amount): bool => abs($amount - 1370.0) < 0.01);
});

it('counts an active reinvestProfits-only package toward depositTotalAmount (body + reinvest)', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $package = makePackage($user, PackageTypeEnum::STANDARD, 500);

    addActiveReinvest($package, 478.62);

    // Expected: deposit (500) + active reinvest (478.62) = 978.62.
    Livewire::test(DashboardIndex::class)
        ->assertViewHas('depositTotalAmount', fn (float $amount): bool => abs($amount - 978.62) < 0.01);
});

it('excludes only ARCHIVE and STAKING (PRESENT is counted) across packagesCount, depositTotalAmount, yieldTotal, and yieldWeek', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $visible = makePackage($user, PackageTypeEnum::STANDARD, 1000);
    $archived = makePackage($user, PackageTypeEnum::ARCHIVE, 2000);
    $staking = makePackage($user, PackageTypeEnum::STAKING, 3000);
    $present = makePackage($user, PackageTypeEnum::PRESENT, 4000);

    foreach ([$visible, $archived, $staking, $present] as $package) {
        PackageProfit::create([
            'uuid' => (string) Str::uuid(),
            'package_uuid' => $package->uuid,
            'amount' => 10,
        ]);
    }

    // STANDARD + PRESENT are counted; ARCHIVE + STAKING are excluded.
    Livewire::test(DashboardIndex::class)
        ->assertViewHas('packagesCount', 2)
        ->assertViewHas('depositTotalAmount', fn (float $amount): bool => abs($amount - 5000.0) < 0.01)
        ->assertViewHas('yieldTotal', fn (float $amount): bool => abs($amount - 20.0) < 0.01)
        ->assertViewHas('yieldWeek', fn (float $amount): bool => abs($amount - 20.0) < 0.01);
});

it('reconciles depositTotalAmount and packagesCount with the ITC Packages tab over a mixed portfolio', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // STANDARD: body 1000 + 50 partnerTransfer + 100 reinvestToBody - 30 balanceWithdraw = 1120,
    // + 200 active reinvest. The 80 withdrawn reinvest must NOT count.
    $standard = makePackage($user, PackageTypeEnum::STANDARD, 1000);
    $partnerTransferTrx = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::PARTNER_TO_PACKAGE,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'amount' => 50,
        'accepted_at' => now(),
    ]);
    PackagePartnerTransfer::create([
        'uuid' => $partnerTransferTrx->uuid,
        'package_uuid' => $standard->uuid,
    ]);
    ReinvestToPackageBody::create([
        'uuid' => (string) Str::uuid(),
        'package_uuid' => $standard->uuid,
        'amount' => 100,
    ]);
    $withdrawTrx = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::WITHDRAW_PACKAGE_TO_BALANCE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 30,
        'accepted_at' => now(),
    ]);
    PackageBalanceWithdraw::create([
        'uuid' => $withdrawTrx->uuid,
        'package_uuid' => $standard->uuid,
    ]);
    addActiveReinvest($standard, 200);
    addWithdrawnReinvest($standard, 80);

    // PRESENT (no zeroing): body 4000 + 150 active reinvest = 4150.
    $present = makePackage($user, PackageTypeEnum::PRESENT, 4000);
    addActiveReinvest($present, 150);

    // PRESENT + zeroing: body forced to 0, but 90 active reinvest still counts.
    $presentZeroed = makePackage($user, PackageTypeEnum::PRESENT, 5000);
    PackageZeroing::create([
        'package_uuid' => $presentZeroed->uuid,
        'transaction_uuid' => (string) Str::uuid(),
    ]);
    addActiveReinvest($presentZeroed, 90);

    // Excluded entirely.
    makePackage($user, PackageTypeEnum::ARCHIVE, 2000);
    makePackage($user, PackageTypeEnum::STAKING, 3000);

    // 1320 (STANDARD) + 4150 (PRESENT) + 90 (zeroed PRESENT) = 5560.
    $expected = itcTabDepositTotal($user);
    expect($expected)->toBeGreaterThan(0.0);

    Livewire::test(DashboardIndex::class)
        ->assertViewHas('packagesCount', 3)
        ->assertViewHas('depositTotalAmount', fn (float $amount): bool => abs($amount - $expected) < 0.01)
        ->assertViewHas('depositTotalAmount', fn (float $amount): bool => abs($amount - 5560.0) < 0.01);
});

it('reports gross partner accrual for the period, matching the Partner Program page, not the net balance delta', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Accrued 120 within the last week...
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'trx_type' => TrxTypeEnum::START_BONUS_ACCRUAL,
        'amount' => 120,
        'accepted_at' => now()->subDays(2),
    ]);

    // ...then immediately transferred out to the main balance within the same window.
    Transaction::factory()->create([
        'user_id' => $user->id,
        'balance_type' => BalanceTypeEnum::PARTNER,
        'trx_type' => TrxTypeEnum::PARTNER_TO_MAIN_SELF_MIRROR,
        'amount' => 120,
        'accepted_at' => now()->subDay(),
    ]);

    // Net balance delta would be 0 (this was the reported bug). Gross accrual must be 120.
    Livewire::test(DashboardIndex::class)
        ->assertViewHas('weekStats', fn (array $stats): bool => abs($stats['delta'] - 120.0) < 0.01)
        ->assertViewHas('monthStats', fn (array $stats): bool => abs($stats['delta'] - 120.0) < 0.01);
});

it('does not trigger an N+1 query for the transaction relation when computing depositTotalAmount', function () {
    // Two separate users with a different number of packages, so the render-time
    // query count can be compared without package-creation side effects (observers
    // updating user_summary, etc.) polluting the measurement window.
    $userWithFewPackages = User::factory()->create();
    makePackage($userWithFewPackages, PackageTypeEnum::STANDARD, 100);

    $userWithManyPackages = User::factory()->create();

    foreach ([100, 200, 300, 400, 500] as $body) {
        makePackage($userWithManyPackages, PackageTypeEnum::STANDARD, $body);
    }

    $this->actingAs($userWithFewPackages);
    DB::connection()->enableQueryLog();
    Livewire::test(DashboardIndex::class);
    $queryCountFewPackages = count(DB::connection()->getQueryLog());
    DB::connection()->flushQueryLog();

    $this->actingAs($userWithManyPackages);
    Livewire::test(DashboardIndex::class);
    $queryCountManyPackages = count(DB::connection()->getQueryLog());
    DB::connection()->disableQueryLog();

    // A lazy-loaded `transaction` relation per package (the N+1 this fix avoids) would
    // make the 5-package render issue noticeably more queries than the 1-package render.
    expect($queryCountManyPackages)->toBe($queryCountFewPackages);
});
