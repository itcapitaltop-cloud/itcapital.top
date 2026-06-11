<?php

use App\MoonShine\Pages\ItcStaking\ItcStakingDetailPage;
use App\MoonShine\Pages\PackageDefinition\PackageDefinitionDetailPage;
use App\MoonShine\Pages\PackageDefinition\PackageDefinitionIndexPage;
use App\MoonShine\Pages\User\UserDetailPage;
use App\Services\ActivityLog\ActivityFeedService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use MoonShine\Components\FlexibleRender;

/**
 * All admin journals must share one pagination design (the "Настройки пакетов" journal
 * style): a "Показано от X до Y из Z" summary plus "Назад"/"Вперёд" buttons. The design
 * lives in the overridden MoonShine views in resources/views/vendor/moonshine/ui/.
 */
it('renders the shared pagination view in the package journal style', function () {
    $paginator = new LengthAwarePaginator(range(1, 15), total: 35, perPage: 15, currentPage: 2, options: ['path' => '/admin/journal']);

    $html = $paginator->links('moonshine::ui.pagination')->toHtml();

    expect($html)->toContain('Показано от 16 до 30 из 35')
        ->toContain('Страница 2 из 3')
        ->toContain('Назад')
        ->toContain('Вперёд')
        ->toContain('btn btn-primary');
});

it('shows only the summary when everything fits on one page', function () {
    $paginator = new LengthAwarePaginator(range(1, 3), total: 3, perPage: 15, currentPage: 1, options: ['path' => '/admin/journal']);

    $html = $paginator->links('moonshine::ui.pagination')->toHtml();

    expect($html)->toContain('Показано от 1 до 3 из 3')
        ->not->toContain('Страница 1 из 1');
});

it('renders simple pagination (journals without totals) in the same style', function () {
    // 51 items with perPage 50 → the simple paginator knows there is a next page.
    $paginator = new Paginator(range(1, 51), perPage: 50, currentPage: 2, options: ['path' => '/admin/journal']);

    $html = $paginator->links('moonshine::ui.simple-pagination')->toHtml();

    expect($html)->toContain('Страница 2')
        ->toContain('Назад')
        ->toContain('Вперёд')
        ->toContain('btn btn-primary');
});

it('hides simple pagination when there is nothing to flip through', function () {
    $paginator = new Paginator(range(1, 5), perPage: 50, currentPage: 1, options: ['path' => '/admin/journal']);

    expect(trim($paginator->links('moonshine::ui.simple-pagination')->toHtml()))->toBe('');
});

it('paginates both package journals through the shared view', function (string $pageClass) {
    $page = new $pageClass();
    $journalPagination = new ReflectionMethod($page, 'journalPagination');
    $journalPagination->setAccessible(true);

    $paginator = new LengthAwarePaginator(range(1, 15), total: 35, perPage: 15, currentPage: 1, options: ['path' => '/admin/journal']);
    $components = $journalPagination->invoke($page, $paginator);

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(FlexibleRender::class);

    $html = Blade::renderComponent($components[0]);

    expect($html)->toContain('Показано от 1 до 15 из 35')
        ->toContain('Страница 1 из 3');
})->with([
    PackageDefinitionIndexPage::class,
    PackageDefinitionDetailPage::class,
]);

it('returns full paginators from the user detail feeds so their journals show totals', function () {
    // MoonShine's TableBuilder switches to the full pagination view (with the
    // «Показано от X до Y из Z» summary) only for LengthAwarePaginator items.
    $service = app(ActivityFeedService::class);

    expect($service->userDetailUserFeed(1))->toBeInstanceOf(LengthAwarePaginatorContract::class)
        ->and($service->userDetailAdminFeed(1))->toBeInstanceOf(LengthAwarePaginatorContract::class);
});

it('marks user detail feed pagination links with their journal sub-tab', function () {
    $service = app(ActivityFeedService::class);

    expect($service->userDetailUserFeed(1)->url(2))->toContain('journal_tab=user')
        ->and($service->userDetailAdminFeed(1)->url(2))->toContain('journal_tab=admin');
});

it('keeps the journal sub-tab the admin is paginating active after the reload', function (string $pageClass, string $userPageParam) {
    $page = new $pageClass();
    $activeJournalTab = new ReflectionMethod($page, 'activeJournalTab');
    $activeJournalTab->setAccessible(true);

    // Default landing: admin sub-tab.
    request()->replace([]);
    expect($activeJournalTab->invoke($page))->toBe('admin');

    // Paginating the user sub-tab.
    request()->replace([$userPageParam => '2', 'journal_tab' => 'user']);
    expect($activeJournalTab->invoke($page))->toBe('user');

    // Then paginating the admin sub-tab: its link keeps the stale user page param
    // (withQueryString), but the explicit journal_tab marker must win.
    request()->replace([$userPageParam => '2', 'journal_tab' => 'admin']);
    expect($activeJournalTab->invoke($page))->toBe('admin');

    // Legacy links without the marker fall back to the page-param heuristic.
    request()->replace([$userPageParam => '2']);
    expect($activeJournalTab->invoke($page))->toBe('user');
})->with([
    'user card journal' => [UserDetailPage::class, 'user_logs_page'],
    'staking journal' => [ItcStakingDetailPage::class, 'staking_user_logs_page'],
]);
