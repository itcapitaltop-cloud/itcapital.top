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
        Schema::table('itc_packages', function (Blueprint $table) {
            $table->boolean('profit_percent_overridden')
                ->default(false)
                ->after('month_profit_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itc_packages', function (Blueprint $table) {
            $table->dropColumn('profit_percent_overridden');
        });
    }
};
