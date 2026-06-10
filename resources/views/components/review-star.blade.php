@props(['filled' => false])

@php
    $starPath = 'M11.2455 0.768037L13.5829 5.46498L18.8092 6.21833C19.9484 6.3826 20.4033 7.77102 19.5791 8.56799L15.7974 12.2243L16.6901 17.3869C16.8847 18.5121 15.694 19.3703 14.6749 18.839L10.0001 16.4017L5.32531 18.839C4.3062 19.3703 3.11551 18.5121 3.31006 17.3869L4.20278 12.2243L0.420708 8.56799C-0.403462 7.77102 0.0514164 6.3826 1.19059 6.21833L6.41694 5.46498L8.75434 0.768037C9.2639 -0.255687 10.7359 -0.255687 11.2455 0.768037Z';
@endphp

@if($filled)
    <svg {{ $attributes->merge(['class' => 'w-5 h-[19px] shrink-0']) }} viewBox="0 0 20 19" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="{{ $starPath }}" fill="#FFC533" />
    </svg>
@else
    <svg {{ $attributes->merge(['class' => 'w-5 h-[19px] shrink-0']) }} viewBox="-1 -1 22 21" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="{{ $starPath }}" stroke="#FFC533" stroke-width="1.5" stroke-linejoin="round" />
    </svg>
@endif
