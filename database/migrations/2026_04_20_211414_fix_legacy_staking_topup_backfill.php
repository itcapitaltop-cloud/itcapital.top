<?php

use App\Models\StakingPurchase;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        StakingPurchase::query()->where('id', 36)->update([
            'token_amount' => 13800,
            'purchase_rate' => 0.1,
        ]);

        StakingPurchase::query()->where('id', 37)->update([
            'token_amount' => 13800,
            'purchase_rate' => 0.1,
        ]);
    }
};
