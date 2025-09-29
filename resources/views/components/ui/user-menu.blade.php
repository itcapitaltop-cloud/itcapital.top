
<div {{ $attributes->merge(['class' => "flex items-end justify-end gap-[21px] z-[60]"]) }}>
    {{-- Язык --}}
{{--    <button type="button" class="flex items-center gap-2 px-1 py-1.5 rounded-md text-white select-none cursor-pointer border border-transparent transition">--}}
{{--        <img src="{{ vite()->icon('/flags/ru.svg') }}" alt="Русский" class="w-[28px] h-[20px]" />--}}
{{--        <img src="{{ vite()->icon('/actions/arrow-down-mini.svg') }}" alt="arrow" />--}}
{{--    </button>--}}
    @auth
        {{-- Кнопка колокольчика --}}
        <button type="button"
                @click.stop="window.dispatchEvent(new CustomEvent('notifications:toggle')); $store.menu.open = false;"
                class="relative flex items-center justify-center rounded-[6px] bg-transparent hover:bg-[#232347] transition-colors">
            <img src="{{ vite()->icon('/actions/bell.svg') }}" alt="{{ __('notify') }}" class="w-[24px] h-[24px]" />
            <span
                x-cloak
                x-show="$store.notifications.unread > 0"
                x-text="$store.notifications.unread"
                class="absolute -top-[8px] -right-[8px] min-w-[18px] h-[18px] px-[4px]
                     rounded-[5px] bg-[#FF3B30] text-white text-[12px] leading-[18px] text-center">
            </span>
        </button>
        <button type="button"
                x-on:click="isSettingsModal=true; $store.menu.open = false; console.log(isSettingsModal)"
                class="flex items-center justify-center rounded-[6px]
                       bg-transparent hover:bg-[#232347] transition-colors">
            <img src="{{ vite()->icon('/actions/settings.svg') }}"
                 alt="{{ __('settings') }}"
                 class="w-[24px] h-[24px]" />
        </button>
        <div class="flex items-center gap-[8px]">
            <img src="{{ vite()->icon('/actions/user.svg') }}" alt="User" class="w-[24px] h-[24px] text-[#B4FF59]" />
            <div class="flex items-center gap-[21px]">
                <a href="{{ route('dashboard') }}" class="text-white font-medium text-base hover:underline">
                    {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                </a>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="flex items-center justify-center rounded-[6px] bg-transparent hover:bg-[#232347] transition-colors">
                        <img src="{{ vite()->icon('/actions/logout.svg') }}" alt="Выход" class="w-[24px] h-[24px] text-[#B4FF59]" />
                    </button>
                </form>
            </div>
        </div>

    @else
        {{-- если не авторизован --}}
        <a href="{{ route('login') }}"
           class="px-[16px] pt-[13px] pb-[13px] rounded-lg bg-[#282858]
                  text-white font-medium hover:bg-[#372963] border border-[#341D78]">
            {{ __('sign-in') }}
        </a>
        <a href="{{ route('login') }}"
           class="px-[16px] pt-[14px] pb-[14px] rounded-lg font-medium
                  bg-[#B4FF59] text-black hover:bg-[#C5FF80] focus:ring-2 focus:ring-[#B4FF59]">
            {{ __('sign-up') }}
        </a>
    @endauth
</div>
