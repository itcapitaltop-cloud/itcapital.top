<header x-data="header" x-init="init" x-cloak
        x-bind:class="[dY < 0 ? '-top-full' : 'top-0', startScrollY === 0 ? 'backdrop-blur-none' : 'backdrop-blur-md']"
        class="header fixed w-full py-6 transition-all duration-700 z-20 bg-[#1B193975]">
    <div class="container">
        <div class="flex justify-between items-center">
            {{-- Логотип с glow --}}
            <x-ui.logo />
            {{-- Меню --}}
            @unless($isAuthPage)
                <ul class="gap-[36px] hidden xl:flex">
                    <li>
                        <a href="#club" class="text-white font-medium transition-colors duration-200 hover:text-[#B4FF59]">
                            {{ __('about_club') }}
                        </a>
                    </li>
                    <li>
                        <a href="#profit" class="text-white font-medium transition-colors duration-200 hover:text-[#B4FF59]">
                            {{ __('passive_income') }}
                        </a>
                    </li>
                    <li>
                        <a href="#academy" class="text-white font-medium transition-colors duration-200 hover:text-[#B4FF59]">
                            {{ __('academy') }}
                        </a>
                    </li>
                </ul>
            @endunless

            <button
                type="button"
                @click="$store.menu.open = true"
                class="flex md:hidden items-center justify-center ml-2"
                aria-label="{{ __('open_menu') }}">
                <img src="{{ vite()->icon('/main/burger-white.svg') }}" alt=""/>
            </button>
            {{-- Блок: язык + кнопки --}}
            <x-ui.user-menu class="hidden md:flex" />
        </div>
    </div>
</header>

@script
<script>

    Alpine.store('helpers', {
        translit(str) {
            const ru = 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдеёжзийклмнопрстуфхцчшщъыьэюя';
            const en = ['A','B','V','G','D','E','E','ZH','Z','I','Y','K','L','M','N','O','P','R','S','T','U','F','KH','TS','CH','SH','SCH','','Y','','E','YU','YA',
                'a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','kh','ts','ch','sh','sch','','y','','e','yu','ya'];
            return str.split('').map(s => {
                const i = ru.indexOf(s);
                return i >= 0 ? en[i] : s;
            }).join('').toUpperCase();
        }
    })
    Alpine.data('header', () => ({
        dY: 0,
        startScrollY: 0,
        init() {
            this.startScrollY = window.scrollY

            window.addEventListener('scroll', () => {
                this.dY = Math.sign(this.startScrollY + 20 - window.scrollY)
            })

            window.addEventListener('scrollend', () => {
                this.startScrollY = window.scrollY
            })
        },
    }))
</script>
@endscript
