<?php

use App\ActivityLog\ActivityManager;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Models\BusinessActivity;
use App\Models\ItcPackage;
use App\Models\TokenRate;
use App\Models\User;
use App\Services\ActivityLog\ActivityFeedService;
use App\Services\Package\Staking\StakingPurchaseService;
use App\Services\Token\TokenRateResolver;
use App\Settings\GeneralSetting;

/**
 * @return array{0: User, 1: User, 2: ItcPackage}
 */
function stakingJournalFixture(float $amountUsd = 300, float $monthProfitPercent = 2): array
{
    $settings = app(GeneralSetting::class);
    $settings->exchange_rate_itc = 0.10;
    $settings->start_bonus_staking_percent = 5;
    $settings->save();

    TokenRate::query()->delete();
    app(TokenRateResolver::class)->upsertRate('2026-04-01', 0.10);

    $admin = User::factory()->create();
    $user = User::factory()->create();
    $package = app(StakingPurchaseService::class)->createPackage($user->id, $amountUsd, $monthProfitPercent);

    return [$admin, $user, $package];
}

it('показывает покупку пакета стейкинга в пользовательском журнале карточки', function () {
    [, $user, $package] = stakingJournalFixture(100);

    $rows = app(ActivityFeedService::class)->userDetailUserFeed($user->id);

    expect(collect($rows->items())->pluck('type'))
        ->toContain("Куплен пакет стейкинга {$package->uuid} на 1000.00 коинов при курсе 0.1");
});

it('пишет событие изменения процента прибыли пакета стейкинга', function () {
    [$admin, $user, $package] = stakingJournalFixture();

    $this->withoutMiddleware();

    $this->actingAs($admin)
        ->post("/itcapitalmoonshineadminpanel/itc-staking/package/staking/{$package->uuid}", [
            'profit_percent' => 7.5,
            'start_bonus_staking_percent' => 5,
            'amount' => 0,
            'manual_profit' => 0,
            'manual_accrual_type' => 'profit',
        ]);

    $activity = BusinessActivity::query()
        ->where('description', ActivityEventTypeEnum::StakingProfitPercentChanged->value)
        ->latest('id')
        ->firstOrFail();

    expect($activity->user_id)->toBe($user->id)
        ->and($activity->getExtraProperty('feeds'))->toContain(ActivityFeedTypeEnum::UserDetailAdmin->value)
        ->and(app(ActivityManager::class)->resolve($activity))
        ->toBe("Процент прибыли пакета стейкинга {$package->uuid} изменён администратором с 2% на 7.5%");
});

it('сохраняет и логирует процент стартовой премии из формы редактирования пакета', function () {
    [$admin, $user, $package] = stakingJournalFixture();

    $this->withoutMiddleware();

    $this->actingAs($admin)
        ->post("/itcapitalmoonshineadminpanel/itc-staking/package/staking/{$package->uuid}", [
            'profit_percent' => $package->month_profit_percent,
            'start_bonus_staking_percent' => 9,
            'amount' => 0,
            'manual_profit' => 0,
            'manual_accrual_type' => 'profit',
        ]);

    $activity = BusinessActivity::query()
        ->where('description', ActivityEventTypeEnum::StakingStartBonusPercentChanged->value)
        ->latest('id')
        ->firstOrFail();

    expect((float) $user->fresh()->setting('start_bonus_staking_percent'))->toBe(9.0)
        ->and(app(ActivityManager::class)->resolve($activity))
        ->toBe("Процент стартовой премии по стейкингу изменён администратором с 5% на 9% (пакет {$package->uuid})");
});

it('не пишет событие процента, когда значения не изменились', function () {
    [$admin, , $package] = stakingJournalFixture();

    $this->withoutMiddleware();

    $this->actingAs($admin)
        ->post("/itcapitalmoonshineadminpanel/itc-staking/package/staking/{$package->uuid}", [
            'profit_percent' => $package->month_profit_percent,
            'start_bonus_staking_percent' => 5,
            'amount' => 0,
            'manual_profit' => 0,
            'manual_accrual_type' => 'profit',
        ]);

    expect(BusinessActivity::query()->whereIn('description', [
        ActivityEventTypeEnum::StakingProfitPercentChanged->value,
        ActivityEventTypeEnum::StakingStartBonusPercentChanged->value,
    ])->count())->toBe(0);
});

