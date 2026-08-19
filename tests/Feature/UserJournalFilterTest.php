<?php

use App\Dto\Activity\JournalFilterData;
use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Activity\ActivityJournalCategoryEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\CurrencyEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLog\ActivityFeedService;
use App\Services\ActivityLog\BusinessActivityLogger;
use App\Services\Package\Staking\StakingPurchaseService;
use MoonShine\Models\MoonshineUser;

/**
 * Пользователь с одним событием в каждой категории журнала.
 *
 * @return array{0: User, 1: string}
 */
function journalFilterFixture(): array
{
    $user = User::factory()->create();
    $logger = app(BusinessActivityLogger::class);

    Transaction::query()->create([
        'uuid' => 'DP-FILTER-001',
        'user_id' => $user->id,
        'amount' => 150.00,
        'trx_type' => TrxTypeEnum::DEPOSIT,
        'balance_type' => BalanceTypeEnum::MAIN,
    ]);

    $deposit = Deposit::query()->create([
        'uuid' => 'DP-FILTER-001',
        'commission' => 0,
        'currency' => CurrencyEnum::USDT_TRC_20,
        'transaction_hash' => 'hash-filter-001',
        'wallet_address' => 'TTESTWALLET',
    ]);

    $logger->write(new WriteBusinessActivityData(
        type: ActivityEventTypeEnum::DepositRequested,
        userId: $user->id,
        subject: $deposit,
        feeds: [ActivityFeedTypeEnum::Finance, ActivityFeedTypeEnum::UserDetailUser],
        properties: ['amount' => '150.00', 'currency' => $deposit->currency->value],
        causer: $user,
        logName: 'finance',
        occurredAt: now()->subDays(10),
    ));

    $logger->write(new WriteBusinessActivityData(
        type: ActivityEventTypeEnum::PartnerRankIncreased,
        userId: $user->id,
        subject: $user,
        feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
        properties: ['old_rank' => 1, 'new_rank' => 2],
        causer: $user,
        logName: 'partners',
        occurredAt: now()->subDays(5),
    ));

    $package = app(StakingPurchaseService::class)->createPackage($user->id, 100, 2);

    return [$user, $package->uuid];
}

it('фильтрует журнал по категории', function () {
    [$user] = journalFilterFixture();

    $service = app(ActivityFeedService::class);
    $totals = [];

    foreach ([null, ...ActivityJournalCategoryEnum::cases()] as $category) {
        $totals[$category?->value ?? 'all'] = $service
            ->userDetailUserFeed($user->id, filter: new JournalFilterData(category: $category))
            ->total();
    }

    expect($totals['all'])->toBe(3)
        ->and($totals['finance'])->toBe(1)
        ->and($totals['partners'])->toBe(1)
        ->and($totals['staking'])->toBe(1)
        ->and($totals['packages'])->toBe(0);
});

it('показывает в категории «Стейкинг» токеновые события без user_id и feeds', function () {
    [$user, $packageUuid] = journalFilterFixture();

    $package = \App\Models\ItcPackage::whereUuid($packageUuid)->firstOrFail();

    activity('admin')
        ->performedOn($package)
        ->causedBy(User::factory()->create())
        ->withProperties([
            'package_uuid' => $package->uuid,
            'package_type' => PackageTypeEnum::STAKING,
            'amount' => 100,
            'accrual_type' => 'profit',
        ])
        ->log('admin_package_added_manual_profit');

    $rows = app(ActivityFeedService::class)
        ->userDetailUserFeed($user->id, filter: new JournalFilterData(category: ActivityJournalCategoryEnum::Staking));

    expect($rows->total())->toBe(2)
        ->and(collect($rows->items())->pluck('type'))
        ->toContain("На пакет с айди {$package->uuid} вручную начислено: доходность 100.00");
});

it('фильтрует журнал по периоду', function () {
    [$user] = journalFilterFixture();

    $service = app(ActivityFeedService::class);

    $lastWeek = $service->userDetailUserFeed($user->id, filter: new JournalFilterData(
        dateFrom: now()->subDays(7)->startOfDay(),
    ));

    $onlyOldest = $service->userDetailUserFeed($user->id, filter: new JournalFilterData(
        dateTo: now()->subDays(7)->endOfDay(),
    ));

    expect($lastWeek->total())->toBe(2)
        ->and($onlyOldest->total())->toBe(1);
});

it('собирает фильтр из запроса и игнорирует мусорные даты', function () {
    $request = Request::create('/', 'GET', [
        'journal_category' => 'staking',
        'journal_date_from' => '2026-03-01',
        'journal_date_to' => 'не-дата',
    ]);

    $filter = JournalFilterData::fromRequest($request);

    expect($filter->category)->toBe(ActivityJournalCategoryEnum::Staking)
        ->and($filter->dateFrom?->format('Y-m-d H:i:s'))->toBe('2026-03-01 00:00:00')
        ->and($filter->dateTo)->toBeNull()
        ->and($filter->isEmpty())->toBeFalse()
        ->and(JournalFilterData::fromRequest(Request::create('/'))->isEmpty())->toBeTrue();
});

it('сбрасывает категорию, но сохраняет период для админской вкладки', function () {
    $filter = new JournalFilterData(
        category: ActivityJournalCategoryEnum::Finance,
        dateFrom: now()->subDay(),
        dateTo: now(),
    );

    expect($filter->withoutCategory()->category)->toBeNull()
        ->and($filter->withoutCategory()->dateFrom)->toBe($filter->dateFrom)
        ->and($filter->withoutCategory()->dateTo)->toBe($filter->dateTo);
});

