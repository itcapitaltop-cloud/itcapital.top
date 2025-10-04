<div class="flex rounded-[12px] bg-[#211F41] px-6 py-4 gap-4 {{ $class }}">
    {{-- item --}}
    <div class="pr-4 flex flex-col">
        <p class="flex items-center gap-1">
            <img src="{{ vite()->icon('currency/itc.svg') }}" class="w-[12px]" alt="">
            <span class="text-white text-[14px] font-black">{{ scale($mainBalanceAmount) }}</span>
        </p>
        <p class="text-[10px] text-white/40 leading-[0.5]">
            {{ __('components_account_dashboard_widget_balance_pill_main') }}</p>
    </div>

    <div class="px-4 flex flex-col">
        <p class="flex items-center gap-1">
            <img src="{{ vite()->icon('currency/itc-partners.svg') }}" class="w-[12px]" alt="">
            <span class="text-white text-[14px] font-black">{{ scale($partnerBalanceAmount) }}</span>
        </p>
        <p class="text-[10px] text-white/40 leading-[0.5]">
            {{ __('components_account_dashboard_widget_balance__pill_partners') }}</p>
    </div>

    {{--    <div class="pl-4 flex flex-col"> --}}

    {{--        <p class="flex items-center gap-1"> --}}
    {{--            <img src="{{ vite()->icon('currency/itc-token.svg') }}" class="w-[12px]" alt=""> --}}
    {{--            <span class="text-white text-[14px] font-black"> --}}{{-- {{ $token }} --}}{{-- 0</span> --}}
    {{--        </p> --}}
    {{--        <p class="text-[10px] text-white/40 leading-[0.5]">токены</p> --}}
    {{--    </div> --}}

    <div class="pl-4 flex flex-col ml-auto">
        <a href="{{ route('finance') }}" class="flex items-center justify-center">
            <img src="{{ vite()->icon('actions/wallet.svg') }}" alt="">
        </a>
    </div>

</div>
