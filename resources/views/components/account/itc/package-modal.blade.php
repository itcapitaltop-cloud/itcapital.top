<x-widget.modal>
    <x-bg.section-slim>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm {{ $package->type->getTextColor() }}">{{ $package->type->getName() }}</p>
                <h3 class="text-white text-base -mt-0.5">{{ __('components_account_itc_package_modal_itc') }}</h3>
            </div>
            <figure class="cursor-pointer" x-on:click="isModalActive = false">
                <img class="icon-white w-3" src="{{ vite()->icon('/actions/cancel.svg') }}" alt="">
            </figure>
        </div>
    </x-bg.section-slim>
    <x-bg.section-slim>
        <div class="grid grid-cols-2 gap-6">
            <div>
                <h3 class="text-gray-300 text-sm">{{ __('components_account_itc_package_modal_deposit') }}</h3>
                <h4 class="text-base text-white">{{ scale($package->transaction->amount) }} ITC @if ($package->reinvest_profits_sum_amount > 0)
                        <span class="text-green">+
                            {{ scale($package->reinvest_profits_sum_amount) }}
                        </span>
                    @endif
                </h4>
            </div>
            <div>
                <h3 class="text-gray-300 text-sm">{{ __('components_account_itc_package_modal_dividends') }}</h3>
                <h4 class="text-base text-white">{{ scale($package->getCurrentProfitAmount()) }} ITC</h4>
            </div>
            <div>
                <h3 class="text-gray-300 text-sm">{{ __('components_account_itc_package_modal_all_dividends') }}</h3>
                <h4 class="text-base text-white">{{ scale($package->profits_sum_amount) }} ITC</h4>
            </div>
        </div>
    </x-bg.section-slim>
    <x-bg.section-zero>
        <div class="flex gap-3">
            <button @disabled($package->getCurrentProfitAmount()->isEqualTo('0')) wire:click="profitReinvest('{{ $package->uuid }}')"
                title="{{ __('components_account_itc_package_reinvest_dividends') }}" type="button"
                class="flex-1 py-1.5 enabled:hover:bg-gray-450 disabled:opacity-70">
                <figure>
                    <img src="{{ vite()->icon('/actions/arrows-rotate.svg') }}" class="w-3 icon-green mx-auto"
                        alt="">
                </figure>
            </button>
            <button @disabled($package->getCurrentProfitAmount()->isEqualTo('0')) title="{{ __('components_account_itc_package_modal_withdraw_dividends') }}"
                wire:click="withdrawProfit('{{ $package->uuid }}')" type="button"
                class="flex-1 py-1.5 enabled:hover:bg-gray-450 disabled:opacity-70">
                <figure>
                    <img src="{{ vite()->icon('/actions/coin.svg') }}" class="w-3 icon-green mx-auto" alt="">
                </figure>
            </button>
            <button title="{{ __('components_account_itc_package_modal_reinvest_stock') }}" type="button" class="flex-1 py-1.5 hover:bg-gray-450">
                <figure>
                    <img src="{{ vite()->icon('/actions/cube.svg') }}" class="w-3 icon-yellow mx-auto" alt="">
                </figure>
            </button>
            <button title="{{ __('components_account_itc_package_modal_close_stock') }}" type="button" class="flex-1 py-1.5 hover:bg-gray-450">
                <figure>
                    <img src="{{ vite()->icon('/actions/cancel.svg') }}" class="w-3 icon-red mx-auto" alt="">
                </figure>
            </button>
        </div>
    </x-bg.section-zero>
</x-widget.modal>