it('сохраняет выбранный фильтр в ссылках пагинации', function () {
    [$user] = journalFilterFixture();

    request()->merge(['journal_category' => 'staking', 'journal_date_from' => '2026-01-01']);

    $url = app(ActivityFeedService::class)
        ->userDetailUserFeed($user->id, filter: JournalFilterData::fromRequest())
        ->url(2);

    expect($url)->toContain('journal_category=staking')
        ->toContain('journal_date_from=2026-01-01')
        ->toContain('journal_tab=user');
});

it('рендерит панель фильтра с выбранной категорией и датами', function () {
    $html = view('admin.partials.user-journal-filter', [
        'action' => 'http://localhost/admin/user-detail-page',
        'resourceItem' => 61,
        'journalTab' => 'user',
        'categories' => ActivityJournalCategoryEnum::options(),
        'category' => 'staking',
        'dateFrom' => '2026-03-01',
        'dateTo' => '',
        'isFiltered' => true,
        'resetUrl' => 'http://localhost/admin/user-detail-page?resourceItem=61&tab=logs&journal_tab=user',
    ])->render();

    expect($html)->toContain('name="journal_category"')
        ->toContain('name="journal_date_from"')
        ->toContain('name="journal_date_to"')
        ->toContain('<option value="staking" selected>Стейкинг</option>')
        ->toContain('value="2026-03-01"')
        ->toContain('Все события')
        ->toContain('Сбросить')
        ->toContain('<input type="hidden" name="journal_tab" value="user">')
        // Выравнивание строки фильтра держится на трёх вещах: обёртка form-group
        // снимает у полей margin-bottom: 1rem, а инлайновые width/margin гасят
        // правила form-group для вертикальной формы (width: 100% и отступ 1.25rem
        // между соседними группами). Без любой из них контролы разъезжаются.
        ->and(substr_count($html, '<div class="form-group" style="width: auto; margin: 0;">'))->toBe(3);
});

it('не показывает кнопку сброса, когда фильтр пуст', function () {
    $html = view('admin.partials.user-journal-filter', [
        'action' => 'http://localhost/admin/user-detail-page',
        'resourceItem' => 61,
        'journalTab' => 'admin',
        'categories' => [],
        'category' => '',
        'dateFrom' => '',
        'dateTo' => '',
        'isFiltered' => false,
        'resetUrl' => 'http://localhost/admin/user-detail-page',
    ])->render();

    expect($html)->not->toContain('Сбросить')
        ->not->toContain('name="journal_category"')
        ->toContain('name="journal_date_from"');
});

it('отдаёт страницу журнала с панелью фильтра', function () {
    [$user] = journalFilterFixture();

    $admin = MoonshineUser::query()->create([
        'name' => 'Admin',
        'email' => 'journal-filter-admin@example.com',
        'password' => bcrypt('secret-password'),
        'moonshine_user_role_id' => 1,
    ]);

    $response = $this->actingAs($admin, 'moonshine')->get(
        '/itcapitalmoonshineadminpanel/resource/user-resource/user-detail-page'
        . '?resourceItem=' . $user->id . '&tab=logs&journal_tab=user&journal_category=staking'
    );

    $response->assertOk();

    expect($response->getContent())
        ->toContain('name="journal_category"')
        ->toContain('<option value="staking" selected>Стейкинг</option>')
        ->toContain('Сбросить');
});

/**
 * @return array{0: User, 1: MoonshineUser}
 */
function journalPageActors(): array
{
    [$user] = journalFilterFixture();

    $admin = MoonshineUser::query()->create([
        'name' => 'Admin',
        'email' => 'journal-tab-admin@example.com',
        'password' => bcrypt('secret-password'),
        'moonshine_user_role_id' => 1,
    ]);

    return [$user, $admin];
}

it('пишет выбранную подвкладку журнала в адрес без перезагрузки, когда сбрасывать нечего', function () {
    [$user, $admin] = journalPageActors();

    $html = $this->actingAs($admin, 'moonshine')
        ->get("/itcapitalmoonshineadminpanel/resource/user-resource/user-detail-page?resourceItem={$user->id}&tab=logs&journal_tab=user")
        ->assertOk()
        ->getContent();

    expect($html)->toContain('id="journal-tab-sync"')
        ->toContain('data-reload="0"')
        ->toContain('journal_tab=admin')
        ->toContain('journal_tab=user')
        ->toContain('window.history.replaceState');
});

it('уводит на чистый адрес при переключении подвкладки, когда задан фильтр', function () {
    [$user, $admin] = journalPageActors();

    $filtered = $this->actingAs($admin, 'moonshine')
        ->get("/itcapitalmoonshineadminpanel/resource/user-resource/user-detail-page?resourceItem={$user->id}&tab=logs&journal_tab=user&journal_category=staking")
        ->assertOk()
        ->getContent();

    $paginated = $this->actingAs($admin, 'moonshine')
        ->get("/itcapitalmoonshineadminpanel/resource/user-resource/user-detail-page?resourceItem={$user->id}&tab=logs&journal_tab=user&user_logs_page=2")
        ->assertOk()
        ->getContent();

    expect($filtered)->toContain('data-reload="1"')
        ->and($paginated)->toContain('data-reload="1"');
});
