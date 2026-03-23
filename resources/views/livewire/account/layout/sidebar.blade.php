<aside x-data="{ open: false }" :class="{ 'translate-x-0': open, '-translate-x-full': !open }"
    class="fixed inset-y-0 left-0 z-40 w-[340px] shrink-0 overflow-y-auto bg-[#1B1939]/90 text-white
           transition-transform duration-200 md:static md:-mt-[178px] md:z-20 md:h-auto md:min-h-[calc(100%+178px)] md:translate-x-0
           md:overflow-visible pb-8 md:pb-10 text-[16px]">

    <nav class="pt-[125px]">
        <ul class="space-y-[21px] px-6">
            <x-account.nav-button routeName="dashboard">{{ __('home') }}</x-account.nav-button>
            <x-account.nav-button routeName="itc-packages">{{ __('packages') }}</x-account.nav-button>
            <x-account.nav-button routeName="finance">{{ __('finance') }}</x-account.nav-button>
            <x-account.nav-button :blur="!hasPackage(auth()->user()->id)" :close="!hasPackage(auth()->user()->id)"
                routeName="partners">{{ __('affiliate_program') }}</x-account.nav-button>
            <x-account.nav-button routeName="itc-staking">{{ __('itc_staking') }}</x-account.nav-button>
            <x-account.nav-button routeName="academy.landing" target="_blank">{{ __('academy') }}</x-account.nav-button>
            <x-account.nav-button routeName="news.index">
                {{ __('liveware_news') }}
            </x-account.nav-button>
        </ul>
    </nav>

    <livewire:news.index position="sidebar" limit="3" />

</aside>
