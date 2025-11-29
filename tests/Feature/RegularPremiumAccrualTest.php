<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\PartnerClosure;
use App\Models\PartnerLevelPercent;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLevelPercentOverride;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('начисляет регулярную премию по активным пакетам и исключает архивные', function () {
    $upline = User::factory()->create(['rank' => 3]);
    $ref = User::factory()->create();

    DB::table('partner_levels')->insert([
        'id' => 3,
        'level' => 3,
        'name' => 'R3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PartnerLevelPercent::query()->create([
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::REGULAR,
        'line' => 1,
        'percent' => 5.00,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $upline->id,
        'descendant_id' => $ref->id,
        'depth' => 1,
    ]);

    // Active package with profits
    $active = ItcPackage::factory()->create([
        'type' => PackageTypeEnum::STANDARD,
    ]);
    Transaction::query()->create([
        'uuid' => $active->uuid,
        'user_id' => $ref->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 100.00,
        'accepted_at' => now()->subDays(1),
    ]);
    PackageProfit::query()->create([
        'uuid' => 'PP-' . Str::random(10),
        'package_uuid' => $active->uuid,
        'amount' => 20.00,
        'created_at' => now()->subDays(1),
    ]);

    // Archived package with profits in period (must be ignored)
    $arch = ItcPackage::factory()->create([
        'type' => PackageTypeEnum::ARCHIVE,
        'closed_at' => now()->subDays(2),
    ]);
    Transaction::query()->create([
        'uuid' => $arch->uuid,
        'user_id' => $ref->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 100.00,
        'accepted_at' => now()->subDays(10),
    ]);
    PackageProfit::query()->create([
        'uuid' => 'PP-' . Str::random(10),
        'package_uuid' => $arch->uuid,
        'amount' => 50.00,
        'created_at' => now()->subDays(1),
    ]);

    Artisan::call('regular-premium:accrual', ['--no-interaction' => true]);

    // Expect 5% of only active profits (20.00) = 1.00
    $this->assertDatabaseHas('transactions', [
        'user_id' => $upline->id,
        'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL->value,
        'balance_type' => BalanceTypeEnum::REGULAR_PREMIUM->value,
        'amount' => 1.00,
    ]);
});

