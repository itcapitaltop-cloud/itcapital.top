<header class="pt-6 px-6" x-data="{ isMenuActive: false }" >
    <div class="flex justify-between items-center">
        <button x-on:click="isMenuActive = !isMenuActive" class="sm:hidden">
            <div class="icon-white">
                <img class="icon w-4" src="{{ vite()->icon('main/burger.svg') }}" alt="">
            </div>
        </button>
        <nav x-cloak class="fixed top-0 left-0 sm:block w-full h-screen bg-black z-50 p-4 sm:static sm:w-auto sm:h-auto sm:p-0" x-bind:class="[ isMenuActive ? 'block' : 'hidden' ]">
            <ul class="flex flex-col gap-4 sm:flex-row">
                <button x-on:click="isMenuActive = !isMenuActive" class="sm:hidden">
                    <div class="icon-white">
                        <img class="icon w-4" src="{{ vite()->icon('main/burger.svg') }}" alt="">
                    </div>
                </button>
                <x-account.nav-button route-name="dashboard">{{ __('home') }}</x-account.nav-button>
                <x-account.nav-button routeName="itc-packages">{{ __('components_account_itc_package_modal_itc') }}</x-account.nav-button>
                <x-account.nav-button routeName="my-business">{{ __('livewire_dashboard_index_old_my_business_title') }}</x-account.nav-button>
            </ul>
        </nav>
        <div class="flex items-center gap-3">
            <p class="text-white">
                {{ auth()->user()->username }}
            </p>
            <form method="post" action="/auth/logout">
                @csrf
                <button class="text-red font-medium">{{ __('exit') }}</button>
            </form>

        </div>
    </div>
</header>
