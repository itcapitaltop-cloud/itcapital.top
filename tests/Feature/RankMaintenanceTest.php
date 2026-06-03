<?php

use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Services\User\UserRankServices;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();

    // Previous full calendar month — the window the maintenance job evaluates.
    $this->periodStart = now()->subMonthNoOverflow()->startOfMonth();
    $this->periodEnd = now()->startOfMonth();
    $this->service = app(UserRankServices::class);
});

it('demotes the rank by a single step when the monthly line turnover is insufficient', function (): void {
    createPartnerRank(3, lineRequired: 1000.0); // threshold for rank 5 is rank 3

    $user = User::factory()->create(['rank' => 5]);

    // Large turnover, but generated BEFORE the maintenance window → 0 within window.
    addPartnerLineTurnover($user, 999_999.0, $this->periodStart->copy()->subMonths(2));

    $demoted = $this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd);

    expect($demoted)->toBeTrue()
        ->and((int) $user->refresh()->rank)->toBe(4);
});

it('keeps the rank when the monthly line turnover meets the threshold', function (): void {
    createPartnerRank(3, lineRequired: 1000.0);

    $user = User::factory()->create(['rank' => 5]);

    addPartnerLineTurnover($user, 1_500.0, $this->periodStart->copy()->addDays(3));

    $demoted = $this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd);

    expect($demoted)->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe(5);
});

it('demotes one step per consecutive failing month', function (): void {
    createPartnerRank(2, lineRequired: 1000.0); // threshold for rank 4
    createPartnerRank(3, lineRequired: 1000.0); // threshold for rank 5

    $user = User::factory()->create(['rank' => 5]);
    // No turnover inside the window → maintenance fails every month.

    $this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd);
    expect((int) $user->refresh()->rank)->toBe(4);

    $this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd);
    expect((int) $user->refresh()->rank)->toBe(3);
});

it('preserves cumulative line turnover when demoting', function (): void {
    createPartnerRank(3, lineRequired: 1000.0);

    $user = User::factory()->create(['rank' => 5]);
    addPartnerLineTurnover($user, 999_999.0, $this->periodStart->copy()->subMonths(2));

    $turnoverBefore = (float) Transaction::query()
        ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
        ->sum('amount');

    $this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd);

    $turnoverAfter = (float) Transaction::query()
        ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
        ->sum('amount');

    expect((int) $user->refresh()->rank)->toBe(4)
        ->and($turnoverAfter)->toBe($turnoverBefore);
});

it('skips users with a manual rank override', function (): void {
    createPartnerRank(3, lineRequired: 1000.0);

    $user = User::factory()->create(['rank' => 5, 'overridden_rank' => true]);
    // No turnover → would fail maintenance if it were not exempt.

    $demoted = $this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd);

    expect($demoted)->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe(5);
});

it('never demotes users at or below the maintenance floor', function (int $rank): void {
    $user = User::factory()->create(['rank' => $rank]);

    $demoted = $this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd);

    expect($demoted)->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe($rank);
})->with([1, 2]);

it('runs the partner:rank-maintenance command and demotes via the previous-month window', function (): void {
    createPartnerRank(3, lineRequired: 1000.0);

    $user = User::factory()->create(['rank' => 5]);
    // Turnover in the current month (after the window) → 0 within the previous month.
    addPartnerLineTurnover($user, 5_000.0, now());

    Artisan::call('partner:rank-maintenance', ['--user' => $user->id]);

    expect((int) $user->refresh()->rank)->toBe(4);
});

it('does not persist demotions on a --dry-run', function (): void {
    createPartnerRank(3, lineRequired: 1000.0);

    $user = User::factory()->create(['rank' => 5]);

    Artisan::call('partner:rank-maintenance', ['--user' => $user->id, '--dry-run' => true]);

    expect((int) $user->refresh()->rank)->toBe(5);
});

it('never persists a downgrade through the real-time recalculation', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(2, lineRequired: 1_000.0);
    createPartnerRank(3, lineRequired: 5_000.0);

    $user = User::factory()->create(['rank' => 3]);
    // Cumulative line-1 turnover meets rank 2 but not rank 3 → natural rank is 2.
    addPartnerLineTurnover($user, 1_500.0, now()->subDays(10));

    $changed = $this->service->recalculateAndUpdateRank($user);

    expect($changed)->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe(3);
});
