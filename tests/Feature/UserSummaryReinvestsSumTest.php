<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitReinvestWithdraw;
use App\Models\Transaction;
use App\Models\User;
use App\Services\User\UserSummaryService;
use Illuminate\Support\Str;

/**
 * user_summary.reinvests_sum must mirror what the user sees in their cabinet:
 * total reinvested minus withdrawals, excluding soft-deleted reinvests.
 *
 * @see \App\Services\User\UserSummaryService::reinvestsSum
 */
it('исключает удалённые реинвесты из reinvests_sum', function () {
    $user = User::factory()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 1000,
        'accepted_at' => now(),
    ]);

    $package = ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => PackageTypeEnum::STANDARD,
    ]);

    // Активный реинвест — учитывается.
    PackageProfitReinvest::query()->create([
        'uuid' => 'PPR-' . Str::random(10),
        'package_uuid' => $package->uuid,
        'amount' => '100.00',
        'matured_at' => now()->addDays(180),
    ]);

    // Списанный реинвест — учитывается в «reinvested», но вычитается как «withdrawn» → нетто 0.
    $withdrawn = PackageProfitReinvest::query()->create([
        'uuid' => 'PPR-' . Str::random(10),
        'package_uuid' => $package->uuid,
        'amount' => '40.00',
        'matured_at' => now()->addDays(180),
    ]);
    PackageProfitReinvestWithdraw::query()->create([
        'uuid' => 'WPRP-' . Str::random(10),
        'reinvest_uuid' => $withdrawn->uuid,
    ]);

    // Удалённый реинвест — НЕ должен попадать в сумму.
    PackageProfitReinvest::query()->create([
        'uuid' => 'PPR-' . Str::random(10),
        'package_uuid' => $package->uuid,
        'amount' => '25.00',
        'matured_at' => now()->addDays(180),
    ])->delete();

    $reinvestsSum = app(UserSummaryService::class)->computeFor($user->id)['reinvests_sum'];

    // 100 (активный) + 40 − 40 (списанный) + 0 (удалённый исключён) = 100.
    expect((float) $reinvestsSum)->toEqual(100.00);
});
