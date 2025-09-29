<div class="flex flex-col gap-6 lg:flex-row">
    <div class="flex-1">
        <x-bg.main>
            <form wire:submit="submit">
                <x-bg.section>
                    <h3 class="text-white font-normal text-base">{{ __('livewire_account_common_buy_title') }}</h3>
                    <div class="flex mt-4 gap-6">
                        <div class="flex-1">
                            <x-ui.input name="amount" placeholder="{{ __('livewire_account_common_buy_amount_placeholder') }}" custom-label="bg-gray"
                                wire:model="amount">{{ __('livewire_account_common_buy_amount_label') }}</x-ui.input>
                        </div>
                        <div class="w-[200px]">
                            <x-ui.submit-button>{{ __('livewire_account_common_buy_submit_button') }}</x-ui.submit-button>
                        </div>
                    </div>
                </x-bg.section>
            </form>
        </x-bg.main>
    </div>
    <div class="w-[360px]">
        <x-bg.main>
            <x-bg.section>
                <p class="text-white">
                    {{ __('livewire_account_common_buy_info') }}
                </p>
            </x-bg.section>
        </x-bg.main>
    </div>
</div>
