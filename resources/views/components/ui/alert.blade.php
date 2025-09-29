@props(['type' => 'info'])

@php
    $palette = [
        'success' => ['icon' => '/status/check.svg',   'text' => 'text-lime'],
        'error'   => ['icon' => '/status/error.svg',   'text' => 'text-red-400'],
        'warning' => ['icon' => '/status/warning.svg', 'text' => 'text-amber-300'],
        'info'    => ['icon' => '/status/info.svg',    'text' => 'text-blue-300'],
    ];
    $c = $palette[$type] ?? $palette['info'];
@endphp

<div {{ $attributes->merge([
        'class' => "relative flex gap-2 items-start rounded-[12px] px-3 py-2
                    backdrop-blur-[6px] bg-white/5 border border-white/5 ring-1 ring-white/10 {$c['text']}"
    ]) }}>
    <img src="{{ vite()->icon($c['icon']) }}" class="w-[24px] select-none" alt="">
    <span class="leading-snug">{{ $slot }}</span>
</div>
