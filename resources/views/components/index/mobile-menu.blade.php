
<div
    x-data
    x-show="$store.menu.open"
    x-transition.opacity
    class="fixed left-0 top-0 w-screen h-dvh
           z-[60] bg-[#17162dee] backdrop-blur-xl
           flex flex-col md:hidden justify-between"
    style="display: none;"
>
    <div class="absolute right-[16px] top-[39px] justify-between">
        <button @click="$store.menu.open = false">
            <img src="{{ vite()->icon('/actions/cross.svg') }}" alt="{{ __('close') }}" />
        </button>
    </div>

    @if ($isAuthPage)
        <a href="{{ route('index') }}"
           class="flex justify-end gap-2 mt-[120px] text-[16px] px-4 text-white font-medium"
           @click="$store.menu.open=false">
            <img class="w-[16px]" src="{{ vite()->icon('/actions/arrow-to-main-left-white.svg') }}" alt="">
            {{ __('home_page') }}
        </a>
    @elseif ($isAccountPage)
        <ul class="flex flex-col gap-[24px] text-lg items-end mt-[120px] px-4">
            <x-account.nav-button routeName="dashboard">{{ __('home') }}</x-account.nav-button>
            <x-account.nav-button routeName="itc-packages">{{ __('packages') }}</x-account.nav-button>
            <x-account.nav-button routeName="finance">{{ __('finance') }}</x-account.nav-button>
            <x-account.nav-button routeName="partners">{{ __('affiliate_program') }}</x-account.nav-button>
            <x-account.nav-button routeName="academy.landing">
                {{ __('academy') }}
            </x-account.nav-button>
        </ul>
        <div class="w-full flex flex-col">
            <div class="items-end justify-center mb-[15px] px-4 py-8 gap-4 pb-[env(safe-area-inset-bottom)]">
                @auth
                    <x-account.dashboard.widget.balance-pill />
                @endauth
                <x-ui.user-menu class="mt-[25px] mb-[15px] mr-[10px]"/>
            </div>
        </div>
    @else
        <ul class="flex flex-col gap-[24px] text-lg items-end mt-[120px] px-4">
            <li>
                <a href="#club" class="text-white font-medium" @click="$store.menu.open=false">
                    {{ __('about_club') }}
                </a>
            </li>
            <li>
                <a href="#profit" class="text-white font-medium" @click="$store.menu.open=false">
                    {{ __('passive_income') }}
                </a>
            </li>
            <li>
                <a href="#academy" class="text-white font-medium" @click="$store.menu.open=false">
                    {{ __('academy') }}
                </a>
            </li>
        </ul>
        <div class="w-full flex flex-col">
            <div class="items-end justify-center mb-[15px] px-4 py-8 gap-4 pb-[env(safe-area-inset-bottom)]">
                <x-ui.user-menu class="mt-[25px] mb-[15px] mr-[10px]"/>
            </div>
        </div>
    @endif
</div>
