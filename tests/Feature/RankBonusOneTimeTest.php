<?php

use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Services\User\UserRankServices;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
    config()->set('rank.maintenance.enabled', true);
    $this->service = app(UserRankServices::class);
});

it('pays the rank bonus only once even after losing and re-achieving the rank', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(2, lineRequired: 1_000.0, bonus: 25.0);

    $user = User::factory()->create(['rank' => 0, 'max_rank_awarded' => 0]);
    // Cumulative line-1 turnover meets rank 2.
    addPartnerLineTurnover($user, 1_500.0, now()->subDays(5));

    $bonusTransactions = fn (): int => Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', TrxTypeEnum::RANK_BONUS_ACCRUAL)
        ->count();

    // First achievement: rank rises to 2, bonus is paid once.
    expect($this->service->recalculateAndUpdateRank($user))->toBeTrue();
    $user->refresh();

    expect((int) $user->rank)->toBe(2)
        ->and((int) $user->max_rank_awarded)->toBe(2)
        ->and($bonusTransactions())->toBe(1);

    // Simulate losing the rank via the monthly demotion: the baseline is recorded
    // and max_rank_awarded is kept.
    $user->update(['rank' => 1, 'rank_demoted_at' => now()->subDay()]);

    // Pre-demotion turnover alone must not re-achieve the rank.
    expect($this->service->recalculateAndUpdateRank($user))->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe(1);

    // Fresh post-demotion turnover re-achieves rank 2 but pays no second bonus.
    addPartnerLineTurnover($user, 1_200.0, now());

    expect($this->service->recalculateAndUpdateRank($user))->toBeTrue();
    $user->refresh();

    expect((int) $user->rank)->toBe(2)
        ->and($user->rank_demoted_at)->toBeNull()
        ->and($bonusTransactions())->toBe(1);
});

it('ignores an old demotion baseline while rank maintenance is disabled', function (): void {
    config()->set('rank.maintenance.enabled', false);
    createPartnerRank(1); // always-met fallback
    createPartnerRank(2, lineRequired: 1_000.0, bonus: 25.0);

    $user = User::factory()->create([
        'rank' => 1,
        'max_rank_awarded' => 2,
        'rank_demoted_at' => now()->subDay(),
    ]);

    // The turnover was generated before rank_demoted_at. With maintenance
    // disabled, the old baseline should not block the normal rank calculation.
    addPartnerLineTurnover($user, 1_500.0, now()->subDays(5));

    expect($this->service->recalculateAndUpdateRank($user))->toBeTrue()
        ->and((int) $user->refresh()->rank)->toBe(2)
        ->and($user->rank_demoted_at)->toBeNull();
});
