<div x-cloak x-show="{{ $conditionName ?? 'isModalActive' }}" class="fixed w-full h-screen z-50 top-0 left-0 backdrop-blur">
    <div class="absolute w-full h-full bg-black opacity-75"></div>
    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full px-3 sm:w-auto">
        <x-bg.main>
            {{ $slot }}
        </x-bg.main>
    </div>
</div>
