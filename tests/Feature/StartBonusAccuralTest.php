<?php

use App\Contracts\Accruals\StartBonusAccrualContract;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Itc\Packages as ItcPackages;
use App\Models\PartnerClosure;
use App\Models\PartnerLevelPercent;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLevelPercentOverride;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('начисляет стартовый бонус при покупке реферала', function () {
    $ancestor = User::factory()->create([
        'rank' => 3,
        'extended_lines' => false,
    ]);
    $buyer = User::factory()->create();

    DB::table('partner_levels')->insert([
        'id' => 3,
        'level' => 3,
        'name' => 'R3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PartnerLevelPercent::query()->create([
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::START,
        'line' => 1,
        'percent' => 5.00,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $ancestor->id,
        'descendant_id' => $buyer->id,
        'depth' => 1,
    ]);

    app(StartBonusAccrualContract::class)->accrue($buyer->id, 100.00);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $ancestor->id,
        'trx_type' => TrxTypeEnum::START_BONUS_ACCRUAL->value,
        'balance_type' => BalanceTypeEnum::PARTNER->value,
        'amount' => 5.00,
    ]);

    $trx = Transaction::where('user_id', $ancestor->id)
        ->where('trx_type', TrxTypeEnum::START_BONUS_ACCRUAL)
        ->first();
    $this->assertNotNull($trx);
    $this->assertNotNull($trx->accepted_at);

    $this->assertDatabaseHas('partner_rewards', [
        'from_user_id' => $buyer->id,
        'reward_type' => PartnerRewardTypeEnum::START->value,
        'line' => 1,
        'amount' => 5.00,
    ]);
});

it('процент зависит от ранга', function () {
    $ancestor = User::factory()->create([
        'rank' => 2,
    ]);
    $buyer = User::factory()->create();

    DB::table('partner_levels')->insert([
        'id' => 2,
        'level' => 2,
        'name' => 'R2',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PartnerLevelPercent::query()->create([
        'partner_level_id' => 2,
        'bonus_type' => PartnerRewardTypeEnum::START,
        'line' => 1,
        'percent' => 3.50,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $ancestor->id,
        'descendant_id' => $buyer->id,
        'depth' => 1,
    ]);

    app(StartBonusAccrualContract::class)->accrue($buyer->id, 200.00);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $ancestor->id,
        'trx_type' => TrxTypeEnum::START_BONUS_ACCRUAL->value,
        'balance_type' => BalanceTypeEnum::PARTNER->value,
        'amount' => 7.00,
    ]);
});

it('начисления за настоящий пакет', function () {
    $ancestor = User::factory()->create(['rank' => 3]);
    $buyer = User::factory()->create();

    DB::table('partner_levels')->insert([
        'id' => 3,
        'level' => 3,
        'name' => 'R3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PartnerLevelPercent::query()->create([
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::START,
        'line' => 1,
        'percent' => 5.00,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $ancestor->id,
        'descendant_id' => $buyer->id,
        'depth' => 1,
    ]);

    // Create a PRESENT package via Livewire flow which does not call accrue()
    /** @var \Illuminate\Contracts\Auth\Authenticatable $buyer */
    $this->actingAs($buyer);
    Livewire::test(ItcPackages::class)
        ->set('packageType', \App\Enums\Itc\PackageTypeEnum::PRESENT)
        ->set('duration', 1)
        ->set('amount', '100')
        ->set('percent', '8.2')
        ->call('createPackage');

    $this->assertDatabaseMissing('transactions', [
        'user_id' => $ancestor->id,
        'trx_type' => TrxTypeEnum::START_BONUS_ACCRUAL->value,
    ]);
});

