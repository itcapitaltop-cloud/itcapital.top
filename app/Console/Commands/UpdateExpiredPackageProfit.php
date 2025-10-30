<?php

namespace App\Console\Commands;

use App\Models\ItcPackage;
use Illuminate\Console\Command;

class UpdateExpiredPackageProfit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packages:expired-set-profit {--uuid=}';

    protected $description = 'Устанавливает доходность 5.5% для истекших пакетов';

    public function handle(): int
    {
        $query = ItcPackage::query()
            ->where('work_to', '<', now())
            ->where('month_profit_percent', '<>', 5.5)
            ->with(['reinvestProfits']);

        if ($uuid = $this->option('uuid')) {
            $query->where('uuid', $uuid);
        }

        $expired = $query->get();

        $count = 0;

        foreach ($expired as $package) {
            $balance = $package->transaction->amount;

            if ($balance > 100) {
                $package->month_profit_percent = 5.5;
            } else {
                $package->month_profit_percent = 0;
            }
            $package->save();

            $count++;
        }

        $this->info("Обновлено пакетов: {$count}");

        return self::SUCCESS;
    }
}
