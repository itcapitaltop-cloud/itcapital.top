@props([
    'class'=>null,
    'overflow'   => 'hidden',])
    @php $classes = 'bg-[radial-gradient(50%_105%_at_90%_97%,#2D2864_0%,#211F41_100%)]
                border-[2px] border-[#4c40d0]/60 rounded-[24px] flex flex-col flex-1';
         $classes .= $overflow === 'visible'
                ? ' overflow-visible'
                : ' overflow-hidden';
    @endphp

<div {{ $attributes->merge(['class'=>"$classes $class"]) }}>
    {{ $slot }}
</div>
