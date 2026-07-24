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
        $this->removeDuplicateReviews();

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });
    }

    /**
     * Keep a single review per user, preferring approved ones and then the
     * most recent, and delete the remaining duplicates.
     */
    private function removeDuplicateReviews(): void
    {
        $duplicateUserIds = DB::table('reviews')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $keepId = DB::table('reviews')
                ->where('user_id', $userId)
                ->orderByRaw("CASE WHEN status = 'approved' THEN 0 ELSE 1 END")
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->value('id');

            DB::table('reviews')
                ->where('user_id', $userId)
                ->where('id', '!=', $keepId)
                ->delete();
        }
    }
};
