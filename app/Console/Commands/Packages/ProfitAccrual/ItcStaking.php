<?php

declare(strict_types=1);

namespace App\Console\Commands\Packages\ProfitAccrual;

use Carbon\Carbon;
use Illuminate\Console\Command;

final class ItcStaking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profit-accrual:itc-staking
                            {--as-of= : Посчитать начисление на указанную дату и время, например "2026-04-01 00:00:00"}';

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
        $asOf = $this->option('as-of');

        new \App\Actions\Packages\ProfitAccural\ItcStaking()->execute(
            $asOf ? Carbon::parse((string) $asOf) : null
        );

        return Command::SUCCESS;
    }
}
