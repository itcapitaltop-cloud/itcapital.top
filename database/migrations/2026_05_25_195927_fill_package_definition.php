<?php

use App\Enums\Itc\PackageTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('package_definitions')
            ->insert([
                [
                    'slug' => PackageTypeEnum::STANDARD->value,
                    'type' => PackageTypeEnum::STANDARD->value,
                    'name' => 'Standard',
                    'min_start_amount' => '250.00',
                    'default_profit_percent' => '5.00',
                    'duration_months' => 1,
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'slug' => PackageTypeEnum::PRIVILEGE->value,
                    'type' => PackageTypeEnum::PRIVILEGE->value,
                    'name' => 'Privilege',
                    'default_profit_percent' => '8.00',
                    'min_start_amount' => '2500.00',
                    'duration_months' => 6,
                    'sort_order' => 2,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'slug' => PackageTypeEnum::VIP->value,
                    'type' => PackageTypeEnum::VIP->value,
                    'name' => 'VIP',
                    'default_profit_percent' => '10.00',
                    'min_start_amount' => '5000.00',
                    'duration_months' => 8,
                    'sort_order' => 3,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'slug' => PackageTypeEnum::PRESENT->value,
                    'type' => PackageTypeEnum::PRESENT->value,
                    'name' => 'Present',
                    'default_profit_percent' => '8.20',
                    'min_start_amount' => '0.00',
                    'duration_months' => 0,
                    'sort_order' => 4,
                    'is_active' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'slug' => PackageTypeEnum::STAKING->value,
                    'type' => PackageTypeEnum::STAKING->value,
                    'name' => 'Staking',
                    'default_profit_percent' => 2,
                    'min_start_amount' => 0,
                    'duration_months' => 0,
                    'sort_order' => 5,
                    'is_active' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('package_definitions')->whereIn('type', [
            PackageTypeEnum::STAKING->value,
            PackageTypeEnum::PRESENT->value,
            PackageTypeEnum::VIP->value,
            PackageTypeEnum::PRIVILEGE->value,
            PackageTypeEnum::STANDARD->value,
        ])->forceDelete();
    }
};
