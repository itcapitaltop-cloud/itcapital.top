<?php

use App\Models\Transaction;
use App\Models\User;

it('создание пользователя', function () {
    $user = User::factory()->create();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'rank' => 0,
    ]);
});

it('пользователь с достаточным депозитом получает ранг 0', function () {
    $rank = \App\Models\PartnerRank::factory()->create();

    \App\Models\PartnerRankRequirement::factory()
        ->withDefaultRequirements()
        ->create(['partner_rank_id' => $rank->id]);

    $user = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
    ]);

    $rank = app(\App\Services\User\UserRankServices::class)->calculateRank($user);

    expect($transaction->user_id)->toBe($user->id)
        ->and($rank)->toBe(0);
});

it('пользователь с достаточным депозитом получает ранг 1', function () {
    $user = User::factory()->create();

    $rank = \App\Models\PartnerRank::factory()->create();

    \App\Models\PartnerRankRequirement::factory()
        ->withDefaultRequirements()
        ->create(['partner_rank_id' => $rank->id]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => \App\Enums\Transactions\TrxTypeEnum::BUY_PACKAGE,
        'amount' => 100,
        'accepted_at' => now(),
    ]);

    \App\Models\ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'closed_at' => null,
    ]);

    $service = app(\App\Services\User\UserRankServices::class);
    $calculatedRank = $service->calculateRank($user);

    expect($calculatedRank)->toBe(1);
});

it('пользователь с недостаточным депозитом не получает ранг', function () {
    $user = User::factory()->create();

    $rank = \App\Models\PartnerRank::factory()->create();

    \App\Models\PartnerRankRequirement::factory()
        ->withDefaultRequirements()
        ->create(['partner_rank_id' => $rank->id]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => \App\Enums\Transactions\TrxTypeEnum::BUY_PACKAGE,
        'amount' => 50,
        'accepted_at' => now(),
    ]);

    \App\Models\ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'closed_at' => null,
    ]);

    $service = app(\App\Services\User\UserRankServices::class);
    $calculatedRank = $service->calculateRank($user);

    expect($transaction->amount)->toBe('50.00')
        ->and($calculatedRank)->toBe(0);
});

it('проверка кумулятивных требований по линиям для ранга 2', function () {
    $sponsor = User::factory()->create();
    $partner1 = User::factory()->create();
    $partner2 = User::factory()->create();

    \App\Models\PartnerClosure::factory()->create(['ancestor_id' => $sponsor->id, 'descendant_id' => $partner1->id, 'depth' => 1]);
    \App\Models\PartnerClosure::factory()->create(['ancestor_id' => $sponsor->id, 'descendant_id' => $partner2->id, 'depth' => 1]);

    // Ранг 1: личный депозит 1000
    $rank1 = \App\Models\PartnerRank::factory()->create(['rank' => 1, 'bonus_usd' => 100]);
    \App\Models\PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $rank1->id,
        'line' => null,
        'personal_deposit' => 1000,
    ]);

    // Ранг 2: личный депозит 2000 + 1 линия 5000
    $rank2 = \App\Models\PartnerRank::factory()->create(['rank' => 2, 'bonus_usd' => 500]);
    \App\Models\PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $rank2->id,
        'line' => null,
        'personal_deposit' => 2000,
    ]);
    \App\Models\PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $rank2->id,
        'line' => 1,
        'required_turnover' => 5000,
    ]);

    // Депозит спонсора
    $trx = Transaction::factory()->create([
        'user_id' => $sponsor->id,
        'amount' => 2000,
        'trx_type' => \App\Enums\Transactions\TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now(),
    ]);
    \App\Models\ItcPackage::factory()->create(['uuid' => $trx->uuid]);

    // Депозиты партнеров
    $trx1 = Transaction::factory()->create([
        'user_id' => $partner1->id,
        'amount' => 3000,
        'trx_type' => \App\Enums\Transactions\TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now(),
    ]);
    \App\Models\ItcPackage::factory()->create(['uuid' => $trx1->uuid]);

    $trx2 = Transaction::factory()->create([
        'user_id' => $partner2->id,
        'amount' => 2000,
        'trx_type' => \App\Enums\Transactions\TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now(),
    ]);
    \App\Models\ItcPackage::factory()->create(['uuid' => $trx2->uuid]);

    $service = app(\App\Services\User\UserRankServices::class);
    $calculatedRank = $service->calculateRank($sponsor);

    expect($calculatedRank)->toBe(2);
});

