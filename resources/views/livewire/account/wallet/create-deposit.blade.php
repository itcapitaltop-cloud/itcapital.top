<form wire:submit="submit" class="mt-6">
    <x-bg.main>
        <x-bg.section>
            <h3 class="text-white font-normal text-base">{{ __('livewire_deposit_crypto_step_1') }}</h3>
            <div class="mt-3">
                <x-ui.input custom-label="bg-gray" name="amount" placeholder="{{ __('livewire_deposit_crypto_amount_placeholder') }}">{{ __('livewire_deposit_crypto_amount_label') }}</x-ui.input>
            </div>
        </x-bg.section>
        <x-bg.section>
            <h3 class="text-white font-normal text-base">
                {{ __('livewire_deposit_crypto_step_2') }}
            </h3>
        </x-bg.section>
        <x-bg.section>
            <h3 class="text-white font-normal text-base">{{ __('livewire_deposit_crypto_step_3') }}</h3>
            <div class="mt-3">
                <x-ui.input custom-label="bg-gray" name="transactionHash" placeholder="trx-hash">{{ __('livewire_deposit_crypto_transaction_hash_label') }}</x-ui.input>
            </div>
            <div class="grid grid-cols-6 mt-6">
                <div>
                    <x-ui.submit-button>{{ __('livewire_deposit_crypto_confirm') }}</x-ui.submit-button>
                </div>
            </div>
        </x-bg.section>
    </x-bg.main>
</form>
