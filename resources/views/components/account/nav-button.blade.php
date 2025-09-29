@props([
    'routeName' => null,
    'badge' => null,
])

<li>
    <a href="{{ $routeName ? route($routeName) : '' }}"
        @class([
             'block font-medium transition-colors',
             'text-lime'          => request()->routeIs($routeName),
             'text-white hover:text-lime' => !request()->routeIs($routeName),
        ])>
        {{ $slot }}

        @isset($badge)
            <span
                class=" ml-[10px] px-[8px] py-[4px] rounded-md text-[12px]
                       bg-[#433F8E] text-white select-none">
                {{ $badge }}
            </span>
        @endisset
    </a>
</li>
