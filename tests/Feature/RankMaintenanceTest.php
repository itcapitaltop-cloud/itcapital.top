<?php

use App\Enums\Transactions\TrxTypeEnum;
use App\Models\PartnerRankRequirement;
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
    // No turnover inside either window → maintenance fails every month.

    $this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd);
    expect((int) $user->refresh()->rank)->toBe(4);

    // The next month's run evaluates the next window and demotes one more step.
    $nextPeriodEnd = $this->periodEnd->copy()->addMonthNoOverflow();

    $this->service->applyMonthlyMaintenance($user, $this->periodEnd, $nextPeriodEnd);
    expect((int) $user->refresh()->rank)->toBe(3);
});

it('demotes at most once per evaluated window on repeated runs', function (): void {
    createPartnerRank(2, lineRequired: 1000.0); // threshold for rank 4
    createPartnerRank(3, lineRequired: 1000.0); // threshold for rank 5

    $user = User::factory()->create(['rank' => 5]);

    expect($this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd))->toBeTrue()
        ->and((int) $user->refresh()->rank)->toBe(4);

    // Re-running maintenance for the same window must not demote again.
    expect($this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd))->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe(4);
});

it('does not demote again for the same window after the rank was regained', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(3, lineRequired: 1_000.0);
    createPartnerRank(5, lineRequired: 5_000.0);

    $user = User::factory()->create(['rank' => 5, 'max_rank_awarded' => 5]);
    addPartnerLineTurnover($user, 999_999.0, $this->periodStart->copy()->subMonths(2));

    $this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd);
    expect((int) $user->refresh()->rank)->toBe(4);

    // Fresh post-demotion turnover regains rank 5 and clears the baseline.
    addPartnerLineTurnover($user, 5_000.0, now());
    $this->service->recalculateAndUpdateRank($user);
    expect((int) $user->refresh()->rank)->toBe(5);

    // The already-punished month must not demote the regained rank again.
    expect($this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd))->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe(5);
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

it('stamps the demotion baseline when demoting', function (): void {
    createPartnerRank(3, lineRequired: 1000.0);

    $user = User::factory()->create(['rank' => 5]);

    $this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd);

    expect($user->refresh()->rank_demoted_at?->toDateTimeString())
        ->toBe($this->periodEnd->toDateTimeString());
});

it('does not re-promote a demoted user from pre-demotion turnover', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(3, lineRequired: 1_000.0); // maintenance threshold for rank 5
    createPartnerRank(5, lineRequired: 5_000.0);

    $user = User::factory()->create(['rank' => 5, 'max_rank_awarded' => 5]);
    // Lifetime turnover meets rank 5, but it was generated before the demotion.
    addPartnerLineTurnover($user, 999_999.0, $this->periodStart->copy()->subMonths(2));

    $this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd);
    expect((int) $user->refresh()->rank)->toBe(4);

    // The real-time recalculation must not bounce the rank back from old turnover.
    $changed = $this->service->recalculateAndUpdateRank($user);

    expect($changed)->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe(4);
});

it('regains the rank with post-demotion turnover, clears the baseline and pays no second bonus', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(3, lineRequired: 1_000.0);
    createPartnerRank(5, lineRequired: 5_000.0, bonus: 100.0);

    $user = User::factory()->create(['rank' => 5, 'max_rank_awarded' => 5]);
    addPartnerLineTurnover($user, 999_999.0, $this->periodStart->copy()->subMonths(2));

    $this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd);
    expect((int) $user->refresh()->rank)->toBe(4);

    // Fresh turnover generated after the demotion meets the rank 5 requirement again.
    addPartnerLineTurnover($user, 5_000.0, now());

    expect($this->service->recalculateAndUpdateRank($user))->toBeTrue();
    $user->refresh();

    $bonusTransactions = Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::RANK_BONUS_ACCRUAL)
        ->count();

    expect((int) $user->rank)->toBe(5)
        ->and($user->rank_demoted_at)->toBeNull()
        ->and($bonusTransactions)->toBe(0);
});

