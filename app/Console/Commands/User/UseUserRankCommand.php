<?php

declare(strict_types=1);

namespace App\Console\Commands\User;

use App\Models\User;
use App\Services\User\UserRankServices;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class UseUserRankCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:use-rank {--user= : пересчитать только указанного пользователя} {--no-bonus : не начислять бонус за повышение}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Назначение рангов для пользователей';

    /**
     * Execute the console command.
     */
    public function handle(UserRankServices $services): int
    {
        $skipBonus = (bool) $this->option('no-bonus');
        $userId = $this->option('user') ? (int) $this->option('user') : null;

        Log::info('[UseUserRankCommand.handle] started', [
            'user_id' => $userId,
            'skip_bonus' => $skipBonus,
        ]);

        $query = ! is_null($userId) ? User::whereKey($userId) : User::query();

        $processed = 0;
        $updated = 0;

        $query->chunkById(500, function (Collection $users) use (&$processed, &$updated, $services, $skipBonus) {
            $users->each(function (User $user) use (&$processed, &$updated, $services, $skipBonus) {
                $processed++;

                try {
                    Log::debug('[UseUserRankCommand.handle] recalculating user rank', [
                        'user_id' => $user->id,
                        'old_rank' => $user->rank,
                        'overridden_rank' => (bool) $user->overridden_rank,
                        'skip_bonus' => $skipBonus,
                    ]);

                    if ($services->recalculateAndUpdateRank($user, ! $skipBonus)) {
                        $updated++;
                    }
                } catch (Throwable $e) {
                    Log::error('[UseUserRankCommand.handle] user recalculation failed', [
                        'user_id' => $user->id,
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                    ]);

                    throw $e;
                }
            });

        });

        Log::info('[UseUserRankCommand.handle] completed', [
            'user_id' => $userId,
            'processed' => $processed,
            'updated' => $updated,
            'skip_bonus' => $skipBonus,
        ]);

        $this->info("Рангов пересчитано и сохранено: {$updated}");

        return Command::SUCCESS;
    }
}
