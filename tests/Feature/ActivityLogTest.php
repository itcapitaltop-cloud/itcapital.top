<?php

use App\Contracts\Logs\LogRepositoryContract;
use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\CurrencyEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\BusinessActivity;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLog\ActivityFeedService;
use App\Services\ActivityLog\BusinessActivityLogger;
use Illuminate\Support\Facades\Artisan;

it('строит finance feed из activity log', function () {
    $user = User::factory()->create();

    Transaction::query()->create([
        'uuid' => 'DP-FEED-001',
        'user_id' => $user->id,
        'amount' => 150.00,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
    ]);

    $deposit = Deposit::query()->create([
        'uuid' => 'DP-FEED-001',
        'commission' => 0,
        'currency' => CurrencyEnum::USDT_TRC_20,
        'transaction_hash' => 'hash-feed-001',
        'wallet_address' => 'TTESTWALLET',
    ]);

    $logger = app(BusinessActivityLogger::class);

    $logger->write(new WriteBusinessActivityData(
        type: ActivityEventTypeEnum::DepositRequested,
        userId: $user->id,
        subject: $deposit,
        feeds: [ActivityFeedTypeEnum::Finance, ActivityFeedTypeEnum::UserDetailUser],
        properties: [
            'amount' => '150.00',
            'currency' => $deposit->currency->value,
        ],
        causer: $user,
        logName: 'finance',
        context: 'account',
        occurredAt: now()->subMinute(),
    ));

    $logger->write(new WriteBusinessActivityData(
        type: ActivityEventTypeEnum::DepositApproved,
        userId: $user->id,
        subject: $deposit,
        feeds: [ActivityFeedTypeEnum::Finance, ActivityFeedTypeEnum::UserDetailAdmin],
        properties: [
            'amount' => '150.00',
            'currency' => $deposit->currency->value,
        ],
        causer: $user,
        logName: 'finance',
        context: 'admin',
        occurredAt: now(),
    ));

    $operations = app(ActivityFeedService::class)
        ->financeFeed($user->id)
        ->values()
        ->all();

    expect($operations)->toHaveCount(2)
        ->and($operations[0]['status'])->toBe('Одобрено')
        ->and($operations[0]['arrow'])->toBe('down')
        ->and($operations[0]['type'])->toContain('одобрена')
        ->and($operations[1]['status'])->toBe('На модерации')
        ->and($operations[1]['type'])->toContain('Создана заявка на ввод');
});

it('пишет admin audit в activity log через log repository', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $transaction = Transaction::query()->create([
        'uuid' => 'TX-ADMIN-001',
        'user_id' => $user->id,
        'amount' => 100.00,
        'trx_type' => TrxTypeEnum::WITHDRAW,
        'balance_type' => BalanceTypeEnum::MAIN,
    ]);

    $this->actingAs($admin);

    app(LogRepositoryContract::class)->updated(
        model: $transaction,
        actionType: 'approve_transaction',
        oldValues: ['accepted_at' => null],
        newValues: ['accepted_at' => '2026-04-12 12:00:00'],
        targetUseId: $user->id,
    );

    $activity = BusinessActivity::query()
        ->where('description', 'approve_transaction')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity?->user_id)->toBe($user->id)
        ->and($activity?->event)->toBe('admin')
        ->and($activity?->getExtraProperty('old_values'))->toBe(['accepted_at' => null])
        ->and($activity?->getExtraProperty('new_values'))->toBe(['accepted_at' => '2026-04-12 12:00:00']);

    expect(app(ActivityFeedService::class)->userDetailAdminFeed($user->id))
        ->toHaveCount(1)
        ->and(app(ActivityFeedService::class)->userDetailAdminFeed($user->id)[0]['action'])
        ->toBe('Одобрение заявки на вывод');
});

it('делает finance backfill идемпотентным', function () {
    $user = User::factory()->create();

    Transaction::query()->create([
        'uuid' => 'DP-BACKFILL-001',
        'user_id' => $user->id,
        'amount' => 250.00,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
        'accepted_at' => now(),
    ]);

    Deposit::query()->create([
        'uuid' => 'DP-BACKFILL-001',
        'commission' => 0,
        'currency' => CurrencyEnum::USDT_TRC_20,
        'transaction_hash' => 'hash-backfill-001',
        'wallet_address' => 'TBACKFILLWALLET',
    ]);

    Artisan::call('activity:backfill-business-logs', ['--only' => 'finance']);
    Artisan::call('activity:backfill-business-logs', ['--only' => 'finance']);

    expect(BusinessActivity::query()
        ->where('user_id', $user->id)
        ->where('properties->legacy_source', 'transactions')
        ->count())->toBe(2);

    expect(BusinessActivity::query()
        ->where('user_id', $user->id)
        ->where('description', ActivityEventTypeEnum::DepositRequested->value)
        ->count())->toBe(1);

    expect(BusinessActivity::query()
        ->where('user_id', $user->id)
        ->where('description', ActivityEventTypeEnum::DepositApproved->value)
        ->count())->toBe(1);
});
