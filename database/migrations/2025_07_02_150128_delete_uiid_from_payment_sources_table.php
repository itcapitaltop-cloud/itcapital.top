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
        Schema::table('payment_sources', function (Blueprint $table) {
            Schema::table('payment_sources', function (Blueprint $table) {
                if (Schema::hasColumn('payment_sources', 'uuid')) {
                    $table->dropColumn('uuid');
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_sources', function (Blueprint $table) {
            //
        });
    }
};
