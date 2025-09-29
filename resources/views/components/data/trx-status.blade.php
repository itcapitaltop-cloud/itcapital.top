@use('\App\Enums\Transactions\TransactionStatusEnum')

<p
    @class([
        'text-red' => $trxStatus->checkStatus(TransactionStatusEnum::REJECTED),
        'text-green' => $trxStatus->checkStatus(TransactionStatusEnum::ACCEPTED),
        'text-yellow' => $trxStatus->checkStatus(TransactionStatusEnum::MODERATE)
    ])
>
    {{ $trxStatus->getName() }}
</p>
