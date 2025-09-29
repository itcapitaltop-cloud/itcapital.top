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
        Schema::table('package_profit_reinvest_withdraws', function (Blueprint $table) {
            $table->renameColumn('package_uuid', 'reinvest_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_profit_reinvest_withdraws', function (Blueprint $table) {
            //
        });
    }
};
