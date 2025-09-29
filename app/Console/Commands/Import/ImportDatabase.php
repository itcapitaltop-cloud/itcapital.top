<?php

namespace App\Console\Commands\Import;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\User;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Statement;

class ImportDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:import {users} {packages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected function getOnlyRecordsFromCsv(string $path, array $header): Collection
    {
        $csv = Reader::createFromPath($path);

        $users = $csv->getRecords($header);

        return collect([...$users])->slice(1);
    }

    /**
     * Execute the console command.
     */
    public function handle(TransactionRepositoryContract $transactionRepo)
    {
        $users = $this->getOnlyRecordsFromCsv(Storage::path($this->argument('users')), [
            0 => 'id',
            1 => 'password',
            2 => 'username',
            3 => 'first_name',
            4 => 'last_name',
            5 => 'email',
            6 => 'balance',
            8 => 'common_fund_balance'
        ]);
        $packages = $this->getOnlyRecordsFromCsv(Storage::path($this->argument('packages')), [
            0 => 'uuid',
            1 => 'user_id',
            2 => 'username',
            3 => 'deposit',
            4 => 'to_withdraw',
            6 => 'reinvested_profit',
            8 => 'type',
            9 => 'work_to'
        ]);
//        $this->table(['uuid', 'user_id', 'username', 'deposit', 'to_withdraw', 'reinvested_profit', 'type', 'work_to'], $packages);

//        $this->info($packages->first()['reinvested_profit']);

        DB::transaction(function () use ($users, $packages, $transactionRepo) {
            foreach ($users as $user) {



                DB::table('users')->insert([
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'password' => $user['password'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email']
                ]);

                $userPackages = $packages->where('user_id', $user['id']);

                $transactionRepo->commonStore(new CreateTransactionDto(
                    userId: $user['id'],
                    trxType: TrxTypeEnum::HIDDEN_DEPOSIT,
                    balanceType: BalanceTypeEnum::MAIN,
                    amount: BigDecimal::of($user['balance'])->plus($user['common_fund_balance'] ?? 0)->plus($userPackages->reduce(function (BigDecimal $carry, array $item) {
                        return $carry->plus($item['deposit']);
                    }, BigDecimal::of('0'))),
                    acceptedAt: Carbon::now(),
                    prefix: 'HD-'
                ));

                foreach ($userPackages as $package) {
                    $transaction = $transactionRepo->commonStore(new CreateTransactionDto(
                        userId: $user['id'],
                        trxType: TrxTypeEnum::BUY_PACKAGE,
                        balanceType: BalanceTypeEnum::MAIN,
                        amount: $package['deposit'],
                        acceptedAt: Carbon::now(),
                        prefix: 'ITC-',
                    ));

                    ItcPackage::query()->create([
                        'uuid' => $transaction->uuid,
                        'work_to' => $package['work_to'],
                        'type' => PackageTypeEnum::VIP,
                        'month_profit_percent' => '8.2'
                    ]);

                    PackageProfit::query()->create([
                        'package_uuid' => $transaction->uuid,
                        'amount' => BigDecimal::of($package['to_withdraw'] ?? 0)->plus($package['reinvested_profit'] === "" ? 0 : $package['reinvested_profit']),
                        'uuid' => 'PP-'.Str::random(10)
                    ]);

                    PackageProfitReinvest::query()->create([
                        'package_uuid' => $transaction->uuid,
                        'amount' => $package['reinvested_profit'] === "" ? 0 : $package['reinvested_profit'],
                        'uuid' => 'PP-'.Str::random(10)
                    ]);
                }
            }
        });

        $this->info('Database successfully imported!');
    }
}
