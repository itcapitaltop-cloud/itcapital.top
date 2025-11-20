<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if(!Schema::hasTable('user_summary')) {
            Schema::create('user_summary', function (Blueprint $table) {
                $table->bigInteger('user_id')->primary();
                $table->decimal('investments_sum', 18, 2)->default(0);
                $table->decimal('reinvests_sum', 18, 2)->default(0);
                $table->decimal('partner_balance', 18, 2)->default(0);
                $table->integer('partners_count')->default(0);
                $table->timestamp('first_package_at')->nullable();
                $table->decimal('buy_packages_sum', 18, 2)->default(0);
            });
        }

        // Обновляем функцию триггера с исправленными типами транзакций
        DB::unprepared("
            CREATE OR REPLACE FUNCTION public.trg_user_summary_on_transaction()
             RETURNS trigger
             LANGUAGE plpgsql
            AS \$\$
            DECLARE
              debit_types  TEXT[] := ARRAY[
                'deposit',
                'withdraw_package_profit',
                'withdraw_package_reinvest_profit',
                'withdraw_package',
                'hidden_deposit',
                'partner_to_main_self',
                'partner_transfer_in',
                'rank_bonus_accrual',
                'start_bonus_accrual',
                'regular_premium_accrual',
                'regular_premium_to_partner',
                'withdraw_package_to_balance'
              ];
              credit_types TEXT[] := ARRAY[
                'buy_package',
                'withdraw',
                'partner_to_main_self_mirror',
                'partner_transfer_out',
                'regular_premium_to_partner_mirror',
                'partner_bonus_rollback',
                'partner_to_package'
              ];

              partner_credit_types TEXT[] := ARRAY[
                'partner_to_main_self_mirror',
                'partner_transfer_out',
                'partner_to_package'
              ];

              commission NUMERIC;

            BEGIN
              IF TG_OP = 'INSERT' THEN
                -- при дебете (+investments_sum)
                IF NEW.balance_type <> 'partner' THEN
                  IF NEW.trx_type = 'deposit' AND NEW.accepted_at IS NOT NULL THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum + NEW.amount
                     WHERE user_id = NEW.user_id;
                  ELSIF NEW.trx_type = ANY(debit_types) AND NEW.trx_type <> 'deposit' THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum + NEW.amount
                     WHERE user_id = NEW.user_id;
                  END IF;
                END IF;

                -- при кредите (-investments_sum)
                IF NEW.trx_type = 'withdraw' AND NEW.accepted_at IS NOT NULL THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum - NEW.amount
                     WHERE user_id = NEW.user_id;
                ELSIF NEW.trx_type = ANY(credit_types) AND NEW.trx_type <> 'withdraw' THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum - NEW.amount
                   WHERE user_id = NEW.user_id;
                END IF;

                -- first_package_at
                IF NEW.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET first_package_at = LEAST(
                       COALESCE(first_package_at, NEW.accepted_at),
                       NEW.accepted_at
                     )
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET buy_packages_sum = buy_packages_sum + NEW.amount
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.balance_type = 'partner' THEN
                    IF NEW.trx_type = ANY(partner_credit_types) THEN
                        UPDATE user_summary
                           SET partner_balance = partner_balance - NEW.amount
                         WHERE user_id = NEW.user_id;
                    ELSE
                        UPDATE user_summary
                           SET partner_balance = partner_balance + NEW.amount
                         WHERE user_id = NEW.user_id;
                    END IF;
                END IF;

              ELSIF TG_OP = 'DELETE' THEN
                -- откат по OLD: дебет
                IF OLD.balance_type <> 'partner' THEN
                  IF OLD.trx_type = 'deposit' AND OLD.accepted_at IS NOT NULL THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum - OLD.amount
                     WHERE user_id = OLD.user_id;
                  ELSIF OLD.trx_type = ANY(debit_types) AND OLD.trx_type <> 'deposit' THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum - OLD.amount
                     WHERE user_id = OLD.user_id;
                  END IF;
                END IF;

                -- откат по OLD: кредит
                IF OLD.trx_type = 'withdraw' AND OLD.accepted_at IS NOT NULL THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum + OLD.amount
                     WHERE user_id = OLD.user_id;
                ELSIF OLD.trx_type = ANY(credit_types) AND OLD.trx_type <> 'withdraw' THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum + OLD.amount
                   WHERE user_id = OLD.user_id;
                END IF;

                -- откат first_package_at
                IF OLD.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET first_package_at = (
                       SELECT MIN(t.accepted_at)
                         FROM transactions t
                        WHERE t.user_id  = OLD.user_id
                          AND t.trx_type = 'buy_package'
                          AND t.accepted_at IS NOT NULL
                     )
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET buy_packages_sum = buy_packages_sum - OLD.amount
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.balance_type = 'partner' THEN
                    IF OLD.trx_type = ANY(partner_credit_types) THEN
                        UPDATE user_summary
                           SET partner_balance = partner_balance + OLD.amount
                         WHERE user_id = OLD.user_id;
                    ELSE
                        UPDATE user_summary
                           SET partner_balance = partner_balance - OLD.amount
                         WHERE user_id = OLD.user_id;
                    END IF;
                END IF;

              ELSIF TG_OP = 'UPDATE' THEN
                -- откат OLD
                IF OLD.balance_type <> 'partner' THEN
                  IF OLD.trx_type = 'deposit' AND OLD.accepted_at IS NOT NULL THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum - OLD.amount
                     WHERE user_id = OLD.user_id;
                  ELSIF OLD.trx_type = ANY(debit_types) AND OLD.trx_type <> 'deposit' THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum - OLD.amount
                     WHERE user_id = OLD.user_id;
                  END IF;
                END IF;

                IF OLD.trx_type = 'withdraw' AND OLD.accepted_at IS NOT NULL THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum + OLD.amount
                     WHERE user_id = OLD.user_id;
                ELSIF OLD.trx_type = ANY(credit_types) AND OLD.trx_type <> 'withdraw' THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum + OLD.amount
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET first_package_at = (
                       SELECT MIN(t.accepted_at)
                         FROM transactions t
                        WHERE t.user_id  = OLD.user_id
                          AND t.trx_type = 'buy_package'
                          AND t.accepted_at IS NOT NULL
                     )
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET buy_packages_sum = buy_packages_sum - OLD.amount
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.balance_type = 'partner' THEN
                    IF OLD.trx_type = ANY(partner_credit_types) THEN
                        UPDATE user_summary
                           SET partner_balance = partner_balance + OLD.amount
                         WHERE user_id = OLD.user_id;
                    ELSE
                        UPDATE user_summary
                           SET partner_balance = partner_balance - OLD.amount
                         WHERE user_id = OLD.user_id;
                    END IF;
                END IF;

                -- применение NEW
                IF NEW.balance_type <> 'partner' THEN
                  IF NEW.trx_type = 'deposit' AND NEW.accepted_at IS NOT NULL THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum + NEW.amount
                     WHERE user_id = NEW.user_id;
                  ELSIF NEW.trx_type = ANY(debit_types) AND NEW.trx_type <> 'deposit' THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum + NEW.amount
                     WHERE user_id = NEW.user_id;
                  END IF;
                END IF;

                IF NEW.trx_type = 'withdraw' AND NEW.accepted_at IS NOT NULL THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum - NEW.amount
                     WHERE user_id = NEW.user_id;
                ELSIF NEW.trx_type = ANY(credit_types) AND NEW.trx_type <> 'withdraw' THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum - NEW.amount
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET first_package_at = LEAST(
                       COALESCE(first_package_at, NEW.accepted_at),
                       NEW.accepted_at
                     )
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET buy_packages_sum = buy_packages_sum + NEW.amount
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.balance_type = 'partner' THEN
                    IF NEW.trx_type = ANY(partner_credit_types) THEN
                        UPDATE user_summary
                           SET partner_balance = partner_balance - NEW.amount
                         WHERE user_id = NEW.user_id;
                    ELSE
                        UPDATE user_summary
                           SET partner_balance = partner_balance + NEW.amount
                         WHERE user_id = NEW.user_id;
                    END IF;
                END IF;

              END IF;

              RETURN NULL;
            END;
            \$\$;
        ");

        // Пересчитываем все балансы в user_summary для синхронизации
        $this->recalculateAllBalances();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем старую версию триггера (оригинальную)
        DB::unprepared("
            CREATE OR REPLACE FUNCTION public.trg_user_summary_on_transaction()
             RETURNS trigger
             LANGUAGE plpgsql
            AS \$\$
            DECLARE
              debit_types  TEXT[] := ARRAY[
                'deposit',
                'withdraw_package_profit',
                'withdraw_package_reinvest_profit',
                'withdraw_package',
                'hidden_deposit',
                'partner_to_main_self',
                'partner_transfer_in',
                'rank_bonus_accrual',
                'start_bonus_accrual'
              ];
              credit_types TEXT[] := ARRAY[
                'buy_package',
                'withdraw'
              ];

              partner_credit_types TEXT[] := ARRAY[
                'partner_to_main_self_mirror',
                'partner_transfer_out',
                'partner_to_package'
              ];

              commission NUMERIC;

            BEGIN
              IF TG_OP = 'INSERT' THEN
                -- при дебете (+investments_sum)
                IF NEW.balance_type <> 'partner' THEN
                  IF NEW.trx_type = 'deposit' AND NEW.accepted_at IS NOT NULL THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum + NEW.amount
                     WHERE user_id = NEW.user_id;
                  ELSIF NEW.trx_type = ANY(debit_types) AND NEW.trx_type <> 'deposit' THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum + NEW.amount
                     WHERE user_id = NEW.user_id;
                  END IF;
                END IF;

                -- при кредите (-investments_sum)
                IF NEW.trx_type = 'withdraw' AND NEW.accepted_at IS NOT NULL THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum - NEW.amount
                     WHERE user_id = NEW.user_id;
                ELSIF NEW.trx_type = ANY(credit_types) AND NEW.trx_type <> 'withdraw' THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum - NEW.amount
                   WHERE user_id = NEW.user_id;
                END IF;

                -- first_package_at
                IF NEW.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET first_package_at = LEAST(
                       COALESCE(first_package_at, NEW.accepted_at),
                       NEW.accepted_at
                     )
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET buy_packages_sum = buy_packages_sum + NEW.amount
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.balance_type = 'partner' THEN
                    IF NEW.trx_type = ANY(partner_credit_types) THEN
                        UPDATE user_summary
                           SET partner_balance = partner_balance - NEW.amount
                         WHERE user_id = NEW.user_id;
                    ELSE
                        UPDATE user_summary
                           SET partner_balance = partner_balance + NEW.amount
                         WHERE user_id = NEW.user_id;
                    END IF;
                END IF;

              ELSIF TG_OP = 'DELETE' THEN
                -- откат по OLD: дебет
                IF OLD.balance_type <> 'partner' THEN
                  IF OLD.trx_type = 'deposit' AND OLD.accepted_at IS NOT NULL THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum - OLD.amount
                     WHERE user_id = OLD.user_id;
                  ELSIF OLD.trx_type = ANY(debit_types) AND OLD.trx_type <> 'deposit' THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum - OLD.amount
                     WHERE user_id = OLD.user_id;
                  END IF;
                END IF;

                -- откат по OLD: кредит
                IF OLD.trx_type = 'withdraw' AND OLD.accepted_at IS NOT NULL THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum + OLD.amount
                     WHERE user_id = OLD.user_id;
                ELSIF OLD.trx_type = ANY(credit_types) AND OLD.trx_type <> 'withdraw' THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum + OLD.amount
                   WHERE user_id = OLD.user_id;
                END IF;

                -- откат first_package_at
                IF OLD.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET first_package_at = (
                       SELECT MIN(t.accepted_at)
                         FROM transactions t
                        WHERE t.user_id  = OLD.user_id
                          AND t.trx_type = 'buy_package'
                          AND t.accepted_at IS NOT NULL
                     )
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET buy_packages_sum = buy_packages_sum - OLD.amount
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.balance_type = 'partner' THEN
                    IF OLD.trx_type = ANY(partner_credit_types) THEN
                        UPDATE user_summary
                           SET partner_balance = partner_balance + OLD.amount
                         WHERE user_id = OLD.user_id;
                    ELSE
                        UPDATE user_summary
                           SET partner_balance = partner_balance - OLD.amount
                         WHERE user_id = OLD.user_id;
                    END IF;
                END IF;

              ELSIF TG_OP = 'UPDATE' THEN
                -- откат OLD
                IF OLD.balance_type <> 'partner' THEN
                  IF OLD.trx_type = 'deposit' AND OLD.accepted_at IS NOT NULL THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum - OLD.amount
                     WHERE user_id = OLD.user_id;
                  ELSIF OLD.trx_type = ANY(debit_types) AND OLD.trx_type <> 'deposit' THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum - OLD.amount
                     WHERE user_id = OLD.user_id;
                  END IF;
                END IF;

                IF OLD.trx_type = 'withdraw' AND OLD.accepted_at IS NOT NULL THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum + OLD.amount
                     WHERE user_id = OLD.user_id;
                ELSIF OLD.trx_type = ANY(credit_types) AND OLD.trx_type <> 'withdraw' THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum + OLD.amount
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET first_package_at = (
                       SELECT MIN(t.accepted_at)
                         FROM transactions t
                        WHERE t.user_id  = OLD.user_id
                          AND t.trx_type = 'buy_package'
                          AND t.accepted_at IS NOT NULL
                     )
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET buy_packages_sum = buy_packages_sum - OLD.amount
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.balance_type = 'partner' THEN
                    IF OLD.trx_type = ANY(partner_credit_types) THEN
                        UPDATE user_summary
                           SET partner_balance = partner_balance + OLD.amount
                         WHERE user_id = OLD.user_id;
                    ELSE
                        UPDATE user_summary
                           SET partner_balance = partner_balance - OLD.amount
                         WHERE user_id = OLD.user_id;
                    END IF;
                END IF;

                -- применение NEW
                IF NEW.balance_type <> 'partner' THEN
                  IF NEW.trx_type = 'deposit' AND NEW.accepted_at IS NOT NULL THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum + NEW.amount
                     WHERE user_id = NEW.user_id;
                  ELSIF NEW.trx_type = ANY(debit_types) AND NEW.trx_type <> 'deposit' THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum + NEW.amount
                     WHERE user_id = NEW.user_id;
                  END IF;
                END IF;

                IF NEW.trx_type = 'withdraw' AND NEW.accepted_at IS NOT NULL THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum - NEW.amount
                     WHERE user_id = NEW.user_id;
                ELSIF NEW.trx_type = ANY(credit_types) AND NEW.trx_type <> 'withdraw' THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum - NEW.amount
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET first_package_at = LEAST(
                       COALESCE(first_package_at, NEW.accepted_at),
                       NEW.accepted_at
                     )
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET buy_packages_sum = buy_packages_sum + NEW.amount
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.balance_type = 'partner' THEN
                    IF NEW.trx_type = ANY(partner_credit_types) THEN
                        UPDATE user_summary
                           SET partner_balance = partner_balance - NEW.amount
                         WHERE user_id = NEW.user_id;
                    ELSE
                        UPDATE user_summary
                           SET partner_balance = partner_balance + NEW.amount
                         WHERE user_id = NEW.user_id;
                    END IF;
                END IF;

              END IF;

              RETURN NULL;
            END;
            \$\$;
        ");

        if(!Schema::hasTable('user_summary')) {
            Schema::dropIfExists('user_summary');
        }
    }

    /**
     * Пересчитывает все балансы в user_summary для синхронизации с транзакциями
     */
    private function recalculateAllBalances(): void
    {
        // Получаем всех пользователей с summary
        $users = DB::table('users')
            ->join('user_summary', 'users.id', '=', 'user_summary.user_id')
            ->select('users.id')
            ->get();

        foreach ($users as $user) {
            // Рассчитываем баланс по транзакциям
            $debits = [
                'deposit',
                'withdraw_package_profit',
                'withdraw_package_reinvest_profit',
                'withdraw_package',
                'hidden_deposit',
                'partner_to_main_self',
                'partner_transfer_in',
                'rank_bonus_accrual',
                'start_bonus_accrual',
                'regular_premium_accrual',
                'regular_premium_to_partner',
                'withdraw_package_to_balance'
            ];

            $credits = [
                'buy_package',
                'withdraw',
                'partner_to_main_self_mirror',
                'partner_transfer_out',
                'regular_premium_to_partner_mirror',
                'partner_bonus_rollback',
                'partner_to_package'
            ];

            $debitsList = "'" . implode("','", $debits) . "'";
            $creditsList = "'" . implode("','", $credits) . "'";

            $calculatedBalance = DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('balance_type', 'main')
                ->whereNull('rejected_at')
                ->selectRaw("
                    SUM(
                        CASE
                            WHEN trx_type IN ($debitsList) THEN amount
                            WHEN trx_type IN ($creditsList) THEN -amount
                            ELSE 0
                        END
                    ) as balance
                ")
                ->value('balance') ?? 0;

            DB::table('user_summary')
                ->where('user_id', $user->id)
                ->update(['investments_sum' => $calculatedBalance]);
        }
    }
};
