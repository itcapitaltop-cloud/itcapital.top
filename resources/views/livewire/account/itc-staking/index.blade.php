<div x-data="{
    isModalBuyPackageActive: false,
    isModalBuyPackageMoreActive: false
}" x-on:itc:packages-refresh.window="$wire.$refresh()" class="relative">
    <div class="md:block hidden absolute top-[5px] right-[3px]">
        @if (count($packages) > 0)
            <x-ui.submit-button x-on:click="isModalBuyPackageMoreActive = true">
                {{ __('component_livewire_account_itc_staking_buy_more') }}
            </x-ui.submit-button>
        @else
            <x-ui.submit-button x-on:click="isModalBuyPackageActive = true">
                {{ __('components_account_common_header_buy_package_itc_staging') }}
            </x-ui.submit-button>
        @endif

    </div>
    <x-ui.card-tabs :tabs="[
        'packages' => __('packages'),
        'log' => __('livewire_finance_tab_log'),
    ]" class="mx-auto">

        <x-slot name="packages">
            <h3 class="font-bold transition-colors text-lime">{{ __('exchange_rate_itc', ['count' => $exchangeRateItc]) }}</h3>
            <div>
                <x-widget.modal condition-name="isModalBuyPackageActive" max-width="xl"
                    class="p-4 md:min-w-[447px] w-full max-w-[447px]">
                    <x-bg.section-slim class="!px-1 !py-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-white font-dela text-base md:text-[20px]">
                                {{ __('component_livewire_account_itc_staking_buy_packages') }}
                            </h3>
                            <figure class="cursor-pointer" x-on:click="isModalBuyPackageActive = false">
                                <img class="icon-white w-4" src="{{ vite()->icon('/actions/cancel.svg') }}"
                                    alt="">
                            </figure>
                        </div>
                    </x-bg.section-slim>

                    <x-bg.section-slim class="!px-1 !py-2">
                        <div class="flex flex-col sm:flex-row sm:items-center mb-6 md:mb-[32px] gap-2 sm:gap-0">
                            <p class="sm:mr-[102px] block text-sm md:text-base">
                                {{ __('components_account_dashboard_widget_balance_main_balance') }}
                            </p>
                            <p class="flex gap-2 items-center">
                                <img src="{{ vite()->icon('currency/itc.svg') }}" class="w-[12px]" alt="">
                                <span class="text-sm md:text-base">{{ number_format($mainBalance, 2, '.', '') }}</span>
                            </p>
                        </div>
                        <form wire:submit="buyPackage" x-on:bought.window="isModalBuyPackageActive = false">
                            <x-ui.input name="amount"
                                placeholder="{{ __('component_livewire_account_itc_staking_buy_packages_sum') }}"
                                validate="number" input-class="py-[5px] px-[12px]">
                                {{ __('component_livewire_account_itc_staking_buy_packages_form_label') }}
                            </x-ui.input>

                            <x-ui.submit-button action="buyPackage" class="w-full mt-6 md:mt-8">
                                {{ __('buy') }}
                            </x-ui.submit-button>
                        </form>
                    </x-bg.section-slim>
                </x-widget.modal>

                <x-widget.modal condition-name="isModalBuyPackageMoreActive" max-width="xl"
                    class="p-4 md:min-w-[447px] w-full max-w-[447px]">
                    <x-bg.section-slim class="!px-1 !py-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-white font-dela text-base md:text-[20px]">
                                {{ __('component_livewire_account_itc_staking_buy_more') }}
                            </h3>
                            <figure class="cursor-pointer" x-on:click="isModalBuyPackageMoreActive = false">
                                <img class="icon-white w-4" src="{{ vite()->icon('/actions/cancel.svg') }}"
                                    alt="">
                            </figure>
                        </div>
                    </x-bg.section-slim>

                    <x-bg.section-slim class="!px-1 !py-2">
                        <div class="flex flex-col sm:flex-row sm:items-center mb-6 md:mb-[32px] gap-2 sm:gap-0">
                            <p class="sm:mr-[102px] block text-sm md:text-base">
                                {{ __('components_account_dashboard_widget_balance_main_balance') }}
                            </p>
                            <p class="flex gap-2 items-center">
                                <img src="{{ vite()->icon('currency/itc.svg') }}" class="w-[12px]" alt="">
                                <span class="text-sm md:text-base">{{ number_format($mainBalance, 2, '.', '') }}</span>
                            </p>
                        </div>
                        <form wire:submit="buyPackageMore" x-on:bought.window="isModalBuyPackageMoreActive = false">
                            <x-ui.input name="amount"
                                placeholder="{{ __('component_livewire_account_itc_staking_buy_packages_sum') }}"
                                validate="number" input-class="py-[5px] px-[12px]">
                                {{ __('component_livewire_account_itc_staking_buy_packages_form_label') }}
                            </x-ui.input>

                            <x-ui.submit-button action="buyPackageMore" class="w-full mt-6 md:mt-8">
                                {{ __('buy') }}
                            </x-ui.submit-button>
                        </form>
                    </x-bg.section-slim>
                </x-widget.modal>

                <div class="md:hidden block">
                    <x-ui.submit-button x-on:click="isModalBuyPackageActive = true">
                        {{ __('components_account_common_header_buy_package') }}
                    </x-ui.submit-button>
                </div>
                @if (count($packages) > 0)
                    <div class="flex flex-col gap-6 md:gap-[44px] mt-4 md:mt-[20px]">
                        @foreach ($packages as $package)
                            <x-account.itc.package-staking :package="$package" />
                        @endforeach
                    </div>
                @else
                    <p class="opacity-[.3] font-medium text-sm md:text-base text-center text-gray-500">
                        {{ __('component_livewire_account_itc_staking_no_packages') }}
                    </p>
                @endif

            </div>
        </x-slot>

        <x-slot name="log">
            @if (count($logs) > 0)
                <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
                    <table class="w-full text-left min-w-[500px] md:min-w-full">
                        <thead>
                            <tr class="font-dela text-sm md:text-[16px] leading-8 md:leading-[40px]">
                                <th class="py-1.5 pr-4 font-thin">
                                    {{ __('livewire_dashboard_index_old_transactions_date_header') }}
                                </th>
                                <th class="py-1.5 font-thin">{{ __('event') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm md:text-base">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="py-1.5 pr-4 whitespace-nowrap">
                                        {{ $log->created_at->format('d.m.Y, H:i') }}</td>
                                    <td class="py-1.5 whitespace-nowrap">{{ $log->text }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-0">
                                        <div class="min-h-[300px] md:min-h-[400px] flex items-center justify-center">
                                            <p class="text-white/60 text-sm md:text-base">{{ __('no_event') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <p class="opacity-[.3] font-medium text-sm md:text-base text-center text-gray-500">
                    {{ __('component_livewire_account_itc_staking_no_packages') }}
                </p>
            @endif
        </x-slot>

    </x-ui.card-tabs>
</div>
