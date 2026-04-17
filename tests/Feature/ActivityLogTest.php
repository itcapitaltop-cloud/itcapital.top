<?php

use App\Contracts\Logs\LogRepositoryContract;
use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\CurrencyEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\BusinessActivity;
use App\Models\Deposit;
use App\Models\ItcPackage;
use App\Models\TokenRate;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLog\ActivityFeedService;
use App\Services\ActivityLog\BusinessActivityLogger;
use App\Services\Package\Staking\StakingPurchaseService;
use App\Services\Token\TokenRateResolver;
use App\Settings\GeneralSetting;
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

it('не показывает изменение суммы пакета во вкладке администратора', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $transaction = Transaction::query()->create([
        'uuid' => 'TX-PKG-ADMIN-001',
        'user_id' => $user->id,
        'amount' => 300.00,
        'trx_type' => TrxTypeEnum::HIDDEN_DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
    ]);

    ItcPackage::query()->create([
        'uuid' => 'ITC-13P5z7ru7m',
        'work_to' => now()->addWeeks(30),
        'type' => PackageTypeEnum::STANDARD,
        'month_profit_percent' => '8.2',
    ]);

    $this->actingAs($admin);

    app(LogRepositoryContract::class)->updated(
        model: $transaction,
        actionType: 'update_itc_package_amount',
        oldValues: ['amount' => '200.00'],
        newValues: ['amount' => '300.00'],
        targetUseId: $user->id,
        extraProperties: ['package_uuid' => 'ITC-13P5z7ru7m'],
    );

    $rows = app(ActivityFeedService::class)->userDetailAdminFeed($user->id);

    expect($rows)->toBeEmpty();
});

it('не показывает вывод реинвеста профита на баланс во вкладке администратора', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $transaction = Transaction::query()->create([
        'uuid' => 'TX-REINVEST-ADMIN-001',
        'user_id' => $user->id,
        'amount' => 25.00,
        'trx_type' => TrxTypeEnum::WITHDRAW_PACKAGE_REINVEST_PROFIT,
        'balance_type' => BalanceTypeEnum::MAIN,
    ]);

    $this->actingAs($admin);

    app(LogRepositoryContract::class)->updated(
        model: $transaction,
        actionType: 'withdraw_package_reinvest_profit',
        oldValues: ['amount' => '25.00'],
        newValues: ['amount' => '25.00'],
        targetUseId: $user->id,
    );

    $rows = app(ActivityFeedService::class)->userDetailAdminFeed($user->id);

    expect($rows)->toBeEmpty();
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

it('не подмешивает legacy курс в описание покупки обычного пакета', function () {
    $user = User::factory()->create();

    $package = ItcPackage::query()->create([
        'uuid' => 'PKG-LOG-001',
        'work_to' => now()->addWeeks(30),
        'type' => PackageTypeEnum::STANDARD,
        'month_profit_percent' => '8.2',
    ]);

    app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
        type: ActivityEventTypeEnum::PackagePurchased,
        userId: $user->id,
        subject: $package,
        feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
        properties: [
            'amount' => '250.00',
            'package_uuid' => $package->uuid,
            'package_type' => $package->type->value,
        ],
        causer: $user,
        logName: 'packages',
        context: 'account',
    ));

    $activity = BusinessActivity::query()
        ->where('description', ActivityEventTypeEnum::PackagePurchased->value)
        ->latest('id')
        ->firstOrFail();

    $text = app(\App\ActivityLog\ActivityManager::class)->resolve($activity);

    expect($text)->toContain('Куплен пакет PKG-LOG-001 на сумму 250.00 ITC')
        ->and(mb_stripos($text, 'курс'))->toBeFalse();
});

it('показывает в журнале пакетов изменение баланса пакета администратором', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $transaction = Transaction::query()->create([
        'uuid' => 'PKG-ADMIN-LOG-001',
        'user_id' => $user->id,
        'amount' => 300.00,
        'trx_type' => TrxTypeEnum::HIDDEN_DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
    ]);

    $package = ItcPackage::query()->create([
        'uuid' => 'ITC-13P5z7ru7m',
        'work_to' => now()->addWeeks(30),
        'type' => PackageTypeEnum::STANDARD,
        'month_profit_percent' => '8.2',
    ]);

    $this->actingAs($admin);

    app(BusinessActivityLogger::class)->writeDescription(
        description: 'admin_package_changed_amount',
        userId: $user->id,
        subject: $transaction,
        feeds: [ActivityFeedTypeEnum::Packages],
        properties: [
            'package_uuid' => $package->uuid,
            'amount' => '300.00',
            'old_amount' => '200.00',
            'package_type' => $package->type->value,
        ],
        causer: $admin,
        logName: 'admin',
        context: 'admin',
    );

    $rows = app(ActivityFeedService::class)->packageFeed($user->id)->values()->all();

    expect($transaction->uuid)->toBe('PKG-ADMIN-LOG-001')
        ->and($rows)->toHaveCount(1)
        ->and($rows[0]['event'])->toBe('Баланс пакета ITC-13P5z7ru7m изменен администратором с 200.00 на 300.00');
});

