<div>
    <x-bg.main>
        <x-bg.section-slim>
            <p class="text-white">{{ __('components_account_itc_wallet_header_your_balance') }}</p>
            <p class="text-white text-2xl font-medium">{{ $mainBalanceAmount }} PC</p>
        </x-bg.section-slim>
    </x-bg.main>
    <ul class="mt-4 flex gap-3">
        <x-account.second-nav-button route-name="wallet">
            {{ __('home') }}
        </x-account.second-nav-button>
        <x-account.second-nav-button route-name="deposit">
            {{ __('components_account_itc_wallet_header_deposit') }}
        </x-account.second-nav-button>
        <x-account.second-nav-button route-name="withdraw">
            {{ __('components_account_itc_wallet_header_withdraw') }}
        </x-account.second-nav-button>
    </ul>
</div>


