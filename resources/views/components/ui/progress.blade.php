@props([
    'value' => 0,
    'max'   => 100,
    'class' => '',
    'labelClass' => 'text-white/40',
    'trackClass' => 'bg-white/10',
    'barClass' => 'bg-[#B4FF59]',
])

@php
    $max  = max(1, (float) $max);
    $perc = min(100, floor(($value / $max) * 100));
@endphp

@if(trim($slot))
    <div class="mb-[4px] text-[12px] font-semibold {{ $labelClass }}">{{ $slot }}</div>
@endif

<div {{ $attributes->merge(['class' => "w-full h-[6px] rounded $trackClass $class"]) }}>
    <div class="h-full rounded {{ $barClass }}" style="width: {{ $perc }}%;"></div>
</div>
