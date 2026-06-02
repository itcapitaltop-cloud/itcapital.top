<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('staking_purchases') || ! Schema::hasTable('token_rates')) {
            return;
        }

        $legacyRate = DB::table('token_rates')
            ->orderBy('effective_from')
            ->value('rate');
        $firstTokenRateDate = DB::table('token_rates')
            ->orderBy('effective_from')
            ->value('effective_from');

        if ($legacyRate === null || $firstTokenRateDate === null) {
            return;
        }

        DB::table('staking_purchases')
            ->whereDate('purchased_at', '<', $firstTokenRateDate)
            ->whereRaw('ABS(purchase_rate - 1) < 0.000001')
            ->whereRaw('ABS(amount_usd - token_amount) < 0.01')
            ->update([
                'amount_usd' => DB::raw('ROUND(token_amount * ' . (float) $legacyRate . ', 2)'),
                'purchase_rate' => round((float) $legacyRate, 6),
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible data correction for legacy staking purchases.
    }
};
