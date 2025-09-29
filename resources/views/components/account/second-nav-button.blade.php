<li>
    <a href="{{ route($routeName) }}"
        class="py-0.5 px-2 inline-block border @if (Illuminate\Support\Facades\Route::currentRouteName() === $routeName) text-white border-white @else text-gray-300 border-gray-300 @endif rounded-md font-normal">
        {{ $slot }}
    </a>
</li>
