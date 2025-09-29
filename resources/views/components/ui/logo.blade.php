<a href="/"
    {{ $attributes->merge(['class' => 'flex items-center gap-3 group overflow-visible']) }}>
    <span class="relative block overflow-visible">
        {{-- glow‑фон по центру --}}
        <img src="{{ vite()->icon('/main/glow-full-logo.svg') }}"
             class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2
                    pointer-events-none max-w-none" alt=""/>

        {{-- подсвеченный badge сбоку --}}
        <img src="{{ vite()->icon('/main/glow-logo-badge.svg') }}"
             class="absolute -left-[60px] top-1/2 -translate-y-1/2
                    pointer-events-none" alt=""/>

        {{-- сам логотип --}}
        <img src="{{ vite()->icon('/main/logotype.svg') }}"
             class="relative z-10" alt="IT Capital"/>
    </span>
</a>
