<div class="relative bg-[#1B1939]/10 md:bg-[#1B193975] px-6 pr-4 pt-6 pb-4 space-y-6 top-0 z-50">
    {{-- Баланс + кнопка кошелька --}}
    <div class="flex items-center gap-10">
        <x-ui.logo class="mr-auto"/>
        <x-account.dashboard.widget.balance-pill class="hidden md:flex"/>
        <button
            type="button"
            @click="$store.menu.open = true"
            class="flex md:hidden items-center justify-center ml-2"
            aria-label="{{ __('open_menu') }}">
            <img src="{{ vite()->icon('/main/burger-white.svg') }}" alt=""/>
        </button>
        <x-ui.user-menu class="hidden md:flex" />
    </div>

    {{-- Котировки --}}

</div>
