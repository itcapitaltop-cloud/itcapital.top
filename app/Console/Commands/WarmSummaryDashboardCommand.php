<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Admin\SummaryMetricsService;
use Illuminate\Console\Command;

class WarmSummaryDashboardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summary:warm-dashboard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recompute and cache the admin summary dashboard snapshot';

    /**
     * Execute the console command.
     */
    public function handle(SummaryMetricsService $metrics): int
    {
        $metrics->refresh();

        $this->info('Admin summary dashboard snapshot refreshed.');

        return self::SUCCESS;
    }
}
