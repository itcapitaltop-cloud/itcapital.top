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
        Schema::create('notification_profit_readeds', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id');
            $table->unique('notification_id', 'upr_notification_id_unique');

            // FK на notifications.id, с каскадным удалением
            $table->foreign('notification_id')
                ->references('id')
                ->on('notifications')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_profit_readeds');
    }
};
