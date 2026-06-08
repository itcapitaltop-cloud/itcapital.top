<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\User\UserRankServices;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class UsersRecalcRankCommand extends Command
{
    protected $signature = 'users:recalc-rank {--user= : пересчитать только указанного пользователя} {--no-bonus : не начислять бонус за повышение}';

    protected $description = 'Пересчитать фактический ранг и записать в поле users.rank';

    public function handle(UserRankServices $services): int
    {
        $skipBonus = (bool) $this->option('no-bonus');
        $userId = $this->option('user') ? (int) $this->option('user') : null;

        Log::info('[UsersRecalcRankCommand.handle] started', [
            'user_id' => $userId,
            'skip_bonus' => $skipBonus,
        ]);

        $query = ! is_null($userId)
            ? User::withoutGlobalScope('notBanned')->whereKey($userId)
            : User::query();

        $processed = 0;
        $updated = 0;

        $query->chunkById(500, function (Collection $users) use (&$processed, &$updated, $services, $skipBonus) {
            $users->each(function (User $user) use (&$processed, &$updated, $services, $skipBonus) {
                $processed++;

                try {
                    Log::debug('[UsersRecalcRankCommand.handle] recalculating user rank', [
                        'user_id' => $user->id,
                        'old_rank' => $user->rank,
                        'overridden_rank' => (bool) $user->overridden_rank,
                        'skip_bonus' => $skipBonus,
                    ]);

                    if ($services->recalculateAndUpdateRank($user, ! $skipBonus)) {
                        $updated++;
                    }
                } catch (Throwable $e) {
                    Log::error('[UsersRecalcRankCommand.handle] user recalculation failed', [
                        'user_id' => $user->id,
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                    ]);

                    throw $e;
                }
            });
        });

        Log::info('[UsersRecalcRankCommand.handle] completed', [
            'user_id' => $userId,
            'processed' => $processed,
            'updated' => $updated,
            'skip_bonus' => $skipBonus,
        ]);

        $this->info("Рангов пересчитано и сохранено: {$updated}");

        return self::SUCCESS;
    }
}
