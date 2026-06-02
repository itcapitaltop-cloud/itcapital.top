<?php

use App\Actions\User\Partner\ProgressBarAction;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PartnerClosure;
use App\Models\PartnerRank;
use App\Models\PartnerRankRequirement;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;

it('builds line statistics for the requested user instead of the authenticated admin', function () {
    $admin = User::factory()->create();
    $sponsor = User::factory()->create(['rank' => 1]);
    $sponsorPartner = User::factory()->create();
    $adminPartner = User::factory()->create();

    $rankTwo = PartnerRank::factory()->create(['rank' => 2]);

    PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $rankTwo->id,
        'line' => 1,
        'required_turnover' => 5000,
        'personal_deposit' => null,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $sponsor->id,
        'descendant_id' => $sponsorPartner->id,
        'depth' => 1,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $admin->id,
        'descendant_id' => $adminPartner->id,
        'depth' => 1,
    ]);

    $sponsorTransaction = Transaction::factory()->create([
        'user_id' => $sponsorPartner->id,
        'amount' => 1000,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now(),
    ]);

    ItcPackage::factory()->create([
        'uuid' => $sponsorTransaction->uuid,
        'closed_at' => null,
    ]);

    $adminTransaction = Transaction::factory()->create([
        'user_id' => $adminPartner->id,
        'amount' => 7000,
        'trx_type' => TrxTypeEnum::BUY_PACKAGE,
        'accepted_at' => now(),
    ]);

    ItcPackage::factory()->create([
        'uuid' => $adminTransaction->uuid,
        'closed_at' => null,
    ]);

    $this->actingAs($admin);

    $bars = collect(ProgressBarAction::make()->run($sponsor->id));

    $lineBar = $bars->first(fn (array $bar): bool => (float) $bar['target'] === 5000.0);

    expect($bars)->toBeInstanceOf(Collection::class)
        ->and($lineBar)->not->toBeNull()
        ->and((float) $lineBar['current'])->toBe(1000.0)
        ->and((float) $lineBar['target'])->toBe(5000.0);
});

it('preserves pre-override line excess and fills missing manual-rank baseline', function () {
    $admin = User::factory()->create();
    $sponsor = User::factory()->create([
        'rank' => 1,
        'overridden_rank' => true,
        'overridden_rank_from' => now()->subDay(),
    ]);
    $lineOnePartner = User::factory()->create();
    $lineTwoPartner = User::factory()->create();

    $rankOne = PartnerRank::factory()->create(['rank' => 1]);
    PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $rankOne->id,
        'line' => 1,
        'required_turnover' => 4000,
        'personal_deposit' => null,
    ]);
    PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $rankOne->id,
        'line' => 2,
        'required_turnover' => 5000,
        'personal_deposit' => null,
    ]);

    $rankTwo = PartnerRank::factory()->create(['rank' => 2]);
    PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $rankTwo->id,
        'line' => 1,
        'required_turnover' => 5000,
        'personal_deposit' => null,
    ]);
    PartnerRankRequirement::factory()->create([
        'partner_rank_id' => $rankTwo->id,
        'line' => 2,
        'required_turnover' => 6000,
        'personal_deposit' => null,
    ]);

    PartnerClosure::factory()->create([
        'ancestor_id' => $sponsor->id,
        'descendant_id' => $lineOnePartner->id,
        'depth' => 1,
    ]);
    PartnerClosure::factory()->create([
        'ancestor_id' => $sponsor->id,
        'descendant_id' => $lineTwoPartner->id,
        'depth' => 2,
    ]);

    foreach ([
        [$lineOnePartner, 6500],
        [$lineTwoPartner, 3000],
    ] as [$partner, $amount]) {
        $transaction = Transaction::factory()->create([
            'user_id' => $partner->id,
            'amount' => $amount,
            'trx_type' => TrxTypeEnum::BUY_PACKAGE,
            'accepted_at' => now()->subDays(2),
        ]);

        ItcPackage::factory()->create([
            'uuid' => $transaction->uuid,
            'closed_at' => null,
        ]);
    }

    $this->actingAs($admin);

    $bars = collect(ProgressBarAction::make()->run($sponsor->id));
    $lineOneBar = $bars->first(fn (array $bar): bool => (float) $bar['target'] === 5000.0);
    $lineTwoBar = $bars->first(fn (array $bar): bool => (float) $bar['target'] === 6000.0);

    expect((float) $lineOneBar['current'])->toBe(6500.0)
        ->and((float) $lineTwoBar['current'])->toBe(3000.0);
});
