@props([
    'value' => 0,
    'max'   => 100,
    'class' => '',
])

@php
    $max  = max(1, (float) $max);
    $perc = min(100, round(($value / $max) * 100));
@endphp

@if(trim($slot))
    <div class="mb-[4px] text-[12px] font-semibold text-white/40">{{ $slot }}</div>
@endif

<div {{ $attributes->merge(['class' => "w-full h-[6px] bg-white/10 rounded $class"]) }}>
    <div class="h-full bg-[#B4FF59] rounded" style="width: {{ $perc }}%;"></div>
</div>
