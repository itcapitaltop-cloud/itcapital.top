<?php

declare(strict_types=1);

namespace App\Console\Commands\User;

use App\Models\User;
use App\Services\User\UserRankServices;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

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

        $query = ! is_null($userId) ? User::whereKey($userId) : User::query();

        $updated = 0;

        $query->chunkById(500, function (Collection $users) use (&$updated, $services, $skipBonus) {
            $users->map(function (User $user) use (&$updated, $services, $skipBonus) {
                if ($services->recalculateAndUpdateRank($user, ! $skipBonus)) {
                    $updated++;
                }
            });

        });

        $this->info("Рангов пересчитано и сохранено: {$updated}");

        return Command::SUCCESS;
    }
}
