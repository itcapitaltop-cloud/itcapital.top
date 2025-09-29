<div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 mt-3 gap-3">
    @foreach ($deposits as $deposit)
        <div class="bg-gray rounded-lg p-4">
            <div class="grid gap-4">
                <p class="text-gray-300">#{{ $deposit->uuid }}</p>
                <div class="flex gap-6 items-center">
                    <x-data.desc name="{{ __('livewire_deposit_crypto_amount_label') }}">
                        <p class="text-white">
                            {{ \Brick\Money\Money::of($deposit->amount, $deposit->balance_type->getCurrency()) }}</p>
                    </x-data.desc>
                    <x-data.desc name="{{ __('date_create') }}">
                        <p class="text-white">{{ $deposit->created_at->format('d.m.y') }}</p>
                    </x-data.desc>
                </div>
                <x-data.desc name="{{ __('livewire_dashboard_index_old_transactions_status_header') }}">
                    <x-data.trx-status :trx-status="$deposit->status" />
                </x-data.desc>
            </div>
        </div>
    @endforeach
</div>
