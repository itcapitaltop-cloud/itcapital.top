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
        Schema::table('package_profit_reinvests', function (Blueprint $table) {
            // Реинвест — это одна строка с одной суммой и одной выплатой, поэтому здесь
            // хватает двух колонок состояния вместо отдельной таблицы-реестра, которая
            // понадобилась разблокировке тела пакета (одно тело даёт много частичных
            // разблокировок, один реинвест — ровно одну). Связь с выплатной транзакцией
            // уже держит package_profit_reinvest_withdraws, поэтому payout_transaction_uuid
            // здесь не нужен. Повторную выплату отсекает не индекс (на reinvest_uuid там
            // обычный, не уникальный), а скоуп duePayout(): его whereDoesntHave('withdraw')
            // убирает выплаченный реинвест из очереди.
            //
            // Обе колонки nullable и без default: существующие строки остаются
            // неразблокированными, бэкфилл не нужен.
            $table->timestamp('unlocked_at')->nullable()->after('matured_at');
            $table->timestamp('payout_at')->nullable()->after('unlocked_at');

            // Команда выплаты ежечасно сканирует очередь по payout_at.
            $table->index(['payout_at'], 'ppr_payout_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_profit_reinvests', function (Blueprint $table) {
            $table->dropIndex('ppr_payout_at_index');
            $table->dropColumn(['unlocked_at', 'payout_at']);
        });
    }
};
