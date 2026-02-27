<?php

use App\Models\ItcPackage;
use App\Models\User;
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
        Schema::create('staking_transaction_accruals', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ItcPackage::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignId('source_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->smallInteger('line')->nullable();
            $table->decimal('amount', 20, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('staking_transaction_accruals', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(ItcPackage::class);
            $table->dropConstrainedForeignIdFor(User::class);
        });

        Schema::dropIfExists('staking_transaction_accruals');
    }
};
