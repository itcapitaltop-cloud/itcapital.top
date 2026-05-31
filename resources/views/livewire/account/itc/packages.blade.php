<div x-data="{ isModalBuyPackageActive: false }" x-on:itc:packages-refresh.window="$wire.$refresh()" class="relative">
    <div class="md:block hidden absolute top-[5px] right-[3px]">
        <x-ui.submit-button x-on:click="isModalBuyPackageActive = true">
            {{ __('components_account_common_header_buy_package') }}
        </x-ui.submit-button>
    </div>
    <x-ui.card-tabs :tabs="[
        'packages' => __('packages'),
        'log' => __('livewire_finance_tab_log'),
    ]" class="mx-auto">

        <x-slot name="packages">
            <div>
                <x-widget.modal condition-name="isModalBuyPackageActive" max-width="sm"
                    class="p-4 md:min-w-[350px] min-w-[250px]">
                    <x-bg.section-slim class="!px-1 !py-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-white font-dela text-[20px] ">{{ __('livewire_itc_purchase_packages') }}
                            </h3>
                            <figure class="cursor-pointer" x-on:click="isModalBuyPackageActive = false">
                                <img class="icon-white w-4" src="{{ vite()->icon('/actions/cancel.svg') }}"
                                    alt="">
                            </figure>
                        </div>
                    </x-bg.section-slim>

                    <x-bg.section-slim class="!px-1 !py-2">
                        <form wire:submit="buyPackage" x-on:bought.window="isModalBuyPackageActive = false">
                            <x-ui.select name="selectedPackageDefinitionId" :options="$packageDefinitionOptions"
                                :placeholder="__('livewire_itc_package_placeholder')" :label="__('livewire_itc_package_label')"
                                :nullable="true" />
                            @error('selectedPackageDefinitionId')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror

                            <x-ui.input name="amount" placeholder="{{ $this->minimumAmountPlaceholder() }}"
                                validate="number" input-class="py-[5px] px-[12px]" class="mt-8">
                                {{ __('livewire_account_common_buy_amount_label') }}
                            </x-ui.input>

                            <x-ui.input name="promoCode" placeholder="{{ __('livewire_itc_promo_code_placeholder') }}"
                                class="mt-8 mb-5" input-class="py-[5px] px-[12px]" :disabled="$selectedPackageDefinitionId === null">
                                {{ __('livewire_itc_promo_code_label') }}
                            </x-ui.input>

                            <x-ui.button wire:click="applyPromoCode" class="w-full mt-2" type="button" :disabled="$selectedPackageDefinitionId === null">
                                {{ __('livewire_itc_promo_code_apply') }}
                            </x-ui.button>

                            @if ($appliedPromoCodeId)
                                <div class="mt-3 p-3 bg-green-900/30 border border-green-500/30 rounded-lg">
                                    <p class="text-green-400 text-sm">
                                        {{ __('livewire_itc_promo_code_applied_success', ['amount' => $appliedPromoCodeDiscount]) }}
                                    </p>
                                </div>
                            @endif

                            <x-ui.submit-button action="buyPackage" class="w-full mt-4" :is-disable="$selectedPackageDefinitionId === null">
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
                <div class="flex flex-col gap-[44px] mt-[20px]">
                    @foreach ($packages as $package)
                        <x-account.itc.package :package="$package" :mainBalance="$mainBalance" />
                    @endforeach
                </div>
            </div>
        </x-slot>

        <x-slot name="log">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="font-dela text-[16px] leading-[40px]">
                            <th class="py-1.5 pr-4 font-thin">
                                {{ __('livewire_dashboard_index_old_transactions_date_header') }}</th>
                            <th class="py-1.5 font-thin">{{ __('event') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logRows as $row)
                            <tr>
                                <td class="py-1.5 pr-4 whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="py-1.5 whitespace-nowrap">{{ $row['event'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-0">
                                    <div class="min-h-[400px] flex items-center justify-center">
                                        <p class="text-white/60">{{ __('no_event') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>

    </x-ui.card-tabs>
</div>
