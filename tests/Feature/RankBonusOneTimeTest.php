<?php

use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Services\User\UserRankServices;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
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

    // Simulate losing the rank (e.g. monthly demotion) while keeping max_rank_awarded.
    $user->update(['rank' => 0]);

    // Re-achieving rank 2 persists the rank again but pays no second bonus.
    expect($this->service->recalculateAndUpdateRank($user))->toBeTrue();
    $user->refresh();

    expect((int) $user->rank)->toBe(2)
        ->and($bonusTransactions())->toBe(1);
});
