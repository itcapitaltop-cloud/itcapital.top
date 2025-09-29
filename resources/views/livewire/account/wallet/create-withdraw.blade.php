<form wire:submit="submit" class="mt-6">
    <x-bg.main>
        <x-bg.section>
            <h3 class="text-white font-normal text-base">{{ __('livewire_withdraw_step_1') }}</h3>
            <div class="mt-3">
                <x-ui.input custom-label="bg-gray" name="amount" placeholder="{{ __('livewire_deposit_crypto_amount_placeholder') }}">{{ __('livewire_deposit_crypto_amount_label') }}</x-ui.input>
            </div>
            <h3 x-cloak x-show="$wire.amount && $wire.amount >= 10" class="text-white font-normal text-base mt-3 ml-3">
                {{ __('livewire_withdraw_withdrawable_amount') }} ≈ <span x-text="($wire.amount * 0.98 - 2).toFixed(0)"></span> USDT</h3>
        </x-bg.section>
        <x-bg.section>
            <h3 class="text-white font-normal text-base">{{ __('livewire_withdraw_step_2') }}</h3>
            <div class="mt-3">
                <x-ui.input custom-label="bg-gray" name="address" placeholder="WALLET_ADDRESS">{{ __('livewire_withdraw_address_label') }}</x-ui.input>
            </div>
            <div class="grid grid-cols-6 mt-6">
                <div>
                    <x-ui.submit-button>{{ __('livewire_deposit_crypto_confirm') }}</x-ui.submit-button>
                </div>
            </div>
        </x-bg.section>
        <x-bg.section>
            <h3 class="text-white font-normal text-base">{{ __('livewire_withdraw_min_amount') }}</h3>
            <h3 class="text-white font-normal text-base">{{ __('livewire_withdraw_fee_info') }}</h3>
        </x-bg.section>
    </x-bg.main>
</form>

