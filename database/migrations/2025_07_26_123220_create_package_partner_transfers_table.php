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
        Schema::create('package_partner_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('package_uuid');

            $table->foreign('uuid')
                ->references('uuid')->on('transactions')
                ->cascadeOnDelete();

            $table->foreign('package_uuid')
                ->references('uuid')->on('itc_packages')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_partner_transfers');
    }
};
