<?php

namespace App\Livewire\Forms\Account\Dashboard;

use App\Dto\Activity\WriteBusinessActivityData;
use App\Dto\Finance\DepositDataTransferObject;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\CurrencyEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Deposit;
use App\Models\PaymentSource;
use App\Models\Transaction;
use App\Services\ActivityLog\BusinessActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CreateDepositForm extends Form
{
    #[Validate(['required', 'numeric', 'min:0'])]
    public string $depositAmount = '';

    #[Validate(['required', 'string', 'max:255'])]
    public string $transactionHash = '';

    #[Validate(['required', 'in:crypto,fiat'])]
    public string $depositSource = 'crypto';

    #[Validate(['string', 'max:255'])]
    public string $depositAddress = '';

    public function store(): DepositDataTransferObject
    {

        $this->validate();

        $sourceId = PaymentSource::where('source', $this->depositSource)->value('id');

        $network = config('wallet.network');

        $uuid = 'DP-' . Str::random(10);

        $result = DB::transaction(function () use ($network, $uuid, $sourceId) {
            Transaction::query()->create([
                'uuid' => $uuid,
                'amount' => $this->depositAmount,
                'trx_type' => TrxTypeEnum::DEPOSIT,
                'balance_type' => BalanceTypeEnum::MAIN,
                'user_id' => auth()->id(),
            ]);

            $deposit = Deposit::create([
                'uuid' => $uuid,
                'payment_source_id' => $sourceId,
                'transaction_hash' => $this->transactionHash,
                'currency' => CurrencyEnum::fromNetwork($network),
                'commission' => 0,
                'wallet_address' => config('wallet.deposit_address'),
            ]);

            app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                type: ActivityEventTypeEnum::DepositRequested,
                userId: auth()->id(),
                subject: $deposit,
                feeds: [ActivityFeedTypeEnum::Finance, ActivityFeedTypeEnum::UserDetailUser],
                properties: [
                    'amount' => $this->depositAmount,
                    'currency' => $deposit->currency->value,
                    'transaction_hash' => $deposit->transaction_hash,
                    'payment_source' => $deposit->paymentSource?->source,
                    'bank_name' => $this->depositSource === 'fiat' ? $deposit->transaction_hash : null,
                ],
                causer: auth()->user(),
                logName: 'finance',
                context: 'account',
            ));

            return new DepositDataTransferObject(
                paymentSources: $sourceId,
                amount: $this->depositAmount,
                transactionHash: $this->transactionHash,
                walletAddress: config('wallet.deposit_address'),
            );
        });

        $this->resetExcept('depositAddress');

        return $result;
    }
}
