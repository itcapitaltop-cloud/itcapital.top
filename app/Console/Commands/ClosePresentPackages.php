<?php

namespace App\Console\Commands;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageZeroing;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ClosePresentPackages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'itc:close-present-packages';
    protected $description = 'Закрывает пакеты Present, срок работы которых истёк';

    public function __construct(private readonly TransactionRepositoryContract $repo)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        ItcPackage::query()
            ->where('type', PackageTypeEnum::PRESENT)
            ->where('work_to', '<=', now())
            ->whereDoesntHave('zeroing')
            ->with('transaction')
            ->chunkById(250, function ($packages) {
                foreach ($packages as $package) {

                    $initialAmount = $package->transaction->amount ?? 0;

                    if ($initialAmount == 0) {
                        continue;
                    }

                    // создаём транзакцию
                    $dto = new CreateTransactionDto(
                        userId:      $package->transaction->user_id,
                        trxType:     TrxTypeEnum::ZERO_PRESENT_PACKAGE,
                        balanceType: $package->transaction->balance_type,
                        amount:      -$initialAmount,
                        acceptedAt:  Carbon::now(),
                        prefix:      'ITC-',
                    );

                    $result = $this->repo->store($dto, fn () => null);
                    $trx    = $result['transaction'];

                    // сохраняем связь через uuid
                    PackageZeroing::create([
                        'package_uuid'     => $package->uuid,
                        'transaction_uuid' => $trx->uuid,
                    ]);
                }
            });

        return self::SUCCESS;
    }
}
