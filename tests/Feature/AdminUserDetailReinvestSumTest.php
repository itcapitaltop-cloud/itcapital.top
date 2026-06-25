<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitReinvestWithdraw;
use App\Models\PackageProfitWithdraw;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The admin user-detail packages table must show the same reinvest figure the user
 * sees in their cabinet: only active reinvests (excluding soft-deleted and withdrawn).
 *
 * @see \App\MoonShine\Pages\User\UserDetailPage
 * @see resources/views/components/account/itc/package.blade.php (reinvest_profits_sum_amount)
 */
it('считает сумму реинвеста по пакету как в ЛК: без удалённых и без списанных', function () {
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

    // Активный реинвест — должен учитываться.
    PackageProfitReinvest::query()->create([
        'uuid' => 'PPR-' . Str::random(10),
        'package_uuid' => $package->uuid,
        'amount' => '100.00',
        'matured_at' => now()->addDays(180),
    ]);

    // Списанный реинвест — НЕ должен учитываться (есть запись о выводе).
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

    // Удалённый (soft-delete) реинвест — НЕ должен учитываться.
    PackageProfitReinvest::query()->create([
        'uuid' => 'PPR-' . Str::random(10),
        'package_uuid' => $package->uuid,
        'amount' => '25.00',
        'matured_at' => now()->addDays(180),
    ])->delete();

    // Запрос, которым админская карточка (UserDetailPage) теперь питает колонку «Сумма реинвеста».
    $adminSum = ItcPackage::query()
        ->whereKey($package->getKey())
        ->withSum([
            'reinvestProfits as reinvest_profits_sum_amount' => fn ($q) => $q->whereDoesntHave('withdraw'),
        ], 'amount')
        ->sole()
        ->reinvest_profits_sum_amount;

    // Эталон ЛК.
    $cabinetSum = ItcPackage::query()
        ->userPackagesWithFinancials($user->id)
        ->whereKey($package->getKey())
        ->sole()
        ->reinvest_profits_sum_amount;

    expect((float) $adminSum)->toEqual(100.00)
        ->and((float) $cabinetSum)->toEqual(100.00)
        ->and((float) $adminSum)->toEqual((float) $cabinetSum);
});

/**
 * The admin "Неснятые дивиденды" column must match the cabinet's available profit:
 * accrued profits − active reinvests − withdrawn profits. The admin query must eager-load
 * reinvest_profits_all_sum_amount, otherwise getCurrentProfitAmount() skips the reinvest
 * subtraction and overstates the figure.
 *
 * @see \App\MoonShine\Pages\User\UserDetailPage
 * @see \App\Models\ItcPackage::getCurrentProfitAmount()
 */
it('считает неснятые дивиденды в админке как в ЛК: начислено минус реинвест минус вывод', function () {
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

    // Всего начислено дивидендов: 300.
    PackageProfit::query()->create([
        'uuid' => 'PP-' . Str::random(10),
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);

    // Реинвестировано (активный реинвест, ушёл в тело): 100.
    PackageProfitReinvest::query()->create([
        'uuid' => 'PPR-' . Str::random(10),
        'package_uuid' => $package->uuid,
        'amount' => '100.00',
        'matured_at' => now()->addDays(180),
    ]);

    // Выведено дивидендов: 50 (транзакция вывода с тем же uuid).
    $withdrawUuid = 'PPW-' . Str::random(10);
    PackageProfitWithdraw::query()->create([
        'uuid' => $withdrawUuid,
        'package_uuid' => $package->uuid,
    ]);
    Transaction::factory()->create([
        'uuid' => $withdrawUuid,
        'user_id' => $user->id,
        'amount' => 50,
    ]);

    // Запрос, которым админская карточка (UserDetailPage) питает колонку «Неснятые дивиденды».
    $adminPackage = ItcPackage::query()
        ->whereKey($package->getKey())
        ->withSum([
            'reinvestProfits as reinvest_profits_sum_amount' => fn ($q) => $q->whereDoesntHave('withdraw'),
        ], 'amount')
        ->withSum([
            'reinvestProfitsAll as reinvest_profits_all_sum_amount' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)')),
        ], 'amount')
        ->withSum([
            'profits as profits_sum_amount' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)')),
        ], 'amount')
        ->withSum('withdrawProfitsTransactions', 'amount')
        ->sole();

    $cabinetPackage = ItcPackage::query()
        ->userPackagesWithFinancials($user->id)
        ->whereKey($package->getKey())
        ->sole();

    expect($adminPackage->getCurrentProfitAmount()->toFloat())->toEqual(150.00)
        ->and($cabinetPackage->getCurrentProfitAmount()->toFloat())->toEqual(150.00);
});
