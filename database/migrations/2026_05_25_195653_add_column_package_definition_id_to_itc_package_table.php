<?php

use App\Models\Package\PackageDefinition;
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
        Schema::table('itc_package', function (Blueprint $table) {
            $table->foreignIdFor(PackageDefinition::class)->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itc_package', function (Blueprint $table) {
            $table->dropForeignIdFor(PackageDefinition::class);
        });
    }
};
