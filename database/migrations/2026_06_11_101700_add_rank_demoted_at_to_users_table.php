<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'rank_demoted_at')) {
                $table->timestamp('rank_demoted_at')
                    ->nullable()
                    ->after('max_rank_awarded')
                    ->comment('Базлайн последнего понижения ранга: для повышения учитывается только оборот после этой даты');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rank_demoted_at');
        });
    }
};
