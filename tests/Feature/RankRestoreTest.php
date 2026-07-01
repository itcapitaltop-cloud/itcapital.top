<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
    // Restore runs while monthly maintenance is disabled, so the full line
    // turnover (not the demotion baseline) counts towards the natural rank.
    config()->set('rank.maintenance.enabled', false);
});

it('restores a demoted user to the peak rank and clears the demotion baseline', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(2, lineRequired: 1_000.0, bonus: 25.0);

    $user = User::factory()->create([
        'username' => 'RestoredUser',
        'rank' => 1,
        'max_rank_awarded' => 2,
        'rank_demoted_at' => now()->subDay(),
        'rank_demotion_period_end' => now()->subDay(),
    ]);

    // Turnover generated before the demotion baseline — enough for rank 2.
    addPartnerLineTurnover($user, 1_500.0, now()->subDays(5));

    Artisan::call('partner:rank-restore', ['--user' => $user->id]);
    $output = Artisan::output();

    $user->refresh();

    expect((int) $user->rank)->toBe(2)
        ->and($user->rank_demoted_at)->toBeNull()
        ->and($user->rank_demotion_period_end)->toBeNull()
        ->and($output)->toContain('RestoredUser')
        ->and($output)->toContain((string) $user->id);
});

it('restores only to the earned rank when the downline has shrunk, still clearing the baseline', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(2, lineRequired: 1_000.0);
    createPartnerRank(3, lineRequired: 999_999.0); // no longer reachable

    $user = User::factory()->create([
        'rank' => 1,
        'max_rank_awarded' => 3, // once held rank 3
        'rank_demoted_at' => now()->subDay(),
        'rank_demotion_period_end' => now()->subDay(),
    ]);

    // Current turnover only supports rank 2, not the former peak of 3.
    addPartnerLineTurnover($user, 1_500.0, now()->subDays(5));

    Artisan::call('partner:rank-restore', ['--user' => $user->id]);

    $user->refresh();

    // Promote-only lifts to the earned rank 2; the stale baseline is cleared
    // even though the peak was not reached.
    expect((int) $user->rank)->toBe(2)
        ->and($user->rank_demoted_at)->toBeNull()
        ->and($user->rank_demotion_period_end)->toBeNull();
});

it('does not persist anything on a --dry-run', function (): void {
    createPartnerRank(1);
    createPartnerRank(2, lineRequired: 1_000.0);

    $demotedAt = now()->subDay();
    $user = User::factory()->create([
        'rank' => 1,
        'max_rank_awarded' => 2,
        'rank_demoted_at' => $demotedAt,
        'rank_demotion_period_end' => $demotedAt,
    ]);

    addPartnerLineTurnover($user, 1_500.0, now()->subDays(5));

    Artisan::call('partner:rank-restore', ['--user' => $user->id, '--dry-run' => true]);

    $user->refresh();

    expect((int) $user->rank)->toBe(1)
        ->and($user->rank_demoted_at)->not->toBeNull()
        ->and($user->rank_demotion_period_end)->not->toBeNull();
});

it('leaves users with a manual rank override untouched', function (): void {
    createPartnerRank(1);
    createPartnerRank(2, lineRequired: 1_000.0);

    $user = User::factory()->create([
        'rank' => 1,
        'max_rank_awarded' => 2,
        'overridden_rank' => true,
        'rank_demoted_at' => now()->subDay(),
        'rank_demotion_period_end' => now()->subDay(),
    ]);

    addPartnerLineTurnover($user, 1_500.0, now()->subDays(5));

    Artisan::call('partner:rank-restore', ['--user' => $user->id]);

    $user->refresh();

    expect((int) $user->rank)->toBe(1)
        ->and($user->rank_demoted_at)->not->toBeNull();
});
