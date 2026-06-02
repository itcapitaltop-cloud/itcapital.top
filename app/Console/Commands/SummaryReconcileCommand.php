<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserSummary;
use App\Services\User\UserSummaryService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Rebuilds the user_summary projection from the ledger.
 *
 * Self-healing safety net for the single-writer projection: it can re-derive
 * every user's summary, or report drift without writing (--dry-run). Iterates
 * with chunkById so it scales to large user tables and is safe to re-run.
 */
class SummaryReconcileCommand extends Command
{
    protected $signature = 'summary:reconcile
        {--user= : Reconcile a single user id only}
        {--dry-run : Report divergences without writing}
        {--chunk=500 : Users processed per chunk}';

    protected $description = 'Recompute user_summary from the transactions ledger (single source of truth)';

    public function handle(UserSummaryService $summaryService): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $singleUser = $this->option('user');

        $processed = 0;
        $mismatched = 0;

        $handle = function (User $user) use ($summaryService, $dryRun, &$processed, &$mismatched): void {
            $processed++;

            if ($dryRun) {
                $expected = $summaryService->computeFor($user->id);
                $current = UserSummary::query()->find($user->id);
                $diff = $this->diff($expected, $current);

                if ($diff !== []) {
                    $mismatched++;
                    $this->warn("user {$user->id}: " . json_encode($diff, JSON_UNESCAPED_UNICODE));
                    Log::warning('[summary:reconcile] divergence', ['user_id' => $user->id, 'diff' => $diff]);
                }

                return;
            }

            $summaryService->recompute($user->id);
        };

        if ($singleUser !== null) {
            // withoutGlobalScopes so banned users (excluded by the 'notBanned' scope) can be reconciled too.
            $user = User::query()->withoutGlobalScopes()->find((int) $singleUser);

            if ($user === null) {
                $this->error("User {$singleUser} not found.");

                return self::FAILURE;
            }

            $handle($user);
        } else {
            $query = User::query()->withoutGlobalScopes()->select(['id'])->orderBy('id');

            $query->chunkById($chunkSize, function ($users) use ($handle): void {
                foreach ($users as $user) {
                    $handle($user);
                }

                $this->info("…processed {$users->count()} users in this chunk");
            });
        }

        $mode = $dryRun ? 'DRY-RUN' : 'APPLIED';
        $this->info("[{$mode}] processed: {$processed}" . ($dryRun ? ", mismatched: {$mismatched}" : ''));
        Log::info('[summary:reconcile] done', ['mode' => $mode, 'processed' => $processed, 'mismatched' => $mismatched]);

        return self::SUCCESS;
    }

    /**
     * Compare expected computed values against the current summary row.
     *
     * @param array<string, mixed> $expected
     * @return array<string, array{current: mixed, expected: mixed}>
     */
    private function diff(array $expected, ?UserSummary $current): array
    {
        $diff = [];

        foreach ($expected as $field => $expectedValue) {
            $currentValue = $current?->getAttribute($field);

            // Numeric fields: the user_summary columns are numeric(18,2), so compare the
            // stored value against the computed value rounded to the column scale (HALF_UP,
            // matching PostgreSQL numeric rounding) to avoid false positives from precision.
            if (is_numeric($expectedValue) && is_numeric($currentValue)) {
                $expectedRounded = (string) BigDecimal::of((string) $expectedValue)->toScale(2, RoundingMode::HALF_UP);

                if (bccomp((string) $currentValue, $expectedRounded, 2) !== 0) {
                    $diff[$field] = ['current' => $currentValue, 'expected' => $expectedRounded];
                }

                continue;
            }

            if ((string) $currentValue !== (string) $expectedValue) {
                $diff[$field] = ['current' => $currentValue, 'expected' => $expectedValue];
            }
        }

        return $diff;
    }
}
