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
        Schema::create('package_body_unlocks', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('package_uuid');
            $table->decimal('amount', 16, 8);
            $table->timestamp('unlocked_at');
            $table->timestamp('payout_at');

            // Без внешнего ключа на transactions: строка выплаты уже висит в
            // package_balance_withdraws с каскадным удалением, поэтому второй каскад
            // молча стёр бы историю разблокировок, а nullOnDelete() вернул бы
            // выплаченную строку в очередь на повторную выплату. Инвариант
            // «одна разблокировка — одна выплата» держит уникальный индекс.
            $table->string('payout_transaction_uuid')->nullable()->unique('pbu_payout_transaction_uuid_unique');

            // Системное закрытие пакета: деньги уже вернулись телом пакета,
            // поэтому разблокировку нужно снять с очереди, а не выплатить.
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->foreign('package_uuid')
                ->references('uuid')->on('itc_packages')
                ->cascadeOnDelete();

            $table->index(['payout_at'], 'pbu_payout_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_body_unlocks');
    }
};
