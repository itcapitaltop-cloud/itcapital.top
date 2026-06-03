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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'max_rank_awarded')) {
                $table->integer('max_rank_awarded')->default(0)->after('rank');
            }
        });

        // Backfill: treat the currently-held rank as already-paid so existing
        // ranks never re-trigger the one-time rank bonus. Idempotent — re-running
        // only realigns rows where the awarded marker is still below the rank.
        DB::table('users')
            ->whereColumn('max_rank_awarded', '<', 'rank')
            ->update(['max_rank_awarded' => DB::raw('rank')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('max_rank_awarded');
        });
    }
};
