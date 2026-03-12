@php
    use App\Enums\Transactions\PaymentSourcesEnum;
@endphp

<div x-data="{ isDepositSuccessModalActive: @entangle('isDepositSuccessModalActive') }">
    <x-widget.modal condition-name="isDepositSuccessModalActive" max-width="xl"
                    class="p-4 md:min-w-[447px] w-full max-w-[447px]">

        @if (!empty($data))
            <x-bg.section-slim class="!px-1 !py-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-white font-dela text-base md:text-[20px]">
                        {{ $type === 'deposit' ? __('application_created') : __('application_withdraw') }}
                    </h3>

                    <figure class="cursor-pointer" x-on:click="isDepositSuccessModalActive = false">
                        <img class="icon-white w-4" src="{{ vite()->icon('/actions/cancel.svg') }}" alt="">
                    </figure>
                </div>
            </x-bg.section-slim>

            <x-bg.section-slim class="!px-1 !py-2">
                <div class="flex justify-between gap-4 mb-4">
                    <span class="text-white/60 text-xs md:text-sm">
                        {{ $type === 'deposit' ? __('livewire_finance_deposit_source_label') : __('livewire_finance_withdraw_format_label') }}
                    </span>

                    <span class="font-medium text-white text-xs md:text-sm">
                        {{ PaymentSourcesEnum::Crypto->value === $data['paymentSources'] ? __('livewire_finance_deposit_source_crypto') : __('livewire_finance_deposit_source_fiat') }}
                    </span>
                </div>

                <div class="flex justify-between gap-4 mb-4">
                    <span class="text-white/60 text-xs md:text-sm">
                        {{ __('livewire_finance_deposit_amount_label') }}
                    </span>

                    <span class="font-medium text-white text-xs md:text-sm">
                        {{ $data['amount'] }}
                    </span>
                </div>

                @if ($type === 'withdraw')
                    <div class="flex justify-between gap-4 mb-4">
                        <span class="text-white/60 text-xs md:text-sm">
                            {{ __('commission') }}
                        </span>

                        <span class="font-medium text-white text-xs md:text-sm">
                            {{ $data['commission'] }}
                        </span>
                    </div>
                @endif


                @if (PaymentSourcesEnum::Crypto->value === $data['paymentSources'])
                    <div class="flex justify-between gap-4 mb-4">
                        <span class="text-white/60 text-xs md:text-sm">
                            {{ __('livewire_finance_deposit_wallet_address_label') }}
                        </span>

                        <span class="font-medium text-white text-xs md:text-sm break-all text-right">
                            {{ $data['walletAddress'] }}
                        </span>
                    </div>
                @endif

                @if ($type === 'deposit')
                    <div class="flex justify-between gap-4 mb-4">
                        <span class="text-white/60 text-xs md:text-sm">
                            {{ PaymentSourcesEnum::Crypto->value === $data['paymentSources'] ? __('livewire_deposit_crypto_transaction_hash_label') : __('livewire_finance_deposit_bank_name_label_fiat') }}
                        </span>

                        <span class="font-medium text-white text-xs md:text-sm">
                            {{ $data['transactionHash'] ?? '' }}
                        </span>
                    </div>
                @endif

                @if ($type === 'withdraw' && PaymentSourcesEnum::Crypto->value !== $data['paymentSources'])
                    <div class="flex justify-between gap-4 mb-4">
                        <span class="text-white/60 text-xs md:text-sm">
                            {{ __('livewire_finance_withdraw_sbp_phone_label') }}
                        </span>

                        <span class="font-medium text-white text-xs md:text-sm">
                            {{ $data['phone'] }}
                        </span>
                    </div>
                @endif

                @if ($type === 'withdraw' && PaymentSourcesEnum::Crypto->value !== $data['paymentSources'])
                    <div class="flex justify-between gap-4 mb-4">
                        <span class="text-white/60 text-xs md:text-sm">
                            {{ __('livewire_finance_withdraw_bank_name_label') }}
                        </span>

                        <span class="font-medium text-white text-xs md:text-sm">
                            {{ $data['nameBank'] }}
                        </span>
                    </div>
                @endif

                @if ($type === 'withdraw' && PaymentSourcesEnum::Crypto->value !== $data['paymentSources'])
                    <div class="flex justify-between gap-4 mb-4">
                        <span class="text-white/60 text-xs md:text-sm">
                            {{ __('livewire_finance_withdraw_recipient_name_label') }}
                        </span>

                        <span class="font-medium text-white text-xs md:text-sm">
                            {{ $data['fullname'] }}
                        </span>
                    </div>
                @endif

                <x-ui.submit-button class="w-full" x-on:click="isDepositSuccessModalActive = false">
                    {{ __('accessibly') }}
                </x-ui.submit-button>
            </x-bg.section-slim>
        @endif

    </x-widget.modal>
</div>
