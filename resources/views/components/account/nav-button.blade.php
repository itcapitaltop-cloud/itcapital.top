@props([
    'routeName' => null,
    'badge' => null,
    'blur' => false,
    'close' => false,
])

<li>
    <a href="{{ $routeName ? route($routeName) : '' }}" @class([
        'flex  gap-4 items-center font-medium transition-colors',
        'text-lime' => request()->routeIs($routeName),
        'text-white hover:text-lime' => !request()->routeIs($routeName),
    ])>
        <span @class([
            'inline-block',
            'blur-sm' => $blur,
        ])>
            {{ $slot }}
        </span>

        @isset($badge)
            <span
                class="ml-[10px] px-[8px] py-[4px] rounded-md text-[12px]
                       bg-[#433F8E] text-white select-none">
                {{ $badge }}
            </span>
        @endisset

        @if($close)
        <span class="flex-shrink-0">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M14.717 13.6364C14.1885 13.6364 13.76 13.208 13.76 12.6794V7.10656C13.76 5.22832 12.2319 3.7002 10.3536 3.7002C8.47539 3.7002 6.94727 5.22828 6.94727 7.10656V10.3479C6.94727 10.8764 6.51879 11.3049 5.99023 11.3049C5.46168 11.3049 5.0332 10.8764 5.0332 10.3479V7.10656C5.0332 4.17285 7.41992 1.78613 10.3536 1.78613C13.2873 1.78613 15.6741 4.17285 15.6741 7.10656V12.6794C15.6741 13.208 15.2456 13.6364 14.717 13.6364Z" fill="#B1B4B5"/>
                <path d="M16.1763 19.4319H4.53027C3.44297 19.4319 2.56152 18.5504 2.56152 17.4631V10.73C2.56152 9.64268 3.44297 8.76123 4.53027 8.76123H16.1763C17.2636 8.76123 18.145 9.64268 18.145 10.73V17.4631C18.145 18.5504 17.2636 19.4319 16.1763 19.4319Z" fill="#FFB636"/>
                <path d="M4.26953 17.8455H4.1709C3.86023 17.8455 3.6084 17.5937 3.6084 17.283V10.9106C3.6084 10.6 3.86023 10.3481 4.1709 10.3481H4.26953C4.5802 10.3481 4.83203 10.6 4.83203 10.9106V17.283C4.83203 17.5937 4.5802 17.8455 4.26953 17.8455Z" fill="#FFD469"/>
            </svg>
        </span>
        @endif
    </a>
</li>
