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
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->foreignId('package_definition_id')->nullable()->after('package_type')
                ->constrained('package_definitions')->nullOnDelete();
        });

        $this->backfillPackageDefinitionIds();

        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropIndex(['package_type']);
            $table->dropIndex(['package_type', 'used_at']);
            $table->dropColumn('package_type');
            $table->index(['package_definition_id', 'used_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->string('package_type')->nullable()->after('code');
        });

        DB::statement('
            UPDATE promo_codes
            SET package_type = package_definitions.type
            FROM package_definitions
            WHERE package_definitions.id = promo_codes.package_definition_id
        ');

        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_definition_id');
            $table->string('package_type')->nullable(false)->change();
            $table->index('package_type');
            $table->index(['package_type', 'used_at']);
        });
    }

    private function backfillPackageDefinitionIds(): void
    {
        $packageTypes = DB::table('promo_codes')
            ->whereNull('package_definition_id')
            ->distinct()
            ->pluck('package_type');

        foreach ($packageTypes as $packageType) {
            $definitionId = $this->resolveDefinitionId((string) $packageType);

            if ($definitionId === null) {
                continue;
            }

            DB::table('promo_codes')
                ->where('package_type', $packageType)
                ->update(['package_definition_id' => $definitionId]);
        }
    }

    private function resolveDefinitionId(string $packageType): ?int
    {
        $bySlug = DB::table('package_definitions')
            ->where('slug', $packageType)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');

        if ($bySlug !== null) {
            return $bySlug;
        }

        return DB::table('package_definitions')
            ->where('type', $packageType)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');
    }
};
