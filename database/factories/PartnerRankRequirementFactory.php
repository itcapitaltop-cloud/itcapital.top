<?php

namespace Database\Factories;

use App\Models\PartnerRank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class PartnerRankRequirementFactory extends Factory
{
    public function withDefaultRequirements(): static
    {
        return $this->afterCreating(function () {
            $ranks = [];

            for ($i = 2; $i <= 8; $i++) {
                $ranks[$i] = PartnerRank::factory()->create([
                    'rank' => $i,
                    'bonus_usd' => match ($i) {
                        2 => 25.0,
                        3 => 50.0,
                        4 => 75.0,
                        5 => 125.0,
                        6 => 250.0,
                        7 => 350.0,
                        8 => 500.0,
                    },
                ]);
            }

            $rankStructure = [
                // Ранг 2
                ['partner_rank_id' => $ranks[2]->id, 'line' => null, 'required_turnover' => null, 'personal_deposit' => 500],

                // Ранг 3
                ['partner_rank_id' => $ranks[2]->id, 'line' => 1, 'required_turnover' => 1000, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[3]->id, 'line' => null, 'required_turnover' => null, 'personal_deposit' => 1000],

                // Ранг 4
                ['partner_rank_id' => $ranks[3]->id, 'line' => 1, 'required_turnover' => 2000, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[3]->id, 'line' => 2, 'required_turnover' => 2500, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[4]->id, 'line' => null, 'required_turnover' => null, 'personal_deposit' => 2000],

                // Ранг 5
                ['partner_rank_id' => $ranks[4]->id, 'line' => 1, 'required_turnover' => 2500, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[4]->id, 'line' => 2, 'required_turnover' => 3125, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[5]->id, 'line' => null, 'required_turnover' => null, 'personal_deposit' => 3000],

                // Ранг 6
                ['partner_rank_id' => $ranks[5]->id, 'line' => 1, 'required_turnover' => 4000, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[5]->id, 'line' => 2, 'required_turnover' => 5000, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[5]->id, 'line' => 3, 'required_turnover' => 6250, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[6]->id, 'line' => null, 'required_turnover' => null, 'personal_deposit' => 6000],

                // Ранг 7
                ['partner_rank_id' => $ranks[6]->id, 'line' => 1, 'required_turnover' => 5000, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[6]->id, 'line' => 2, 'required_turnover' => 6250, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[6]->id, 'line' => 3, 'required_turnover' => 7813, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[6]->id, 'line' => 4, 'required_turnover' => 9766, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[7]->id, 'line' => null, 'required_turnover' => null, 'personal_deposit' => 10000],

                // Ранг 8
                ['partner_rank_id' => $ranks[7]->id, 'line' => 1, 'required_turnover' => 7500, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[7]->id, 'line' => 2, 'required_turnover' => 9375, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[7]->id, 'line' => 3, 'required_turnover' => 11719, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[7]->id, 'line' => 4, 'required_turnover' => 14648, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[7]->id, 'line' => 5, 'required_turnover' => 18311, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[8]->id, 'line' => null, 'required_turnover' => null, 'personal_deposit' => 15000],

                // Ранг 9 (если есть)
                ['partner_rank_id' => $ranks[8]->id, 'line' => 1, 'required_turnover' => 10000, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[8]->id, 'line' => 2, 'required_turnover' => 12500, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[8]->id, 'line' => 3, 'required_turnover' => 15625, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[8]->id, 'line' => 4, 'required_turnover' => 19531, 'personal_deposit' => null],
                ['partner_rank_id' => $ranks[8]->id, 'line' => 5, 'required_turnover' => 24414, 'personal_deposit' => null],
            ];

            foreach ($rankStructure as $reqData) {
                \App\Models\PartnerRankRequirement::factory()->create([
                    'partner_rank_id' => $reqData['partner_rank_id'],
                    'line' => $reqData['line'],
                    'required_turnover' => $reqData['required_turnover'],
                    'personal_deposit' => $reqData['personal_deposit'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function definition(): array
    {
        return [
            'partner_rank_id' => 1,
            'line' => null,
            'required_turnover' => null,
            'personal_deposit' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
