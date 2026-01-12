<div class="relative" x-data="{ open: @entangle('open') }">
    <button wire:click="$toggle('open')" type="button"
        class="flex items-center gap-2 px-1 py-1.5 rounded-md text-white border border-transparent transition hover:bg-white/10">
        <img src="{{ vite()->icon('/flags/' . $locale . '.svg') }}" class="w-[28px] h-[20px]" alt="{{ $locale }}" />
        <img src="{{ vite()->icon('/actions/arrow-down-mini.svg') }}" class="w-3 h-3 transition-transform"
            :class="open ? 'rotate-180' : ''" />
    </button>
    @if ($open)
        <div wire:click.outside="$set('open', false)"
            class="absolute right-auto bottom-[48px] md:bottom-auto md:top-[42px] w-36 bg-[#17162D]
               border border-[#2E1D78] rounded-lg
               divide-y divide-[#2E1D78] z-20">
            <button wire:click="switch('ru')"
                class="flex items-center gap-2 w-full px-3 py-1 hover:bg-[#232347'] rounded-t-md text-white">
                <img src="{{ vite()->icon('/flags/ru.svg') }}" class="w-3" />
                Русский
            </button>
            <button wire:click="switch('en')"
                class="flex items-center gap-2 w-full px-3 py-1 hover:bg-[#232347'] text-white">
                <img src="{{ vite()->icon('/flags/en.svg') }}" class="w-3" />
                English
            </button>
            <button wire:click="switch('zh')"
                class="flex items-center gap-2 w-full px-3 py-1 hover:bg-[#232347'] rounded-b-md text-white">
                <img src="{{ vite()->icon('/flags/zh.svg') }}" class="w-3" />
                中文
            </button>
        </div>
    @endif
</div>
