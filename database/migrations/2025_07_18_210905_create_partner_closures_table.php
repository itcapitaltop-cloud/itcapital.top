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
        Schema::create('partner_closures', function (Blueprint $table) {
            $table->foreignId('ancestor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('descendant_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('depth');

            $table->primary(['ancestor_id', 'descendant_id']);
            $table->index('depth');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_closures');
    }
};
