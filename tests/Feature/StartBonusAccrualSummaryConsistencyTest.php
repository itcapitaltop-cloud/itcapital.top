<?php

declare(strict_types=1);

use App\Contracts\Accruals\StartBonusAccrualContract;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Models\PartnerClosure;
use App\Models\PartnerLevelPercent;
use App\Models\User;
use App\Models\UserSummary;
use App\Services\User\UserBalanceCalculator;
use Illuminate\Support\Facades\DB;

it('keeps user_summary.partner_balance in sync with the ledger after bulk start-bonus accrual', function () {
    $grandAncestor = User::factory()->create(['rank' => 3, 'extended_lines' => false]);
    $ancestor = User::factory()->create(['rank' => 2, 'extended_lines' => false]);
    $buyer = User::factory()->create();

    DB::table('partner_levels')->insert([
        ['id' => 3, 'level' => 3, 'name' => 'R3', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'level' => 2, 'name' => 'R2', 'created_at' => now(), 'updated_at' => now()],
    ]);

    PartnerLevelPercent::query()->create([
        'partner_level_id' => 3,
        'bonus_type' => PartnerRewardTypeEnum::START,
        'line' => 2,
        'percent' => 4.00,
    ]);

    PartnerLevelPercent::query()->create([
        'partner_level_id' => 2,
        'bonus_type' => PartnerRewardTypeEnum::START,
        'line' => 1,
        'percent' => 6.00,
    ]);

    // Pre-existing (pre-fix) summary rows, so the test fails if recompute is not wired up.
    UserSummary::query()->updateOrCreate(['user_id' => $grandAncestor->id], ['partner_balance' => '0.00000000']);
    UserSummary::query()->updateOrCreate(['user_id' => $ancestor->id], ['partner_balance' => '0.00000000']);

    PartnerClosure::factory()->create([
        'ancestor_id' => $ancestor->id,
        'descendant_id' => $buyer->id,
        'depth' => 1,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $grandAncestor->id,
        'descendant_id' => $buyer->id,
        'depth' => 2,
    ]);

    app(StartBonusAccrualContract::class)->accrue($buyer->id, 100.00);

    $calculator = app(UserBalanceCalculator::class);

    foreach ([$ancestor, $grandAncestor] as $expectedRecipient) {
        $ledgerBalance = $calculator->balanceFor($expectedRecipient->id, BalanceTypeEnum::PARTNER, forceFresh: true);
        $cachedBalance = UserSummary::query()->findOrFail($expectedRecipient->id)->partner_balance;

        expect((float) $cachedBalance)->toBe((float) $ledgerBalance);
    }
});
