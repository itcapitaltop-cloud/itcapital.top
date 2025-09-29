<?php

use App\Enums\Partners\PartnerRewardTypeEnum;
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
        Schema::create('partner_level_percents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_level_id')->constrained('partner_levels')->cascadeOnDelete();
            $table->string('bonus_type', 10);
            $table->unsignedTinyInteger('line');
            $table->decimal('percent', 5, 2);
            $table->unique(['partner_level_id', 'bonus_type', 'line']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_level_percents');
    }
};
