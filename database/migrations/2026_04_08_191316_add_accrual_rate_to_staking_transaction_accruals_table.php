<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staking_transaction_accruals', function (Blueprint $table) {
            $table->decimal('accrual_rate', 12, 6)->nullable()->after('amount');
        });

        DB::statement(<<<'SQL'
            UPDATE staking_transaction_accruals AS sta
            SET accrual_rate = (
                SELECT tr.rate
                FROM token_rates AS tr
                WHERE tr.effective_from <= DATE(sta.created_at)
                ORDER BY tr.effective_from DESC
                LIMIT 1
            )
            WHERE sta.accrual_rate IS NULL
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staking_transaction_accruals', function (Blueprint $table) {
            $table->dropColumn('accrual_rate');
        });
    }
};
