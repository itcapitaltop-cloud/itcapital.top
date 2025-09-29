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
        Schema::create('package_profit_with_reinvest_links', function (Blueprint $table) {
            $table->id();

            $table->string('reinvest_uuid');
            $table->string('profit_uuid')->unique();

            $table->foreign('reinvest_uuid')
                ->references('uuid')->on('package_profit_reinvests')
                ->cascadeOnDelete();

            $table->foreign('profit_uuid')
                ->references('uuid')->on('package_profits')
                ->cascadeOnDelete();

            $table->index('reinvest_uuid');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_profit_with_reinvest_links');
    }
};
