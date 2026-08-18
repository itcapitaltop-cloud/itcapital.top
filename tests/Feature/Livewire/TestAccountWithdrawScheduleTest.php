<?php

use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Account\Finance\Finance;
use App\Models\PaymentSource;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

it('allows a test account to create a withdraw outside Sunday', function (): void {
    Carbon::setTestNow('2026-08-17 12:00:00'); // Monday
    PaymentSource::query()->firstOrCreate(['source' => 'crypto']);
    $user = User::factory()->create(['is_test' => true]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => '100.00',
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Finance::class)
        ->assertDontSee(__('livewire_finance_withdraw_schedule_warning'))
        ->set('withdrawForm.withdrawSource', 'crypto')
        ->set('withdrawForm.withdrawAmount', '25')
        ->set('withdrawForm.address', 'test-wallet')
        ->call('createWithdraw')
        ->assertHasNoErrors()
        ->assertDispatched('withdraw-created');

    expect(Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::WITHDRAW)
        ->whereNull('accepted_at')
        ->whereNull('rejected_at')
        ->value('amount'))->toBe('25.00');
});

it('keeps the Sunday restriction for a regular account', function (): void {
    Carbon::setTestNow('2026-08-17 12:00:00'); // Monday
    $user = User::factory()->create(['is_test' => false]);

    Livewire::actingAs($user)
        ->test(Finance::class)
        ->assertSee(__('livewire_finance_withdraw_schedule_warning'))
        ->call('createWithdraw')
        ->assertDispatched('new-system-notification', type: 'warning')
        ->assertNotDispatched('withdraw-created');

    expect(Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::WITHDRAW)
        ->exists())->toBeFalse();
});
