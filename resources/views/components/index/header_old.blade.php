<header x-data="header" x-init="init" x-cloak
    x-bind:class="[dY < 0 ? '-top-full' : 'top-0', startScrollY === 0 ? 'backdrop-blur-none' : 'backdrop-blur-md']"
    class="header fixed w-full py-6 transition-all duration-700 z-20">
    <div class="container">
        <div class="flex justify-between items-center">
            <a href="/" class="text-white block text-base">
                IT-Capital
            </a>
            <ul class="gap-5 hidden xl:flex">
                <li>
                    <a href="" class="text-white font-medium">{{ __('main_activity') }}</a>
                </li>
                <li>
                    <a href="" class="text-white font-medium">{{ __('investment_package') }}</a>
                </li>
                <li>
                    <a href="" class="text-white font-medium">{{ __('partnership') }}</a>
                </li>
            </ul>
            <div class="gap-3 flex">
                <a href="{{ route('login') }}" class="py-1.5 px-6 bg-gray border border-gray-400 rounded-xl">
                    <p class="text-white font-medium">{{ __('sign-in') }}</p>
                </a>
            </div>

        </div>
    </div>
    {{-- <div>

    </div>
    <div class="flex gap-6">
        <a href="{{ route('sign-up') }}">
            <span class="flex items-center gap-1.5 group">
                <figure class="icon-gray-400 group-hover:icon-white">
                    <img class="w-6 icon-black" src="{{ vite()->icon('/main/logo-line.svg') }}" alt="">
                </figure>
                <p class="text-gray-400 font-medium text-base group-hover:text-white">Присоединиться</p>
            </span>
        </a>
        <a href="{{ route('login') }}">
            <span class="flex items-center gap-1.5 group">
                <figure class="icon-gray-400 group-hover:icon-white">
                    <img class="w-6 icon-black" src="{{ vite()->icon('/actions/login.svg') }}" alt="">
                </figure>
                <p class="text-gray-400 font-medium text-base group-hover:text-white">Войти</p>
            </span>
        </a>
    </div> --}}
</header>

@script
    <script>
        Alpine.data('header', () => ({
            dY: 0,
            startScrollY: 0,
            init() {
                this.startScrollY = window.scrollY

                window.addEventListener('scroll', (e) => {
                    this.dY = Math.sign(this.startScrollY + 20 - window.scrollY)
                })

                window.addEventListener('scrollend', (e) => {
                    this.startScrollY = window.scrollY
                })
            },
        }))
    </script>
@endscript
