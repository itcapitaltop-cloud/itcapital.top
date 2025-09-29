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
        Schema::create('partner_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('reward_type', 10);
            $table->unsignedTinyInteger('line');
            $table->decimal('amount', 18);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_rewards');
    }
};
