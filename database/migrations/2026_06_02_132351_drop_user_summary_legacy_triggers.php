<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Drops the legacy PL/pgSQL functions and triggers that maintained `user_summary`.
 *
 * Why: the trigger logic duplicated the balance rules that live in
 * App\Enums\Transactions\TrxTypeEnum and drifted from them — most notably it
 * ignored `rejected_at` and `refresh_user_summary()` used a stale type list,
 * producing wrong admin balances (see plan financial-balances-integrity).
 *
 * The projection is now maintained exclusively by App\Services\User\UserSummaryService
 * through model observers (registered in AppServiceProvider). The former
 * `trg_user_summary_on_user_insert` row-creation behavior is replaced by
 * App\Observers\UserSummaryRowObserver.
 *
 * down() restores the exact original definitions captured at refactor time from
 * database/sql/user_summary_legacy_triggers.sql.
 */
return new class extends Migration
{
    /**
     * @var list<array{table: string, trigger: string}>
     */
    private array $triggers = [
        ['table' => 'transactions', 'trigger' => 'trg_user_summary_on_transaction'],
        ['table' => 'package_profit_reinvests', 'trigger' => 'trg_user_summary_on_reinvest'],
        ['table' => 'package_profit_reinvest_withdraws', 'trigger' => 'trg_reinvest_withdraw'],
        ['table' => 'partners', 'trigger' => 'trg_user_summary_on_partner'],
        ['table' => 'users', 'trigger' => 'trg_user_summary_user_insert'],
    ];

    /**
     * @var list<string>
     */
    private array $functions = [
        'trg_user_summary_on_transaction',
        'trg_user_summary_on_reinvest',
        'trg_user_summary_on_reinvest_withdraw',
        'trg_user_summary_on_partner',
        'trg_user_summary_on_user_insert',
        'refresh_user_summary',
    ];

    public function up(): void
    {
        foreach ($this->triggers as $trigger) {
            DB::statement("DROP TRIGGER IF EXISTS {$trigger['trigger']} ON {$trigger['table']}");
        }

        foreach ($this->functions as $function) {
            DB::statement("DROP FUNCTION IF EXISTS {$function}()");
        }
    }

    public function down(): void
    {
        $snapshot = database_path('sql/user_summary_legacy_triggers.sql');

        if (! is_file($snapshot)) {
            throw new RuntimeException(
                "Cannot restore legacy user_summary triggers: snapshot missing at {$snapshot}"
            );
        }

        DB::unprepared(file_get_contents($snapshot));
    }
};
