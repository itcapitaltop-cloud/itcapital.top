<?php

use App\Settings\GeneralSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_rates', function (Blueprint $table) {
            $table->id();
            $table->date('effective_from')->unique();
            $table->decimal('rate', 12, 6);
            $table->timestamps();
        });

        $currentRate = app(GeneralSetting::class)->exchange_rate_itc;

        if ($currentRate > 0) {
            DB::table('token_rates')->insert([
                'effective_from' => now()->toDateString(),
                'rate' => $currentRate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('token_rates');
    }
};
