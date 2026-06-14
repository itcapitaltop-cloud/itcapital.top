<?php

use App\Models\User;
use App\Services\User\UserRankServices;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();

    $this->service = app(UserRankServices::class);
});

it('drops a manually inflated rank down to the natural rank when the override is disabled', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(3, lineRequired: 5_000.0);

    // Rank set to 5 manually, but actual line turnover only qualifies for the fallback.
    $user = User::factory()->create(['rank' => 5]);

    $changed = $this->service->syncRankToNatural($user);

    expect($changed)->toBeTrue()
        ->and((int) $user->refresh()->rank)->toBe(1);
});

it('recalculates to the exact natural rank earned through line turnover', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(3, lineRequired: 5_000.0);
    createPartnerRank(5, lineRequired: 50_000.0);

    $user = User::factory()->create(['rank' => 5]);
    // Real turnover qualifies for rank 3 but not rank 5.
    addPartnerLineTurnover($user, 6_000.0, now()->subDays(5));

    $changed = $this->service->syncRankToNatural($user);

    expect($changed)->toBeTrue()
        ->and((int) $user->refresh()->rank)->toBe(3);
});

it('does not change anything when the natural rank already equals the current rank', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(3, lineRequired: 5_000.0);

    $user = User::factory()->create(['rank' => 3]);
    addPartnerLineTurnover($user, 6_000.0, now()->subDays(5));

    $changed = $this->service->syncRankToNatural($user);

    expect($changed)->toBeFalse()
        ->and((int) $user->refresh()->rank)->toBe(3);
});

it('does not pay a second rank bonus when the override is disabled', function (): void {
    createPartnerRank(1); // always-met fallback
    createPartnerRank(3, lineRequired: 5_000.0, bonus: 100.0);

    $user = User::factory()->create(['rank' => 5, 'max_rank_awarded' => 5]);
    addPartnerLineTurnover($user, 6_000.0, now()->subDays(5));

    $this->service->syncRankToNatural($user);

    $bonusTransactions = \App\Models\Transaction::query()
        ->where('user_id', $user->id)
        ->where('trx_type', \App\Enums\Transactions\TrxTypeEnum::RANK_BONUS_ACCRUAL)
        ->count();

    expect((int) $user->refresh()->rank)->toBe(3)
        ->and($bonusTransactions)->toBe(0);
});
