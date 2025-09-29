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
        Schema::table('itc_packages', function (Blueprint $table) {
            $table->dateTime('closed_at')->nullable()->after('work_to');
            $table->decimal('residual_amount', 18, 2)->default(0)->after('closed_at');
            $table->boolean('is_prolonged')->default(false)->after('residual_amount');
            $table->dateTime('prolonged_to')->nullable()->after('is_prolonged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itc_packages', function (Blueprint $table) {
            //
        });
    }
};