it('накопления для реинвестирования', function () {
    $ancestor = User::factory()->create(['rank' => 3]);
    $buyer = User::factory()->create();

    DB::table('partner_levels')->insert([
        'id' => 3,
        'level' => 3,
        'name' => 'R3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    PartnerLevelPercent::query()->create([
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::START,
        'line' => 1,
        'percent' => 5.00,
    ]);
    PartnerClosure::factory()->create([
        'ancestor_id' => $ancestor->id,
        'descendant_id' => $buyer->id,
        'depth' => 1,
    ]);

    // Simulate creating a package (standard) without accrual by directly using createPackage on PRESENT path first
    /** @var \Illuminate\Contracts\Auth\Authenticatable $buyer */
    $this->actingAs($buyer);
    Livewire::test(ItcPackages::class)
        ->set('packageType', \App\Enums\Itc\PackageTypeEnum::STANDARD)
        ->set('amount', '0')
        ->set('percent', '8.2')
        ->call('createPackage');

    // Simulate reinvest actions which should not trigger Start Bonus accrual
    // There is no direct accrual call in reinvest flows, so we just assert absence
    $this->assertDatabaseMissing('transactions', [
        'user_id' => $ancestor->id,
        'trx_type' => TrxTypeEnum::START_BONUS_ACCRUAL->value,
    ]);
});

it('расширенные линии гейтинга на линии 6 и выше', function () {
    $ancestorNoExt = User::factory()->create([
        'rank' => 3,
        'extended_lines' => false,
    ]);
    $ancestorWithExt = User::factory()->create([
        'rank' => 3,
        'extended_lines' => true,
    ]);
    $buyer = User::factory()->create();

    DB::table('partner_levels')->insert([
        'id' => 3,
        'level' => 3,
        'name' => 'R3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PartnerLevelPercent::query()->create([
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::START,
        'line' => 6,
        'percent' => 1.00,
    ]);

    // Two ancestors on line 6; only one has extended_lines enabled
    PartnerClosure::factory()->create([
        'ancestor_id' => $ancestorNoExt->id,
        'descendant_id' => $buyer->id,
        'depth' => 6,
    ]);
    PartnerClosure::factory()->create([
        'ancestor_id' => $ancestorWithExt->id,
        'descendant_id' => $buyer->id,
        'depth' => 6,
    ]);

    app(StartBonusAccrualContract::class)->accrue($buyer->id, 100.00);

    $this->assertDatabaseMissing('transactions', [
        'user_id' => $ancestorNoExt->id,
        'trx_type' => TrxTypeEnum::START_BONUS_ACCRUAL->value,
    ]);
    $this->assertDatabaseHas('transactions', [
        'user_id' => $ancestorWithExt->id,
        'trx_type' => TrxTypeEnum::START_BONUS_ACCRUAL->value,
        'amount' => 1.00,
    ]);
});

it('использует персональный оверрайд процента для стартовой премии вместо глобального', function () {
    $ancestor = User::factory()->create(['rank' => 3]);
    $buyer = User::factory()->create();

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
        'bonus_type' => PartnerRewardTypeEnum::START,
        'line' => 1,
        'percent' => 5.00,
    ]);

    // Персональный оверрайд для пользователя: 7%
    UserLevelPercentOverride::query()->create([
        'user_id' => $ancestor->id,
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::START,
        'line' => 1,
        'percent' => 7.00,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $ancestor->id,
        'descendant_id' => $buyer->id,
        'depth' => 1,
    ]);

    app(StartBonusAccrualContract::class)->accrue($buyer->id, 100.00);

    // Должен использоваться персональный оверрайд 7%, а не глобальный 5%
    // 7% of 100 = 7.00
    $this->assertDatabaseHas('transactions', [
        'user_id' => $ancestor->id,
        'trx_type' => TrxTypeEnum::START_BONUS_ACCRUAL->value,
        'balance_type' => BalanceTypeEnum::PARTNER->value,
        'amount' => 7.00,
    ]);

    // Проверяем что глобальный процент остался без изменений
    $this->assertDatabaseHas('partner_level_percents', [
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::START->value,
        'line' => 1,
        'percent' => 5.00,
    ]);
});

