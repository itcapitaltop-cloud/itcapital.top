<?php

use App\Contracts\Packages\PackageReinvestRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageBalanceWithdraw;
use App\Models\PackageBodyUnlock;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\ItcPackageRepository;

/**
 * Закрытие пакета выплачивает тело целиком транзакцией WITHDRAW_PACKAGE, поэтому
 * ожидающая разблокировка обязана быть снята с очереди — иначе планировщик выплатит
 * те же деньги второй раз.
 */
it('снимает ожидающую разблокировку при закрытии пакета и не платит её повторно', function () {
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

    $unlock = PackageBodyUnlock::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);

    app(ItcPackageRepository::class)->closePackage(
        $package->uuid,
        app(TransactionRepositoryContract::class),
        app(PackageReinvestRepositoryContract::class),
    );

    expect($unlock->fresh()->cancelled_at)->not->toBeNull()
        ->and($unlock->fresh()->payout_transaction_uuid)->toBeNull();

    // Плановая выплата, запущенная после закрытия, не должна платить второй раз.
    $this->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();

    expect($unlock->fresh()->payout_transaction_uuid)->toBeNull()
        ->and(PackageBalanceWithdraw::query()->where('package_uuid', $package->uuid)->count())->toBe(0)
        ->and(Transaction::query()
            ->where('user_id', $user->id)
            ->where('trx_type', TrxTypeEnum::WITHDRAW_PACKAGE_TO_BALANCE)
            ->count())->toBe(0);
});

it('не трогает уже выплаченную разблокировку при закрытии пакета', function () {
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

    $unlock = PackageBodyUnlock::factory()->duePayout()->create([
        'package_uuid' => $package->uuid,
        'amount' => '300.00',
    ]);

    $this->artisan('packages:payout-unlocked-body-amounts')->assertSuccessful();

    $payoutTransactionUuid = $unlock->fresh()->payout_transaction_uuid;

    app(ItcPackageRepository::class)->closePackage(
        $package->uuid,
        app(TransactionRepositoryContract::class),
        app(PackageReinvestRepositoryContract::class),
    );

    expect($unlock->fresh()->cancelled_at)->toBeNull()
        ->and($unlock->fresh()->payout_transaction_uuid)->toBe($payoutTransactionUuid);
});
