<div {{ $attributes->merge(['class' => 'rounded-xl overflow-hidden relative']) }} x-data="gradientBlock" x-on:mousemove="onMove"
     x-on:mouseleave="onLeave"
     x-bind:style="{ transform: `perspective(700px) rotateY(${degY}deg) rotateX(${degX}deg)` }">
    <x-bg.perspective>
        <div class="relative flex flex-col flex-1">
            {{ $slot }}
        </div>
    </x-bg.perspective>
    <div x-bind:style="{ top: `${y}px`, left: `${x}px`, }"
         class="{{ $gradientClass }} absolute opacity-60 -translate-x-1/2 -translate-y-1/2">
    </div>
</div>