it('показывает изменение баланса пакета администратором в журнале пользователя админки', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $transaction = Transaction::query()->create([
        'uuid' => 'PKG-USER-LOG-001',
        'user_id' => $user->id,
        'amount' => 101.00,
        'trx_type' => TrxTypeEnum::HIDDEN_DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
    ]);

    $package = ItcPackage::query()->create([
        'uuid' => 'ITC-13P5z7ru7m',
        'work_to' => now()->addWeeks(30),
        'type' => PackageTypeEnum::STANDARD,
        'month_profit_percent' => '8.2',
    ]);

    app(BusinessActivityLogger::class)->writeDescription(
        description: 'update_itc_package_amount',
        userId: $user->id,
        subject: $transaction,
        feeds: [ActivityFeedTypeEnum::UserDetailUser],
        properties: [
            'package_uuid' => $package->uuid,
            'amount' => '101.00',
            'old_values' => ['amount' => '100.00'],
            'new_values' => ['amount' => '101.00'],
            'package_type' => $package->type->value,
        ],
        causer: $admin,
        logName: 'admin',
        context: 'admin',
    );

    $rows = app(ActivityFeedService::class)->userDetailUserFeed($user->id);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['type'])->toBe('Баланс пакета ITC-13P5z7ru7m изменен администратором с 100.00 на 101.00')
        ->and($rows[0]['operation_amount'])->toBe('101.00');
});

it('пишет staking покупку в новый business activity log', function () {
    $user = User::factory()->create();

    $package = app(StakingPurchaseService::class)->createPackage($user->id, 100);

    $activity = BusinessActivity::query()
        ->where('description', ActivityEventTypeEnum::StakingPackagePurchased->value)
        ->where('user_id', $user->id)
        ->latest('id')
        ->first();

    expect($package->type)->toBe(PackageTypeEnum::STAKING)
        ->and($activity)->not->toBeNull()
        ->and($activity?->getExtraProperty('package_uuid'))->toBe($package->uuid)
        ->and($activity?->getExtraProperty('amount'))->toBe('100')
        ->and($activity?->getExtraProperty('feeds'))->toContain(ActivityFeedTypeEnum::Staking->value);
});

it('показывает сумму возврата при закрытии staking пакета', function () {
    $user = User::factory()->create();

    $package = ItcPackage::query()->create([
        'uuid' => 'ITC-STK-CLOSE-001',
        'work_to' => now()->addWeeks(30),
        'type' => PackageTypeEnum::STAKING,
        'month_profit_percent' => '2',
    ]);

    app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
        type: ActivityEventTypeEnum::PackageClosed,
        userId: $user->id,
        subject: $package,
        feeds: [ActivityFeedTypeEnum::Staking, ActivityFeedTypeEnum::UserDetailUser],
        properties: [
            'amount' => '320.83',
            'package_uuid' => $package->uuid,
            'package_type' => $package->type->value,
            'new_package_type' => PackageTypeEnum::ARCHIVE->value,
        ],
        causer: $user,
        logName: 'packages',
        context: 'admin',
    ));

    $activity = BusinessActivity::query()
        ->where('description', ActivityEventTypeEnum::PackageClosed->value)
        ->latest('id')
        ->firstOrFail();

    $text = app(\App\ActivityLog\ActivityManager::class)->resolve($activity);

    expect($text)->toBe('Пакет стейкинга ITC-STK-CLOSE-001 закрыт, на основной баланс возвращено 320.83 ITC');
});

it('добавляет сумму в staking пакет через админку и пишет корректный журнал', function () {
    $settings = app(GeneralSetting::class);
    $settings->exchange_rate_itc = 0.10;
    $settings->save();

    TokenRate::query()->delete();
    app(TokenRateResolver::class)->upsertRate('2026-04-01', 0.10);

    $admin = User::factory()->create();
    $user = User::factory()->create();

    $package = app(StakingPurchaseService::class)->createPackage($user->id, 300);

    $this->actingAs($admin)
        ->post("/itcapitalmoonshineadminpanel/itc-staking/package/staking/{$package->uuid}", [
            'profit_percent' => $package->month_profit_percent,
            'amount' => 10,
            'manual_profit' => 0,
            'manual_accrual_type' => 'profit',
        ]);

    $package->refresh();
    $package->load('transaction');

    $activity = BusinessActivity::query()
        ->where('description', 'admin_package_changed_amount')
        ->latest('id')
        ->firstOrFail();

    $text = app(\App\ActivityLog\ActivityManager::class)->resolve($activity);

    expect((float) $package->transaction->amount)->toBe(301.0)
        ->and($text)->toBe("Баланс пакета {$package->uuid} изменен администратором с 3000.00 на 3010.00");
});

it('показывает admin изменение staking баланса в пользовательском staking журнале', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $package = ItcPackage::query()->create([
        'uuid' => 'ITC-Rdmng7NE2e',
        'work_to' => now()->addWeeks(30),
        'type' => PackageTypeEnum::STAKING,
        'month_profit_percent' => '2',
    ]);

    Transaction::query()->create([
        'uuid' => $package->uuid,
        'user_id' => $user->id,
        'amount' => 330.83,
        'trx_type' => TrxTypeEnum::HIDDEN_DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
    ]);

    app(BusinessActivityLogger::class)->writeDescription(
        description: 'admin_package_changed_amount',
        userId: $user->id,
        subject: $package,
        feeds: [ActivityFeedTypeEnum::Packages],
        properties: [
            'package_uuid' => $package->uuid,
            'package_type' => PackageTypeEnum::STAKING->value,
            'old_amount' => '1918.27',
            'amount' => '1928.27',
        ],
        causer: $admin,
        logName: 'admin',
        context: 'admin',
    );

    $logs = BusinessActivity::query()
        ->packagesStaking($user->id)
        ->latest()
        ->get()
        ->map(function (BusinessActivity $activity): string {
            return app(\App\ActivityLog\ActivityManager::class)->resolve($activity);
        })
        ->all();

    expect($logs)->toContain('Баланс пакета ITC-Rdmng7NE2e изменен администратором с 1918.27 на 1928.27');
});
