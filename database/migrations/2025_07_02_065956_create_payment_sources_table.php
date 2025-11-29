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
        Schema::create('payment_sources', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->string('source');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropForeign(['payment_source_id']);
        });

        Schema::table('withdraws', function (Blueprint $table) {
            $table->dropForeign(['payment_source_id']);
        });

        Schema::dropIfExists('payment_sources');
    }
};