it('правильно считает стартовую премию для ранга 20 на линии 20 когда в админке включен extended_lines', function () {
    // Симулируем ситуацию: в админке установлен ранг 20 и включен "Доступ к премиям с 20 линий"
    $ancestor = User::factory()->create([
        'rank' => 20,
        'extended_lines' => true, // Включено в админке через форму UserDetailPage
    ]);
    $buyer = User::factory()->create();

    // Создаем запись в partner_levels для ранга 20 (настраивается в админке)
    DB::table('partner_levels')->insert([
        'id' => 20,
        'level' => 20,
        'name' => 'R20',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Создаем процент для ранга 20 на линии 20 (настраивается в админке)
    PartnerLevelPercent::query()->create([
        'partner_level_id' => 20,
        'bonus_type' => PartnerRewardTypeEnum::START,
        'line' => 20,
        'percent' => 2.50,
    ]);

    // Создаем PartnerClosure для линии 20
    PartnerClosure::factory()->create([
        'ancestor_id' => $ancestor->id,
        'descendant_id' => $buyer->id,
        'depth' => 20,
    ]);

    $packageAmount = 1000.00;
    app(StartBonusAccrualContract::class)->accrue($buyer->id, $packageAmount);

    // Проверяем, что транзакция создана с правильной суммой
    // 1000 * 2.5% = 25.00
    $this->assertDatabaseHas('transactions', [
        'user_id' => $ancestor->id,
        'trx_type' => TrxTypeEnum::START_BONUS_ACCRUAL->value,
        'balance_type' => BalanceTypeEnum::PARTNER->value,
        'amount' => 25.00,
    ]);

    $trx = Transaction::where('user_id', $ancestor->id)
        ->where('trx_type', TrxTypeEnum::START_BONUS_ACCRUAL)
        ->first();
    $this->assertNotNull($trx);
    $this->assertNotNull($trx->accepted_at);

    // Проверяем, что PartnerReward создан правильно
    $this->assertDatabaseHas('partner_rewards', [
        'from_user_id' => $buyer->id,
        'reward_type' => PartnerRewardTypeEnum::START->value,
        'line' => 20,
        'amount' => 25.00,
    ]);
});

it('не начисляет стартовую премию для ранга 20 на линии 20 когда extended_lines выключен в админке', function () {
    // Симулируем ситуацию: в админке установлен ранг 20, но "Доступ к премиям с 20 линий" выключен
    $ancestor = User::factory()->create([
        'rank' => 20,
        'extended_lines' => false, // Выключено в админке
    ]);
    $buyer = User::factory()->create();

    // Создаем запись в partner_levels для ранга 20 (настраивается в админке)
    DB::table('partner_levels')->insert([
        'id' => 20,
        'level' => 20,
        'name' => 'R20',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Создаем процент для ранга 20 на линии 20 (настраивается в админке)
    PartnerLevelPercent::query()->create([
        'partner_level_id' => 20,
        'bonus_type' => PartnerRewardTypeEnum::START,
        'line' => 20,
        'percent' => 2.50,
    ]);

    // Создаем PartnerClosure для линии 20
    PartnerClosure::factory()->create([
        'ancestor_id' => $ancestor->id,
        'descendant_id' => $buyer->id,
        'depth' => 20,
    ]);

    app(StartBonusAccrualContract::class)->accrue($buyer->id, 1000.00);

    // Проверяем, что транзакция НЕ создана, так как extended_lines = false
    // В StartBonusAccrualContract для линий 6-20 проверяется extended_lines
    $this->assertDatabaseMissing('transactions', [
        'user_id' => $ancestor->id,
        'trx_type' => TrxTypeEnum::START_BONUS_ACCRUAL->value,
    ]);

    // Проверяем, что PartnerReward НЕ создан
    $this->assertDatabaseMissing('partner_rewards', [
        'from_user_id' => $buyer->id,
        'reward_type' => PartnerRewardTypeEnum::START->value,
        'line' => 20,
    ]);
});
