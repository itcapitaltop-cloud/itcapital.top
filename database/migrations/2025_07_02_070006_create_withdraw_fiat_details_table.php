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
        Schema::create('withdraw_fiat_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('sbp_phone', 32);
            $table->string('bank_name');
            $table->string('recipient_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdraw_fiat_details');
    }
};