it('keeps the demotion baseline while the user is below the previous peak', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(3, lineRequired: 1_000.0);
    createPartnerRank(5, lineRequired: 5_000.0);

    $user = User::factory()->create([
        'rank' => 2,
        'max_rank_awarded' => 5,
        'rank_demoted_at' => now()->subDays(10),
    ]);

    // Post-demotion turnover meets rank 3 but not the previous peak (rank 5).
    addPartnerLineTurnover($user, 1_500.0, now()->subDay());

    expect($this->service->recalculateAndUpdateRank($user))->toBeTrue();
    $user->refresh();

    expect((int) $user->rank)->toBe(3)
        ->and($user->rank_demoted_at)->not->toBeNull();
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

it('demotes step by step down to the floor rank and never below it', function (): void {
    createPartnerRank(1); // threshold for rank 3 — no line requirements → rank 3 is the floor
    createPartnerRank(2, lineRequired: 1_000.0); // threshold for rank 4
    createPartnerRank(3, lineRequired: 1_000.0); // threshold for rank 5

    $user = User::factory()->create(['rank' => 5, 'max_rank_awarded' => 5]);
    // No turnover inside any maintenance window → every month fails.

    // Three consecutive past windows so the demotion baseline stays in the past.
    $month1Start = now()->subMonthsNoOverflow(3)->startOfMonth();
    $month1End = now()->subMonthsNoOverflow(2)->startOfMonth();
    $month2Start = now()->subMonthsNoOverflow(2)->startOfMonth();
    $month2End = now()->subMonthsNoOverflow(1)->startOfMonth();
    $month3Start = now()->subMonthsNoOverflow(1)->startOfMonth();
    $month3End = now()->startOfMonth();

    // Month 1: rank 5 → 4 (threshold rank 3 unmet).
    expect($this->service->applyMonthlyMaintenance($user, $month1Start, $month1End))->toBeTrue()
        ->and((int) $user->refresh()->rank)->toBe(4);

    // Month 2: rank 4 → 3 (threshold rank 2 unmet).
    expect($this->service->applyMonthlyMaintenance($user, $month2Start, $month2End))->toBeTrue()
        ->and((int) $user->refresh()->rank)->toBe(3);

    // Month 3: rank 3 stays 3 — threshold rank 1 has no line requirements (floor reached).
    expect($this->service->applyMonthlyMaintenance($user, $month3Start, $month3End))->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe(3);

    // A further window keeps the user at the floor — never below rank 3.
    $month4Start = now()->startOfMonth();
    $month4End = now()->addMonthNoOverflow()->startOfMonth();

    expect($this->service->applyMonthlyMaintenance($user, $month4Start, $month4End))->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe(3);

    // The peak is preserved throughout the descent.
    expect((int) $user->max_rank_awarded)->toBe(5);
});

it('climbs back up rank by rank from the floor using only post-demotion turnover', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(2, lineRequired: 1_000.0);
    createPartnerRank(3, lineRequired: 2_000.0);
    createPartnerRank(4, lineRequired: 3_000.0);
    createPartnerRank(5, lineRequired: 4_000.0, bonus: 100.0);

    // User already pushed down to the floor (rank 3), previous peak was rank 5.
    $user = User::factory()->create([
        'rank' => 3,
        'max_rank_awarded' => 5,
        'rank_demoted_at' => now()->subDays(5),
    ]);

    // Pre-demotion lifetime turnover that on its own would satisfy rank 5 — must be ignored.
    addPartnerLineTurnover($user, 999_999.0, now()->subMonthsNoOverflow(3));

    expect($this->service->recalculateAndUpdateRank($user))->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe(3);

    // Fresh post-demotion turnover reaches the rank-4 threshold (still below the peak).
    addPartnerLineTurnover($user, 3_000.0, now());

    expect($this->service->recalculateAndUpdateRank($user))->toBeTrue()
        ->and((int) $user->refresh()->rank)->toBe(4)
        ->and($user->refresh()->rank_demoted_at)->not->toBeNull();

    // More post-demotion turnover reaches the previous peak (rank 5) — baseline clears.
    addPartnerLineTurnover($user, 1_000.0, now());

    expect($this->service->recalculateAndUpdateRank($user))->toBeTrue();
    $user->refresh();

    $bonusCount = Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::RANK_BONUS_ACCRUAL)
        ->count();

    expect((int) $user->rank)->toBe(5)
        ->and($user->rank_demoted_at)->toBeNull()
        ->and($bonusCount)->toBe(0); // no second bonus for regaining the peak
});

