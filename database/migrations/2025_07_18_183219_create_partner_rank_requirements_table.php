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
        Schema::create('partner_rank_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_rank_id')->constrained('partner_ranks')->cascadeOnDelete();
            $table->unsignedTinyInteger('line')->nullable();
            $table->decimal('required_turnover', 18, 2)->nullable();
            $table->decimal('personal_deposit', 18, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_rank_requirements');
    }
};
