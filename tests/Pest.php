<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create a partner rank with an optional single line-turnover requirement.
 *
 * A rank with no line requirement is always "met" (used as a fallback rank
 * by the natural-rank calculation).
 */
function createPartnerRank(int $rank, ?float $lineRequired = null, int $line = 1, float $bonus = 0.0): \App\Models\PartnerRank
{
    $partnerRank = \App\Models\PartnerRank::factory()->create([
        'rank' => $rank,
        'bonus_usd' => $bonus,
    ]);

    if ($lineRequired !== null) {
        \App\Models\PartnerRankRequirement::factory()->create([
            'partner_rank_id' => $partnerRank->id,
            'line' => $line,
            'required_turnover' => $lineRequired,
            'personal_deposit' => null,
        ]);
    }

    return $partnerRank;
}

/**
 * Attach a downline on the given line to $ancestor and record a BUY_PACKAGE
 * turnover transaction accepted at $acceptedAt. Returns the downline user.
 */
function addPartnerLineTurnover(
    \App\Models\User $ancestor,
    float $amount,
    \DateTimeInterface $acceptedAt,
    int $line = 1
): \App\Models\User {
    $downline = \App\Models\User::factory()->create();

    \App\Models\PartnerClosure::factory()->create([
        'ancestor_id' => $ancestor->id,
        'descendant_id' => $downline->id,
        'depth' => $line,
    ]);

    \App\Models\Transaction::factory()->create([
        'user_id' => $downline->id,
        'trx_type' => \App\Enums\Transactions\TrxTypeEnum::BUY_PACKAGE,
        'balance_type' => \App\Enums\Transactions\BalanceTypeEnum::MAIN,
        'amount' => $amount,
        'accepted_at' => $acceptedAt,
        'rejected_at' => null,
    ]);

    return $downline;
}
