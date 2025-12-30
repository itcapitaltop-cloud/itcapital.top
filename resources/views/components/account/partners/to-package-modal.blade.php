@props(['partnerBalance'])
<x-widget.modal condition-name="$wire.isModalToPackage" maxWidth="md" class="p-6 w-auto md:w-[440px]">
    <div class="flex flex-col max-h-[80vh]">
        <div class="flex justify-between items-center mb-8 flex-shrink-0">
            <h2 class="font-dela text-[20px] text-white">
                {{ __('components_account_itc_partners_package_deposit_to_package') }}</h2>
            <button type="button" x-on:click="$wire.isModalToPackage = false">
                <img src="{{ vite()->icon('/actions/cancel-large.svg') }}" alt="">
            </button>
        </div>

        <div class="flex flex-col mb-8 flex-shrink-0">
            <div class="flex items-center gap-2 text-[16px]">
                <img src="{{ vite()->icon('/currency/itc-partners.svg') }}" alt="">
                <span class="text-white font-extrabold">
                    {{ $partnerBalance }}
                </span>
            </div>
            <span class="text-white/40 text-[12px] font-semibold">
                {{ __('components_account_itc_partners_package_available_partner_balance') }}
            </span>
        </div>

        <div class="mb-8 flex-shrink-0">
            <x-ui.input name="toPackageAmount" wire:model="toPackageAmount"
                placeholder="{{ __('components_account_itc_partners_package_amount_placeholder') }}" validate="number"
                icon="{{ vite()->icon('/currency/itc-partners.svg') }}" input-class="py-[5px] px-[12px]">
                {{ __('components_account_itc_partners_package_amount_to_deposit') }}
            </x-ui.input>
        </div>

        <div class="flex-shrink-0 mb-4">
            <p class="text-white">{{ __('components_account_itc_partners_package_select_package') }}</p>
        </div>

        <div class="overflow-y-auto flex-shrink min-h-0 mb-8 px-6 w-full">
            <div class="flex items-center flex-col gap-3" x-data="{ sel: @entangle('selectedPackageUuid') }">
                @foreach ($this->packagesForTopup as $pkg)
                    <label class="relative block cursor-pointer mb-[20px] w-full"
                        :class="sel === '{{ $pkg->uuid }}'
                            ?
                            'outline outline-[4px] outline-[#665FF2]/60 rounded-[30px] outline-offset-0 bg-[#2E1D78]/50' :
                            'hover:outline hover:outline-[4px] hover:outline-white/20 hover:outline-offset-0 hover:rounded-[30px]'">
                        <input type="radio" class="absolute inset-0 opacity-0" value="{{ $pkg->uuid }}"
                            wire:model="selectedPackageUuid" />
                        <x-account.itc.package :package="$pkg" :withButtons="false" class="pointer-events-none" />
                        <span x-show="sel === '{{ $pkg->uuid }}'"
                            class="absolute inset-0 bg-[#2E1D78]/50 rounded-[30px]
                                flex items-center justify-center pointer-events-none z-[60]">
                            <img src="{{ vite()->icon('/actions/big-check.svg') }}" alt="✓">
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <form wire:submit.prevent="transferToPackage" class="flex justify-end gap-3 flex-shrink-0">
            <x-ui.button type="button" x-on:click="$wire.isModalToPackage = false">
                {{ __('components_account_itc_partners_package_cancel') }}
            </x-ui.button>
            <x-ui.submit-button action="transferToPackage"
                x-bind:disabled="!$wire.selectedPackageUuid || !$wire.toPackageAmount">
                {{ __('components_account_itc_partners_package_transfer') }}
            </x-ui.submit-button>
        </form>
    </div>
</x-widget.modal>