it('обновление ранга пользователя с начислением бонуса', function () {
    $user = User::factory()->create(['rank' => 0]);

    $rank1 = \App\Models\PartnerRank::factory()->create(['rank' => 1, 'bonus_usd' => 100]);
    \App\Models\PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $rank1->id,
        'line' => null,
        'personal_deposit' => 1000,
    ]);

    $trx = Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => 1000,
        'trx_type' => \App\Enums\Transactions\TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now(),
    ]);
    \App\Models\ItcPackage::factory()->create(['uuid' => $trx->uuid]);

    $service = app(\App\Services\User\UserRankServices::class);
    $updated = $service->recalculateAndUpdateRank($user);

    expect($updated)->toBeTrue()
        ->and($user->fresh()->rank)->toBe(1);

    // Проверяем, что бонус начислен
    $bonusTransaction = Transaction::where('user_id', $user->id)
        ->where('trx_type', \App\Enums\Transactions\TrxTypeEnum::RANK_BONUS_ACCRUAL)
        ->firstOrFail();

    expect($bonusTransaction)->not->toBeNull()
        ->and($bonusTransaction->amount)->toBe('100.00');
});

it('обновление ранга без начисления бонуса', function () {
    $user = User::factory()->create(['rank' => 0]);

    $rank1 = \App\Models\PartnerRank::factory()->create(['rank' => 1, 'bonus_usd' => 100]);
    \App\Models\PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $rank1->id,
        'line' => null,
        'personal_deposit' => 1000,
    ]);

    $trx = Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => 1000,
        'trx_type' => \App\Enums\Transactions\TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now(),
    ]);
    \App\Models\ItcPackage::factory()->create(['uuid' => $trx->uuid]);

    $service = app(\App\Services\User\UserRankServices::class);
    $updated = $service->recalculateAndUpdateRank($user, withBonus: false);

    expect($updated)->toBeTrue()
        ->and($user->fresh()->rank)->toBe(1);

    // Проверяем, что бонус НЕ начислен
    $bonusTransaction = Transaction::where('user_id', $user->id)
        ->where('trx_type', \App\Enums\Transactions\TrxTypeEnum::RANK_BONUS_ACCRUAL)
        ->first();

    expect($bonusTransaction)->toBeNull();
});

it('пересчет ранга с учетом overridden_rank', function () {
    // Пользователь с переопределенным рангом
    $user = User::factory()->create([
        'rank' => 2,
        'overridden_rank' => true,
        'overridden_rank_from' => now()->subMonth(),
    ]);

    $rank2 = \App\Models\PartnerRank::factory()->create(['rank' => 2, 'bonus_usd' => 500]);
    \App\Models\PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $rank2->id,
        'line' => null,
        'personal_deposit' => 5000,
    ]);

    // Старый депозит (до даты override) - 2000
    $oldTrx = Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => 2000,
        'trx_type' => \App\Enums\Transactions\TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now()->subMonths(2),
    ]);
    \App\Models\ItcPackage::factory()->create(['uuid' => $oldTrx->uuid]);

    // Новый депозит (после override) - 1000
    $newTrx = Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => 1000,
        'trx_type' => \App\Enums\Transactions\TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now()->subDays(5),
    ]);
    \App\Models\ItcPackage::factory()->create(['uuid' => $newTrx->uuid]);

    $service = app(\App\Services\User\UserRankServices::class);
    $calculatedRank = $service->calculateRank($user);

    // Личный депозит = 5000 (минимум) + 1000 (новый) = 6000
    // Должен соответствовать рангу 2
    expect($calculatedRank)->toBe(2);
});
