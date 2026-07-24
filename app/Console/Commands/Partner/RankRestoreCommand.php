<?php

declare(strict_types=1);

namespace App\Console\Commands\Partner;

use App\Models\User;
use App\Services\User\UserRankServices;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RankRestoreCommand extends Command
{
    protected $signature = 'partner:rank-restore {--user= : восстановить только указанного пользователя} {--dry-run : рассчитать восстановление без сохранения}';

    protected $description = 'Разовое восстановление рангов, понижённых ежемесячным обслуживанием: повышение до реально заслуженного по полному обороту и очистка базлайна понижения';

    public function handle(UserRankServices $services): int
    {
        if ((bool) config('rank.maintenance.enabled', false)) {
            $this->warn('RANK_MAINTENANCE_ENABLED=true — восстановление считает оборот от базлайна понижения. Выключите обслуживание перед прогоном.');
        }

        $dryRun = (bool) $this->option('dry-run');
        $userId = $this->option('user') ? (int) $this->option('user') : null;

        Log::info('[RankRestoreCommand.handle] started', [
            'user_id' => $userId,
            'dry_run' => $dryRun,
        ]);

        $query = User::withoutGlobalScope('notBanned')
            ->whereNotNull('rank_demoted_at')
            ->where(function ($builder): void {
                $builder->whereNull('overridden_rank')->orWhere('overridden_rank', false);
            });

        if (! is_null($userId)) {
            $query->whereKey($userId);
        }

        $processed = 0;
        $restored = 0;
        $cleared = 0;
        $report = [];

        $query->chunkById(500, function (Collection $users) use (&$processed, &$restored, &$cleared, &$report, $services, $dryRun): void {
            $users->each(function (User $user) use (&$processed, &$restored, &$cleared, &$report, $services, $dryRun): void {
                $processed++;

                try {
                    $currentRank = (int) $user->rank;
                    $targetRank = max($currentRank, $services->calculateRank($user));

                    if ($dryRun) {
                        Log::info('[RankRestoreCommand.handle] restore (dry-run, not persisted)', [
                            'user_id' => $user->id,
                            'username' => $user->username,
                            'old_rank' => $currentRank,
                            'new_rank' => $targetRank,
                            'max_rank_awarded' => (int) $user->max_rank_awarded,
                            'dry_run' => true,
                        ]);

                        $report[] = [$user->id, $user->username, $currentRank, $targetRank];

                        if ($targetRank > $currentRank) {
                            $restored++;
                        }
                        $cleared++;

                        return;
                    }

                    // Повышение до реально заслуженного ранга по полному обороту.
                    // Бонус повторно не начисляется — пик уже был награждён.
                    if ($services->recalculateAndUpdateRank($user, withBonus: false)) {
                        $restored++;
                    }

                    $report[] = [$user->id, $user->username, $currentRank, (int) $user->rank];

                    // Очистка базлайна понижения: recalculateAndUpdateRank сбрасывает
                    // rank_demoted_at только при достижении пика, а rank_demotion_period_end
                    // не трогает вовсе. Убираем оба, чтобы состояние было как до понижения.
                    $baselineCleared = DB::table('users')
                        ->where('id', $user->id)
                        ->where(function ($builder): void {
                            $builder->whereNotNull('rank_demoted_at')->orWhereNotNull('rank_demotion_period_end');
                        })
                        ->update([
                            'rank_demoted_at' => null,
                            'rank_demotion_period_end' => null,
                        ]);

                    if ($baselineCleared > 0) {
                        $cleared++;
                    }
                } catch (Throwable $e) {
                    Log::error('[RankRestoreCommand.handle] user restore failed', [
                        'user_id' => $user->id,
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                    ]);

                    throw $e;
                }
            });
        });

        Log::info('[RankRestoreCommand.handle] completed', [
            'user_id' => $userId,
            'processed' => $processed,
            'restored' => $restored,
            'cleared' => $cleared,
            'dry_run' => $dryRun,
        ]);

        if ($report !== []) {
            $this->table(['ID', 'Логин', 'Ранг был', 'Ранг стал'], $report);
        }

        $this->info($dryRun
            ? "Восстановлений рассчитано (dry-run, без сохранения): рангов поднято {$restored}, базлайнов к очистке {$cleared} из {$processed}"
            : "Восстановлено: рангов поднято {$restored}, базлайнов очищено {$cleared} из {$processed}");

        return self::SUCCESS;
    }
}
