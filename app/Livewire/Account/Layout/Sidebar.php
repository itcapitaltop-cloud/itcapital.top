<?php

declare(strict_types=1);

namespace App\Livewire\Account\Layout;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Transactions\BalanceTypeEnum;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class Sidebar extends Component
{
    #[Locked]
    public string $mainBalance;

    public function boot(TransactionRepositoryContract $transactionRepositoryContract): void
    {
        $this->mainBalance = $transactionRepositoryContract->getBalanceAmountByUserIdAndType(auth()->user()->id, BalanceTypeEnum::MAIN);
    }

    public function render()
    {
        return view('livewire.account.layout.sidebar');
    }
}
