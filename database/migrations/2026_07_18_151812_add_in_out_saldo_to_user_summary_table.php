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
        Schema::table('user_summary', function (Blueprint $table) {
            $table->decimal('in_out_saldo', 18, 2)->default(0);
        });

        // Backfill from the ledger: accepted deposits minus accepted withdraws
        // (same formula as the IN / OUT block on the admin user detail page).
        DB::statement(<<<'SQL'
            UPDATE user_summary us
            SET in_out_saldo = COALESCE(t.saldo, 0)
            FROM (
                SELECT user_id,
                       SUM(CASE WHEN trx_type = 'deposit' THEN amount ELSE -amount END) AS saldo
                FROM transactions
                WHERE trx_type IN ('deposit', 'withdraw')
                  AND accepted_at IS NOT NULL
                GROUP BY user_id
            ) t
            WHERE t.user_id = us.user_id
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_summary', function (Blueprint $table) {
            $table->dropColumn('in_out_saldo');
        });
    }
};
