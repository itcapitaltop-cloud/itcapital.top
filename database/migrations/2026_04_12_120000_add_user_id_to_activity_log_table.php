<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table): void {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('causer_id')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->index(['user_id', 'created_at']);
            });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table): void {
                $table->dropIndex(['user_id', 'created_at']);
                $table->dropConstrainedForeignId('user_id');
            });
    }
};
