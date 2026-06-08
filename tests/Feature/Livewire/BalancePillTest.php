<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Dashboard\BalancePill;
use App\Models\ItcPackage;
use App\Models\Package\Staking\StakingTransactionAccrual;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

it('shows zero staking balance when the user has no staking package', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(BalancePill::class)
        ->assertViewHas('balanceStaking', 0.0);
});

it('derives the staking balance from the staking package body and accruals', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'amount' => 1000,
        'accepted_at' => now(),
    ]);

    $package = ItcPackage::factory()->create([
        'uuid' => $transaction->uuid,
        'type' => PackageTypeEnum::STAKING,
        'closed_at' => null,
    ]);

    StakingTransactionAccrual::query()->create([
        'itc_package_id' => $package->id,
        'user_id' => $user->id,
        'amount' => 250,
        'accrual_rate' => 0.1,
        'type' => StakingTransactionAccrualEnum::Profit,
    ]);

    Livewire::test(BalancePill::class)
        ->assertViewHas('balanceStaking', fn (float $balance): bool => abs($balance - 1250.0) < 0.01);
});
