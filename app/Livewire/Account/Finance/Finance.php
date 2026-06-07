<?php

namespace App\Livewire\Account\Finance;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Transactions\TrxTypeEnum;
use App\Exceptions\Domain\InvalidAmountException;
use App\Livewire\Concerns\WithInfiniteFeed;
use App\Livewire\Forms\Account\Dashboard\CreateDepositForm;
use App\Livewire\Forms\Account\Dashboard\CreateWithdrawForm;
use App\Models\User;
use App\Services\ActivityLog\ActivityFeedService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Finance extends Component
{
    use WithInfiniteFeed;

    public CreateDepositForm $depositForm;

    public CreateWithdrawForm $withdrawForm;

    public function createDeposit()
    {
        $countApplicationsReplenishment = User::query()->withCount([
            'transactions as countApplicationsReplenishment' => fn ($query) => $query
                ->where('trx_type', TrxTypeEnum::DEPOSIT)
                ->whereNull('accepted_at')
                ->whereNull('rejected_at'),
        ])
            ->findOrFail(auth()->id())
            ->countApplicationsReplenishment;

        if ($countApplicationsReplenishment >= 2) {
            throw ValidationException::withMessages([
                'depositForm.depositAmount' => __('failed_count_applications_replenishment'),
            ]);
        }

        $deposit = $this->depositForm->store();

        if ($deposit !== null) {
            $this->dispatch('deposit-created-success', 'deposit', $deposit);
        }

        $this->dispatch('deposit-created');

    }

    public function createWithdraw()
    {
        if (! Carbon::now()->isSunday()) {
            $this->dispatch(
                'new-system-notification',
                type: 'warning',
                message: __('livewire_finance_withdrawal_only_on_sunday')
            );

            return;
        }

        $withdraw = $this->withdrawForm->store(app(TransactionRepositoryContract::class));

        if ($withdraw !== null) {
            $this->dispatch('deposit-created-success', 'withdraw', $withdraw);
        }

        $this->withdrawForm->reset();

        $this->dispatch('withdraw-created');

    }

    public function exception($e, $stopPropagation)
    {
        if ($e instanceof InvalidAmountException) {
            $this->dispatch('new-system-notification', type: 'error', message: $e->getMessage());
            $stopPropagation();
        }
    }

    public function mount()
    {
        // Finance или где создаётся форма
        $this->depositForm->depositAddress = config('wallet.deposit_address');
    }

    public function render()
    {
        [$operations, $operationsHasMore] = $this->paginateFeed(
            app(ActivityFeedService::class)
                ->financeFeed(Auth::id(), $this->feedFetchLimit())
                ->sortByDesc('created_at')
                ->values()
        );

        return view('livewire.account.finance.finance', [
            'operations' => $operations,
            'operationsHasMore' => $operationsHasMore,
            'countApplicationsReplenishment' => User::query()->withCount([
                'transactions as countApplicationsReplenishment' => fn ($query) => $query->where('trx_type', TrxTypeEnum::DEPOSIT)->whereNull('accepted_at')->whereNull('rejected_at'),
            ])
                ->findOrFail(auth()->id())
                ->countApplicationsReplenishment,
        ]);
    }
}