it('показывает оба события процента в админском журнале карточки и в журнале стейкинга', function () {
    [$admin, $user, $package] = stakingJournalFixture();

    $this->withoutMiddleware();

    $this->actingAs($admin)
        ->post("/itcapitalmoonshineadminpanel/itc-staking/package/staking/{$package->uuid}", [
            'profit_percent' => 7.5,
            'start_bonus_staking_percent' => 9,
            'amount' => 0,
            'manual_profit' => 0,
            'manual_accrual_type' => 'profit',
        ]);

    $adminRows = collect(app(ActivityFeedService::class)->userDetailAdminFeed($user->id)->items());

    expect($adminRows->pluck('action'))
        ->toContain("Процент прибыли пакета стейкинга {$package->uuid} изменён администратором с 2% на 7.5%")
        ->toContain("Процент стартовой премии по стейкингу изменён администратором с 5% на 9% (пакет {$package->uuid})")
        ->and($adminRows->firstWhere('action', "Процент прибыли пакета стейкинга {$package->uuid} изменён администратором с 2% на 7.5%")['old_values'])
        ->toBe('2.00');

    $stakingLog = BusinessActivity::query()
        ->packagesStakingWithAdmin($user->id)
        ->get()
        ->map(fn (BusinessActivity $activity): string => app(ActivityManager::class)->resolve($activity))
        ->all();

    expect($stakingLog)
        ->toContain("Процент прибыли пакета стейкинга {$package->uuid} изменён администратором с 2% на 7.5%")
        ->toContain("Процент стартовой премии по стейкингу изменён администратором с 5% на 9% (пакет {$package->uuid})");
});

it('показывает staking-начисление без user_id и feeds в журнале карточки', function () {
    [$admin, $user, $package] = stakingJournalFixture();

    // Исторические staking-вызовы activity() не проставляют ни user_id, ни feeds —
    // владелец у таких записей определяется только через subject → transaction.
    activity('admin')
        ->performedOn($package)
        ->causedBy($admin)
        ->withProperties([
            'package_uuid' => $package->uuid,
            'package_type' => PackageTypeEnum::STAKING,
            'amount' => 100,
            'accrual_type' => 'topup_bonus',
        ])
        ->log('admin_package_added_manual_profit');

    $activity = BusinessActivity::query()->where('description', 'admin_package_added_manual_profit')->latest('id')->firstOrFail();

    expect($activity->user_id)->toBeNull()
        ->and($activity->getExtraProperty('feeds'))->toBeNull();

    $userRow = collect(app(ActivityFeedService::class)->userDetailUserFeed($user->id)->items())
        ->firstWhere('type', "На пакет с айди {$package->uuid} вручную начислено: начисление токенов 100.00");

    $adminRow = collect(app(ActivityFeedService::class)->userDetailAdminFeed($user->id)->items())
        ->firstWhere('action', "На пакет с айди {$package->uuid} вручную начислено: начисление токенов 100.00");

    expect($userRow)->not->toBeNull()
        ->and($userRow['action'])->toBe('Увеличение баланса')
        ->and($userRow['operation_amount'])->toBe('100.00')
        ->and($adminRow)->not->toBeNull()
        ->and($adminRow['operation_amount'])->toBe('100.00');
});

it('берет сумму операции из profit для исторического staking profit_accrued', function () {
    [, $user, $package] = stakingJournalFixture();

    activity('package')
        ->performedOn($package)
        ->causedBy($user->id)
        ->withProperties([
            'profit' => 494.18,
            'percent' => '2.00',
            'uuid' => $package->uuid,
            // `amount` здесь — весь баланс пакета, а не сумма операции.
            'amount' => 24708.77,
            'exchange_rate' => 0.14,
            'package_type' => PackageTypeEnum::STAKING,
        ])
        ->log('profit_accrued');

    $row = collect(app(ActivityFeedService::class)->userDetailUserFeed($user->id)->items())
        ->firstWhere('action', 'Увеличение баланса');

    expect($row['operation_amount'])->toBe('494.18');
});

it('не показывает staking-события чужого пакета в карточке пользователя', function () {
    [$admin, , $package] = stakingJournalFixture();

    $stranger = User::factory()->create();

    activity('admin')
        ->performedOn($package)
        ->causedBy($admin)
        ->withProperties([
            'package_uuid' => $package->uuid,
            'package_type' => PackageTypeEnum::STAKING,
            'amount' => 100,
            'accrual_type' => 'profit',
        ])
        ->log('admin_package_added_manual_profit');

    expect(app(ActivityFeedService::class)->userDetailUserFeed($stranger->id)->total())->toBe(0)
        ->and(app(ActivityFeedService::class)->userDetailAdminFeed($stranger->id)->total())->toBe(0);
});
