<div>
    <div class="bg-gray border border-gray-450 rounded-lg px-3 py-2">
        <p class="text-white">{{ __('components_account_common_header_your_balance') }}:</p>
        <p class="text-white text-2xl font-bold">2342.84 U</p>
    </div>
    <ul class="mt-4 flex gap-3">
        <x-account.second-nav-button route-name="common-fund-buy">
            {{ __('components_account_common_header_buy_package') }}
        </x-account.second-nav-button>

        <x-account.second-nav-button route-name="commonFund">
            {{ __('components_account_common_header_your_packages') }}
        </x-account.second-nav-button>
    </ul>
</div>