it('процент регулярной премии зависит от ранга', function () {
    $upline = User::factory()->create(['rank' => 2]);
    $ref = User::factory()->create();

    DB::table('partner_levels')->insert([
        'id' => 2,
        'level' => 2,
        'name' => 'R2',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PartnerLevelPercent::query()->create([
        'partner_level_id' => 2,
        'bonus_type' => PartnerRewardTypeEnum::REGULAR,
        'line' => 1,
        'percent' => 3.50,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $upline->id,
        'descendant_id' => $ref->id,
        'depth' => 1,
    ]);

    $pkg = ItcPackage::factory()->create();
    Transaction::query()->create([
        'uuid' => $pkg->uuid,
        'user_id' => $ref->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 200.00,
        'accepted_at' => now()->subDays(1),
    ]);
    PackageProfit::query()->create([
        'uuid' => 'PP-' . Str::random(10),
        'package_uuid' => $pkg->uuid,
        'amount' => 10.00,
        'created_at' => now()->subDays(1),
    ]);

    Artisan::call('regular-premium:accrual', ['--no-interaction' => true]);

    // 3.5% of 10 = 0.35
    $this->assertDatabaseHas('transactions', [
        'user_id' => $upline->id,
        'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL->value,
        'balance_type' => BalanceTypeEnum::REGULAR_PREMIUM->value,
        'amount' => 0.35,
    ]);
});

it('учитывает реинвесты в базе расчёта регулярной премии', function () {
    $upline = User::factory()->create(['rank' => 3]);
    $ref = User::factory()->create();

    DB::table('partner_levels')->insert([
        'id' => 3,
        'level' => 3,
        'name' => 'R3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PartnerLevelPercent::query()->create([
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::REGULAR,
        'line' => 1,
        'percent' => 10.00,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $upline->id,
        'descendant_id' => $ref->id,
        'depth' => 1,
    ]);

    $pkg = ItcPackage::factory()->create();
    Transaction::query()->create([
        'uuid' => $pkg->uuid,
        'user_id' => $ref->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 100.00,
        'accepted_at' => now()->subDays(1),
    ]);

    // Dividends 10 + reinvest 15 => net base 25
    PackageProfit::query()->create([
        'uuid' => 'PP-' . Str::random(10),
        'package_uuid' => $pkg->uuid,
        'amount' => 10.00,
        'created_at' => now()->subDays(1),
    ]);
    PackageProfitReinvest::query()->create([
        'uuid' => 'PR-' . Str::random(10),
        'package_uuid' => $pkg->uuid,
        'amount' => 15.00,
        'created_at' => now()->subDays(1),
    ]);

    Artisan::call('regular-premium:accrual', ['--no-interaction' => true]);

    // 10% of (10 + 15) = 2.5
    $this->assertDatabaseHas('transactions', [
        'user_id' => $upline->id,
        'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL->value,
        'balance_type' => BalanceTypeEnum::REGULAR_PREMIUM->value,
        'amount' => 2.50,
    ]);
});

it('использует обновлённые глобальные проценты после изменения в админке', function () {
    $upline = User::factory()->create(['rank' => 3]);
    $ref = User::factory()->create();

    DB::table('partner_levels')->insert([
        'id' => 3,
        'level' => 3,
        'name' => 'R3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Изначальный глобальный процент: 5%
    PartnerLevelPercent::query()->create([
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::REGULAR,
        'line' => 1,
        'percent' => 5.00,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $upline->id,
        'descendant_id' => $ref->id,
        'depth' => 1,
    ]);

    $pkg = ItcPackage::factory()->create();
    Transaction::query()->create([
        'uuid' => $pkg->uuid,
        'user_id' => $ref->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 100.00,
        'accepted_at' => now()->subDays(1),
    ]);
    PackageProfit::query()->create([
        'uuid' => 'PP-' . Str::random(10),
        'package_uuid' => $pkg->uuid,
        'amount' => 100.00,
        'created_at' => now()->subDays(1),
    ]);

    // Первое начисление с изначальным процентом 5% = 5.00
    Artisan::call('regular-premium:accrual', ['--no-interaction' => true]);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $upline->id,
        'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL->value,
        'amount' => 5.00,
    ]);

    // Удаляем старое начисление для пересчёта
    Transaction::where('user_id', $upline->id)
        ->where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
        ->delete();

    // Имитируем изменение процента в админке: обновляем глобальный процент до 7%
    PartnerLevelPercent::where([
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::REGULAR,
        'line' => 1,
    ])->update(['percent' => 7.00]);

    // Пересчёт с новым процентом 7% = 7.00
    Artisan::call('regular-premium:accrual', ['--no-interaction' => true]);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $upline->id,
        'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL->value,
        'balance_type' => BalanceTypeEnum::REGULAR_PREMIUM->value,
        'amount' => 7.00,
    ]);
});

it('использует персональный оверрайд процента вместо глобального', function () {
    $upline = User::factory()->create(['rank' => 3]);
    $ref = User::factory()->create();

    DB::table('partner_levels')->insert([
        'id' => 3,
        'level' => 3,
        'name' => 'R3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Глобальный процент: 5%
    PartnerLevelPercent::query()->create([
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::REGULAR,
        'line' => 1,
        'percent' => 5.00,
    ]);

    // Персональный оверрайд для пользователя: 8%
    UserLevelPercentOverride::query()->create([
        'user_id' => $upline->id,
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::REGULAR,
        'line' => 1,
        'percent' => 8.00,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $upline->id,
        'descendant_id' => $ref->id,
        'depth' => 1,
    ]);

    $pkg = ItcPackage::factory()->create();
    Transaction::query()->create([
        'uuid' => $pkg->uuid,
        'user_id' => $ref->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => BalanceTypeEnum::MAIN,
        'amount' => 100.00,
        'accepted_at' => now()->subDays(1),
    ]);
    PackageProfit::query()->create([
        'uuid' => 'PP-' . Str::random(10),
        'package_uuid' => $pkg->uuid,
        'amount' => 100.00,
        'created_at' => now()->subDays(1),
    ]);

    Artisan::call('regular-premium:accrual', ['--no-interaction' => true]);

    // Должен использоваться персональный оверрайд 8%, а не глобальный 5%
    // 8% of 100 = 8.00
    $this->assertDatabaseHas('transactions', [
        'user_id' => $upline->id,
        'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL->value,
        'balance_type' => BalanceTypeEnum::REGULAR_PREMIUM->value,
        'amount' => 8.00,
    ]);

    // Проверяем что глобальный процент остался без изменений
    $this->assertDatabaseHas('partner_level_percents', [
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::REGULAR->value,
        'line' => 1,
        'percent' => 5.00,
    ]);
});
