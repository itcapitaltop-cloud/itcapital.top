<?php

declare(strict_types=1);

namespace App\Console\Commands\Partner;

use App\Models\User;
use App\Services\User\UserRankServices;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class RankMaintenanceCommand extends Command
{
    protected $signature = 'partner:rank-maintenance {--user= : обслужить только указанного пользователя} {--dry-run : рассчитать понижения без сохранения}';

    protected $description = 'Ежемесячное обслуживание ранга: постепенное понижение при невыполнении требований по линиям за предыдущий месяц';

    public function handle(UserRankServices $services): int
    {
        if (! (bool) config('rank.maintenance.enabled', false)) {
            Log::info('[RankMaintenanceCommand.handle] skipped: rank maintenance disabled');
            $this->info('Понижение ранга отключено настройкой RANK_MAINTENANCE_ENABLED.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $userId = $this->option('user') ? (int) $this->option('user') : null;

        $periodStart = now()->subMonthNoOverflow()->startOfMonth();
        $periodEnd = now()->startOfMonth();

        Log::info('[RankMaintenanceCommand.handle] started', [
            'user_id' => $userId,
            'dry_run' => $dryRun,
            'period_start' => $periodStart->toDateTimeString(),
            'period_end' => $periodEnd->toDateTimeString(),
        ]);

        $query = ! is_null($userId)
            ? User::withoutGlobalScope('notBanned')->whereKey($userId)
            : User::query();

        $processed = 0;
        $demoted = 0;

        $query->chunkById(500, function (Collection $users) use (&$processed, &$demoted, $services, $periodStart, $periodEnd, $dryRun): void {
            $users->each(function (User $user) use (&$processed, &$demoted, $services, $periodStart, $periodEnd, $dryRun): void {
                $processed++;

                try {
                    if ($services->applyMonthlyMaintenance($user, $periodStart, $periodEnd, $dryRun)) {
                        $demoted++;
                    }
                } catch (Throwable $e) {
                    Log::error('[RankMaintenanceCommand.handle] user maintenance failed', [
                        'user_id' => $user->id,
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                    ]);

                    throw $e;
                }
            });
        });

        Log::info('[RankMaintenanceCommand.handle] completed', [
            'user_id' => $userId,
            'processed' => $processed,
            'demoted' => $demoted,
            'dry_run' => $dryRun,
        ]);

        $this->info($dryRun
            ? "Понижений рассчитано (dry-run, без сохранения): {$demoted} из {$processed}"
            : "Рангов понижено: {$demoted} из {$processed}");

        return self::SUCCESS;
    }
}
