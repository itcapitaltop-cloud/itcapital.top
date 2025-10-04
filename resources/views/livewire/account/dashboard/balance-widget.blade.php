<x-bg.main x-data="{ isDepositModalActive: false, isWithdrawModalActive: false }">
    <x-account.dashboard.widget.deposit-modal />
    <x-account.dashboard.widget.withdraw-modal />
    <x-bg.section-slim class="h-full">
        <div class="flex h-full gap-4 flex-col">
            <div class="flex gap-2">
                <figure class="cursor-pointer" x-on:click="isDepositModalActive = true">
                    <img class="icon-green w-4" src="{{ vite()->icon('/actions/plus.svg') }}" alt="">
                </figure>
                <figure class="cursor-pointer" x-on:click="isWithdrawModalActive = true">
                    <img class="icon-red w-4" src="{{ vite()->icon('/actions/minus.svg') }}" alt="">
                </figure>
                <button disabled class="opacity-70">
                    <img class="icon-yellow w-4" src="{{ vite()->icon('/actions/right-left-arrows.svg') }}"
                        alt="">
                </button>
            </div>
            <div class="grid gap-4 mt-3">
                <div>
                    <h3 class="text-sm text-gray-300">{{ __('components_account_dashboard_widget_balance_main_balance') }}:</h3>
                    <h1 class="text-2xl text-white">{{ scale($mainBalanceAmount) }} ITC</h1>
                </div>
                <div>
                    <h3 class="text-sm text-gray-300">{{ __('components_account_dashboard_widget_balance_partners_balance') }}:</h3>
                    <h1 class="text-2xl text-white">{{ scale($partnerBalanceAmount) }} ITC</h1>
                </div>
            </div>
        </div>
    </x-bg.section-slim>
</x-bg.main>


