<?php

namespace App\Livewire\Forms\Account\Dashboard;

use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Activity\WriteBusinessActivityData;
use App\Dto\Finance\WithdrawDataTransferObject;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\CurrencyEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\PaymentSource;
use App\Models\Transaction;
use App\Models\Withdraw;
use App\Models\WithdrawFiatDetail;
use App\Services\ActivityLog\BusinessActivityLogger;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CreateWithdrawForm extends Form
{
    #[Validate(['required', 'in:crypto,fiat'])]
    public string $withdrawSource = 'crypto';

    #[Validate(['required', 'numeric', 'min:10'])]
    public string $withdrawAmount = '';

    #[Validate([
        'nullable',
        'string',
        'max:255',
        'required_without_all:sbpPhone,bankName,recipientName',
    ])]
    public string $address = '';

    #[Validate([
        'nullable',
        'required_without:address',
        // «+7» или «7» + десять цифр, пробелы/дефисы игнорируем
        'regex:/^\+?7[\s\-]?\d{3}[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2}$/',
    ])]
    public string $sbpPhone = '';

    #[Validate([
        'nullable',
        'required_without:address',
        'string',
        'min:2',
        'max:100',
    ])]
    public string $bankName = '';

    #[Validate(['nullable', 'required_without:address', 'string', 'max:255'])]
    public string $recipientName = '';

    public function store(TransactionRepositoryContract $transactionRepo): WithdrawDataTransferObject
    {

        $this->validate();

        $sourceId = PaymentSource::where('source', $this->withdrawSource)->value('id');

        $network = config('wallet.network');

        $transactionRepo->checkBalanceAndStore(new CreateTransactionDto(
            userId: Auth::id(),
            trxType: TrxTypeEnum::WITHDRAW,
            balanceType: BalanceTypeEnum::MAIN,
            amount: $this->withdrawAmount,
            prefix: 'WP-'
        ), function (Transaction $transaction) use ($network, $sourceId) {
            $withdraw = Withdraw::query()->create([
                'uuid' => $transaction->uuid,
                'payment_source_id' => $sourceId,
                'currency' => CurrencyEnum::fromNetwork($network),
                'commission' => BigDecimal::of($this->withdrawAmount)->multipliedBy('0.02')->plus('2'),
                'wallet_address' => $this->address ?: null,
            ]);

            if ($this->withdrawSource === 'fiat') {
                WithdrawFiatDetail::create([
                    'uuid' => $transaction->uuid,
                    'sbp_phone' => $this->sbpPhone,
                    'bank_name' => $this->bankName,
                    'recipient_name' => $this->recipientName,
                ]);
            }

            app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                type: ActivityEventTypeEnum::WithdrawRequested,
                userId: Auth::id(),
                subject: $withdraw,
                feeds: [ActivityFeedTypeEnum::Finance, ActivityFeedTypeEnum::UserDetailUser],
                properties: [
                    'amount' => $this->withdrawAmount,
                    'currency' => $withdraw->currency->value,
                    'payment_source' => $withdraw->paymentSource?->source,
                    'wallet_address' => $withdraw->wallet_address,
                    'bank_name' => $this->withdrawSource === 'fiat' ? $this->bankName : null,
                ],
                causer: Auth::user(),
                logName: 'finance',
                context: 'account',
            ));
        });

        return new WithdrawDataTransferObject(
            paymentSources: $sourceId,
            amount: $this->withdrawAmount,
            walletAddress: $this->address,
            commission: BigDecimal::of($this->withdrawAmount)->multipliedBy('0.02')->plus('2'),
            phone: $this->sbpPhone,
            nameBank: $this->bankName,
            fullname: $this->recipientName,
        );
    }
}
