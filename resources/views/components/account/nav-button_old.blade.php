<li>
    <a href="{{ route($routeName) }}"
        class="py-0.5 px-2 inline-block @if (Illuminate\Support\Facades\Route::currentRouteName() === $routeName) bg-white text-black @else text-white @endif rounded-md  font-medium">
        {{ $slot }}
    </a>
</li>
