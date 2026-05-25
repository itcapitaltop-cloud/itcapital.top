<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Models\Package\PackageDefinition;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        PackageDefinition::query()
            ->insert([
                [
                    'id' => 1,
                    'name' => 'standart',
                    'min_start_amount' => 250,
                    'default_profit_percent' => 5,
                    'duration_months' => 1,
                    'type' => PackageTypeEnum::STANDARD,
                ],
                [
                    'id' => 2,
                    'name' => 'privilege',
                    'default_profit_percent' => 8,
                    'min_start_amount' => 2500,
                    'duration_months' => 6,
                    'type' => PackageTypeEnum::PRIVILEGE,
                ],
                [
                    'id' => 3,
                    'name' => 'vip',
                    'default_profit_percent' => 10,
                    'min_start_amount' => 5000,
                    'duration_months' => 8,
                    'type' => PackageTypeEnum::VIP,
                ],
                [
                    'id' => 4,
                    'name' => 'present',
                    'default_profit_percent' => 8.2,
                    'min_start_amount' => 0,
                    'duration_months' => 0,
                    'type' => PackageTypeEnum::PRESENT,
                ],
                [
                    'id' => 5,
                    'name' => 'staking',
                    'default_profit_percent' => 2,
                    'min_start_amount' => 0,
                    'duration_months' => 0,
                    'type' => PackageTypeEnum::STAKING,
                ],
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PackageDefinition::query()->whereIn([1, 2, 3, 4, 5])->forceDelete();
    }
};
