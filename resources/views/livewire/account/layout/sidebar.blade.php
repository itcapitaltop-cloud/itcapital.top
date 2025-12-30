<aside
    x-data="{ open:false }"
    :class="{ 'translate-x-0': open, '-translate-x-full': !open }"
    class="fixed inset-y-0 left-0 w-[340px] shrink-0 bg-[#1B1939]/90 text-white
           transition-transform duration-200 md:translate-x-0 z-40 text[16px]">

    {{-- навигация --}}
    <nav class="mt-[125px]">
        <ul class="space-y-[21px] px-6">
            <x-account.nav-button routeName="dashboard">{{ __('home') }}</x-account.nav-button>
            <x-account.nav-button routeName="itc-packages">{{ __('packages') }}</x-account.nav-button>
            <x-account.nav-button routeName="finance">{{ __('finance') }}</x-account.nav-button>
            <x-account.nav-button routeName="partners">{{ __('affiliate_program') }}</x-account.nav-button>
            <x-account.nav-button routeName="academy.landing">{{ __('academy') }}</x-account.nav-button>
        </ul>
    </nav>
</aside>
