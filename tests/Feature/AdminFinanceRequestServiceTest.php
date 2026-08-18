<?php

use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\LogActionTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\BusinessActivity;
use App\Models\PaymentSource;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Admin\FinanceRequestService;
use App\Services\User\UserBalanceCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

it('recalculates the balance when an active withdraw amount is edited', function (): void {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => '100.00',
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    $withdraw = Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => '80.00',
        'trx_type' => TrxTypeEnum::WITHDRAW,
        'balance_type' => BalanceTypeEnum::MAIN,
        'accepted_at' => null,
        'rejected_at' => null,
    ]);

    $service = app(FinanceRequestService::class);
    $balance = app(UserBalanceCalculator::class);

    expect((float) $balance->balanceFor($user->id, BalanceTypeEnum::MAIN))->toBe(20.0);

    $service->updateActiveAmount($withdraw->uuid, '40', TrxTypeEnum::WITHDRAW);
    expect((float) $balance->balanceFor($user->id, BalanceTypeEnum::MAIN))->toBe(60.0);

    $service->updateActiveAmount($withdraw->uuid, '130', TrxTypeEnum::WITHDRAW);
    expect((float) $balance->balanceFor($user->id, BalanceTypeEnum::MAIN))->toBe(-30.0);

    $returned = BusinessActivity::query()
        ->where('description', ActivityEventTypeEnum::WithdrawAmountDecreasedByAdmin->value)
        ->firstOrFail();
    $charged = BusinessActivity::query()
        ->where('description', ActivityEventTypeEnum::WithdrawAmountIncreasedByAdmin->value)
        ->firstOrFail();

    expect($returned->getExtraProperty('amount'))->toBe('40.00')
        ->and($returned->getExtraProperty('balance_difference'))->toBe('40.00')
        ->and($charged->getExtraProperty('amount'))->toBe('90.00')
        ->and($charged->getExtraProperty('balance_difference'))->toBe('-90.00')
        ->and(BusinessActivity::query()
            ->where('description', LogActionTypeEnum::UPDATE_WITHDRAW_AMOUNT->value)
            ->whereJsonContains('properties->feeds', ActivityFeedTypeEnum::UserDetailAdmin->value)
            ->count())->toBe(2)
        ->and(BusinessActivity::query()
            ->where('description', LogActionTypeEnum::UPDATE_WITHDRAW_AMOUNT->value)
            ->whereJsonContains('properties->feeds', ActivityFeedTypeEnum::GlobalAdmin->value)
            ->count())->toBe(2);
});

it('does not edit a completed finance request', function (): void {
    $transaction = Transaction::factory()->create([
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    expect(fn () => app(FinanceRequestService::class)->updateActiveAmount(
        $transaction->uuid,
        '500',
        TrxTypeEnum::DEPOSIT,
    ))->toThrow(ValidationException::class);

    expect($transaction->refresh()->amount)->not->toBe('500.00');
});

it('does not change the balance when an active deposit amount is edited', function (): void {
    $user = User::factory()->create();
    $deposit = Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => '100.00',
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
        'accepted_at' => null,
        'rejected_at' => null,
    ]);

    $service = app(FinanceRequestService::class);
    $balance = app(UserBalanceCalculator::class);

    expect((float) $balance->balanceFor($user->id, BalanceTypeEnum::MAIN))->toBe(0.0);

    $service->updateActiveAmount($deposit->uuid, '1000', TrxTypeEnum::DEPOSIT);

    expect($deposit->refresh()->amount)->toBe('1000.00')
        ->and((float) $balance->balanceFor($user->id, BalanceTypeEnum::MAIN))->toBe(0.0);

    $userLog = BusinessActivity::query()
        ->where('description', ActivityEventTypeEnum::DepositAmountChangedByAdmin->value)
        ->firstOrFail();

    expect($userLog->getExtraProperty('balance_difference'))->toBe('0.00')
        ->and(BusinessActivity::query()
            ->where('description', LogActionTypeEnum::UPDATE_DEPOSIT_AMOUNT->value)
            ->whereJsonContains('properties->feeds', ActivityFeedTypeEnum::GlobalAdmin->value)
            ->exists())->toBeTrue();
});

it('creates an active withdraw for a test account on any day and allows a negative balance', function (): void {
    Carbon::setTestNow('2026-08-17 12:00:00'); // Monday
    PaymentSource::query()->firstOrCreate(['source' => 'crypto']);
    $user = User::factory()->create(['is_test' => true]);

    $withdraw = app(FinanceRequestService::class)->createWithdraw(
        user: $user,
        amount: '75',
        source: 'crypto',
        walletAddress: 'test-wallet',
    );
    $transaction = $withdraw->transaction;

    expect($transaction)
        ->not->toBeNull()
        ->user_id->toBe($user->id)
        ->amount->toBe('75.00')
        ->accepted_at->toBeNull()
        ->rejected_at->toBeNull()
        ->and((string) $withdraw->commission)->toBe('3.50')
        ->and($withdraw->wallet_address)->toBe('test-wallet')
        ->and($withdraw->paymentSource?->source)->toBe('crypto')
        ->and((float) app(UserBalanceCalculator::class)->balanceFor($user->id, BalanceTypeEnum::MAIN))
        ->toBe(-75.0)
        ->and(BusinessActivity::query()
            ->where('description', ActivityEventTypeEnum::WithdrawRequested->value)
            ->where('user_id', $user->id)
            ->exists())->toBeTrue()
        ->and(BusinessActivity::query()
            ->where('description', LogActionTypeEnum::CREATE_WITHDRAW->value)
            ->whereJsonContains('properties->feeds', ActivityFeedTypeEnum::GlobalAdmin->value)
            ->exists())->toBeTrue();
});

it('creates a fiat withdraw with the same requisites and commission as the account form', function (): void {
    PaymentSource::query()->firstOrCreate(['source' => 'fiat']);
    $user = User::factory()->create();

    $withdraw = app(FinanceRequestService::class)->createWithdraw(
        user: $user,
        amount: '100',
        source: 'fiat',
        sbpPhone: '+79991234567',
        bankName: 'Тест Банк',
        recipientName: 'Иван Иванов',
    );

    expect((string) $withdraw->commission)->toBe('4.00')
        ->and($withdraw->wallet_address)->toBeNull()
        ->and($withdraw->paymentSource?->source)->toBe('fiat')
        ->and($withdraw->fiatDetail?->sbp_phone)->toBe('+79991234567')
        ->and($withdraw->fiatDetail?->bank_name)->toBe('Тест Банк')
        ->and($withdraw->fiatDetail?->recipient_name)->toBe('Иван Иванов');
});
