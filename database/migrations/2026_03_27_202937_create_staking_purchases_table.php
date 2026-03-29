<?php

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Models\User;
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
        Schema::create('staking_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ItcPackage::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->decimal('amount_usd', 20, 2);
            $table->decimal('token_amount', 20, 2);
            $table->decimal('purchase_rate', 12, 6);
            $table->timestamp('purchased_at');
            $table->timestamps();
        });

        $packages = DB::table('itc_packages')
            ->join('transactions', 'transactions.uuid', '=', 'itc_packages.uuid')
            ->leftJoin('staking_transaction_accruals', function ($join) {
                $join->on('staking_transaction_accruals.itc_package_id', '=', 'itc_packages.id')
                    ->where('staking_transaction_accruals.type', '=', 'topup_bonus');
            })
            ->where('itc_packages.type', PackageTypeEnum::STAKING->value)
            ->groupBy(
                'itc_packages.id',
                'transactions.user_id',
                'transactions.amount',
                'transactions.accepted_at',
                'itc_packages.created_at'
            )
            ->selectRaw('
                itc_packages.id as itc_package_id,
                transactions.user_id,
                transactions.amount as amount_usd,
                transactions.accepted_at,
                itc_packages.created_at,
                COALESCE(SUM(staking_transaction_accruals.amount), 0) / 100 as top_up_bonus_sum
            ')
            ->get();

        foreach ($packages as $package) {
            $tokenAmount = round((float) $package->amount_usd + (float) $package->top_up_bonus_sum, 2);

            if ($tokenAmount <= 0) {
                continue;
            }

            DB::table('staking_purchases')->insert([
                'itc_package_id' => $package->itc_package_id,
                'user_id' => $package->user_id,
                'amount_usd' => $package->amount_usd,
                'token_amount' => $tokenAmount,
                'purchase_rate' => round((float) $package->amount_usd / $tokenAmount, 6),
                'purchased_at' => $package->accepted_at ?? $package->created_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staking_purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(ItcPackage::class);
            $table->dropConstrainedForeignIdFor(User::class);
        });

        Schema::dropIfExists('staking_purchases');
    }
};
