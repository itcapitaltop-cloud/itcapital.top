<?php

namespace App\Livewire\Account\CommonFund;

use App\Contracts\Packages\PackageReinvestRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Packages\CreatePackageReinvestDto;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\CommonFund\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\Package;
use App\Models\PackageReinvest;
use App\Models\Transaction;
use App\Traits\Livewire\CanCatchExceptionTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

class BuyPackage extends Component
{
    use CanCatchExceptionTrait;

    #[Validate(['required', 'numeric', 'min:0'])]
    public string $amount = '';

    public function submit(TransactionRepositoryContract $transactionRepo, PackageReinvestRepositoryContract $packageReinvestRepo): void
    {
//        Log::channel('source')->debug('submitByPackage');
        $this->validate();

        $transactionRepo->checkBalanceAndStore(new CreateTransactionDto(
            userId: Auth::id(),
            trxType: TrxTypeEnum::BUY_PACKAGE,
            balanceType: BalanceTypeEnum::MAIN,
            amount: $this->amount,
            prefix: 'ITC-'
        ), function (Transaction $transaction) use ($packageReinvestRepo) {
            $package = ItcPackage::query()->create([
                'uuid' => $transaction->uuid,
                'month_profit_percent' => '8.2'
            ]);
//            Log::channel('source')->debug($package);
            $packageReinvestRepo->store(new CreatePackageReinvestDto(
                packageUuid: $transaction->uuid,
                expire: $transaction->created_at->addWeeks(30)
            ));

            return $package;
        });

        $this->redirectRoute('commonFund');
    }

    public function render()
    {
        return view('livewire.account.common-fund.buy-package', [
            'packageTypes' => PackageTypeEnum::cases()
        ]);
    }
}
