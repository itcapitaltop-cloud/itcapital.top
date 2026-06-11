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
            if (! Schema::hasColumn('users', 'rank_demotion_period_end')) {
                $table->timestamp('rank_demotion_period_end')
                    ->nullable()
                    ->after('rank_demoted_at')
                    ->comment('Конец расчётного окна, за которое понижение уже применено: защищает от повторного понижения за тот же месяц');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rank_demotion_period_end');
        });
    }
};
