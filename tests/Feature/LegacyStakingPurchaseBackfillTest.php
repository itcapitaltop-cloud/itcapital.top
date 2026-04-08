<?php

declare(strict_types=1);

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\StakingPurchase;
use App\Models\TokenRate;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Package\Staking\StakingPerformanceService;
use App\Settings\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

it('normalizes legacy staking purchases in performance calculations', function () {
    Carbon::setTestNow('2026-03-29 12:00:00');

    $settings = app(GeneralSetting::class);
    $settings->exchange_rate_itc = 0.12;
    $settings->save();

    TokenRate::query()->delete();
    TokenRate::query()->create([
        'effective_from' => '2026-03-27',
        'rate' => 0.10,
    ]);
    TokenRate::query()->create([
        'effective_from' => '2026-03-28',
        'rate' => 0.12,
    ]);

    $user = User::factory()->create();
    $package = ItcPackage::factory()->create([
        'uuid' => 'ITC-LEGACY-STAKING',
        'type' => PackageTypeEnum::STAKING,
        'month_profit_percent' => 2,
    ]);

    Transaction::factory()->create([
        'uuid' => $package->uuid,
        'user_id' => $user->id,
        'amount' => 85,
        'balance_type' => BalanceTypeEnum::MAIN,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => '2026-01-19 14:27:52',
    ]);

    StakingPurchase::query()->create([
        'itc_package_id' => $package->id,
        'user_id' => $user->id,
        'amount_usd' => 85,
        'token_amount' => 85,
        'purchase_rate' => 1,
        'purchased_at' => '2026-01-19 14:27:52',
    ]);

    $package->stakingTransactionAccruals()->create([
        'user_id' => $user->id,
        'type' => StakingTransactionAccrualEnum::Profit,
        'amount' => 1.7,
    ]);

    $performance = app(StakingPerformanceService::class)->forPackage(
        $package->fresh(['transaction', 'stakingPurchases', 'stakingTransactionAccruals'])
    );

    expect($performance['invested_usd'])->toBe(8.5)
        ->and($performance['purchased_tokens'])->toBe(85.0)
        ->and($performance['yield_tokens'])->toBe(1.7)
        ->and($performance['current_value_usd'])->toBe(10.4)
        ->and($performance['unrealized_pnl_usd'])->toBe(1.7)
        ->and($performance['average_purchase_rate'])->toBe(0.1);
});

it('fixes legacy staking purchases in the database migration', function () {
    Carbon::setTestNow('2026-03-29 12:00:00');

    DB::table('token_rates')->delete();
    DB::table('token_rates')->insert([
        [
            'effective_from' => '2026-03-27',
            'rate' => 0.10,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'effective_from' => '2026-03-28',
            'rate' => 0.12,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $user = User::factory()->create();
    $package = ItcPackage::factory()->create([
        'uuid' => 'ITC-LEGACY-MIGRATION',
        'type' => PackageTypeEnum::STAKING,
    ]);

    StakingPurchase::query()->create([
        'itc_package_id' => $package->id,
        'user_id' => $user->id,
        'amount_usd' => 85,
        'token_amount' => 85,
        'purchase_rate' => 1,
        'purchased_at' => '2026-01-19 14:27:52',
    ]);

    $migration = require database_path('migrations/2026_03_29_194659_fix_legacy_staking_purchase_backfill.php');
    $migration->up();

    $purchase = StakingPurchase::query()->firstOrFail();

    expect((float) $purchase->amount_usd)->toBe(8.5)
        ->and((float) $purchase->token_amount)->toBe(85.0)
        ->and((float) $purchase->purchase_rate)->toBe(0.1);
});

afterEach(function () {
    Carbon::setTestNow();
});
