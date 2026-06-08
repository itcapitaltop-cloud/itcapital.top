<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('promo_code_usages', function (Blueprint $table) {
            $table->foreignId('package_definition_id')->nullable()->after('package_type')
                ->constrained('package_definitions')->nullOnDelete();
        });

        DB::statement('
            UPDATE promo_code_usages
            SET package_definition_id = promo_codes.package_definition_id
            FROM promo_codes
            WHERE promo_codes.id = promo_code_usages.promo_code_id
        ');

        Schema::table('promo_code_usages', function (Blueprint $table) {
            $table->dropUnique(['promo_code_id', 'user_id', 'package_type']);
            $table->dropIndex(['user_id', 'package_type']);
            $table->dropColumn('package_type');

            $table->unique(['promo_code_id', 'user_id', 'package_definition_id']);
            $table->index(['user_id', 'package_definition_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo_code_usages', function (Blueprint $table) {
            $table->string('package_type')->nullable()->after('user_id');
        });

        DB::statement('
            UPDATE promo_code_usages
            SET package_type = package_definitions.type
            FROM package_definitions
            WHERE package_definitions.id = promo_code_usages.package_definition_id
        ');

        Schema::table('promo_code_usages', function (Blueprint $table) {
            $table->dropUnique(['promo_code_id', 'user_id', 'package_definition_id']);
            $table->dropIndex(['user_id', 'package_definition_id']);
            $table->dropConstrainedForeignId('package_definition_id');

            $table->string('package_type')->nullable(false)->change();
            $table->unique(['promo_code_id', 'user_id', 'package_type']);
            $table->index(['user_id', 'package_type']);
        });
    }
};
