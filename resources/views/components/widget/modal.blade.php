@props([
    'conditionName' => 'isModalActive',
    'maxWidth'      => 'md',
])

@php
    $sizes = [
        'sm'   => 'sm:max-w-sm',
        'md'   => 'sm:w-auto',
        'lg'   => 'sm:max-w-lg',
        'xl'   => 'sm:max-w-xl',
        'full' => 'w-full',
    ];
    $max = $sizes[$maxWidth] ?? $sizes['md'];
@endphp

<div x-cloak x-show="{{ $conditionName ?? 'isModalActive' }}"
     class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur">

    <!-- полупрозрачный тёмный фон -->
    <div class="absolute inset-0 bg-black/75"></div>

    <!-- само окно -->
    <div class="relative w-full px-3 {{ $max }}">
        <x-bg.main overflow="visible" {{ $attributes->merge(['class' => 'rounded-[12px]']) }}>
            {{ $slot }}
        </x-bg.main>
    </div>
</div>