it('runs the full lifecycle: demotes to the floor then regains the peak from new turnover', function (): void {
    createPartnerRank(1); // floor threshold (rank 3 → no line requirements)
    createPartnerRank(2, lineRequired: 1_000.0);
    createPartnerRank(3, lineRequired: 2_000.0);
    createPartnerRank(4, lineRequired: 3_000.0);
    createPartnerRank(5, lineRequired: 4_000.0, bonus: 100.0);

    $user = User::factory()->create(['rank' => 5, 'max_rank_awarded' => 5]);

    // Lifetime turnover that originally earned rank 5, generated before any maintenance window.
    addPartnerLineTurnover($user, 999_999.0, now()->subMonthsNoOverflow(8));

    $month1Start = now()->subMonthsNoOverflow(3)->startOfMonth();
    $month1End = now()->subMonthsNoOverflow(2)->startOfMonth();
    $month2Start = now()->subMonthsNoOverflow(2)->startOfMonth();
    $month2End = now()->subMonthsNoOverflow(1)->startOfMonth();
    $month3Start = now()->subMonthsNoOverflow(1)->startOfMonth();
    $month3End = now()->startOfMonth();

    // --- Demotion phase: maintenance fails every month down to the floor ---
    $this->service->applyMonthlyMaintenance($user, $month1Start, $month1End);
    expect((int) $user->refresh()->rank)->toBe(4);

    $this->service->applyMonthlyMaintenance($user, $month2Start, $month2End);
    expect((int) $user->refresh()->rank)->toBe(3);

    $this->service->applyMonthlyMaintenance($user, $month3Start, $month3End);
    $user->refresh();

    expect((int) $user->rank)->toBe(3)
        ->and((int) $user->max_rank_awarded)->toBe(5)
        ->and($user->rank_demoted_at)->not->toBeNull();

    // --- Recovery phase: only turnover generated after the demotion counts ---
    addPartnerLineTurnover($user, 4_000.0, now());

    expect($this->service->recalculateAndUpdateRank($user))->toBeTrue();
    $user->refresh();

    $bonusCount = Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::RANK_BONUS_ACCRUAL)
        ->count();

    expect((int) $user->rank)->toBe(5)
        ->and($user->rank_demoted_at)->toBeNull()
        ->and($bonusCount)->toBe(0);
});

it('demotes when any single threshold line is unmet even though others are met', function (): void {
    // Threshold rank 3 (for rank 5) carries two line requirements.
    $thresholdRank = createPartnerRank(3, lineRequired: 1_000.0, line: 1);
    PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $thresholdRank->id,
        'line' => 2,
        'required_turnover' => 1_000.0,
        'personal_deposit' => null,
    ]);

    $user = User::factory()->create(['rank' => 5]);
    // Line 1 met inside the window, line 2 left empty.
    addPartnerLineTurnover($user, 1_500.0, $this->periodStart->copy()->addDays(3), line: 1);

    expect($this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd))->toBeTrue()
        ->and((int) $user->refresh()->rank)->toBe(4);
});

it('preserves the rank when every threshold line is met', function (): void {
    $thresholdRank = createPartnerRank(3, lineRequired: 1_000.0, line: 1);
    PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $thresholdRank->id,
        'line' => 2,
        'required_turnover' => 1_000.0,
        'personal_deposit' => null,
    ]);

    $user = User::factory()->create(['rank' => 5]);
    addPartnerLineTurnover($user, 1_500.0, $this->periodStart->copy()->addDays(2), line: 1);
    addPartnerLineTurnover($user, 1_200.0, $this->periodStart->copy()->addDays(2), line: 2);

    expect($this->service->applyMonthlyMaintenance($user, $this->periodStart, $this->periodEnd))->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe(5);
});
