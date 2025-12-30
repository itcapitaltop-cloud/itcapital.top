<?php

declare(strict_types=1);

namespace App\Console\Commands\Packages\ProfitAccrual;

use Illuminate\Console\Command;

final class ItcStaking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profit-accrual:itc-staking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Accrual of profit on packages ITC staking';

    /**
     * @return int
     *
     * @throws \Throwable
     */
    public function handle(): int
    {
        new \App\Actions\Packages\ProfitAccural\ItcStaking()->execute();

        return Command::SUCCESS;
    }
}
