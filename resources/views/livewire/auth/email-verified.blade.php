<div class="relative w-screen h-dvh bg-[#17162d] flex items-center justify-center">
    <x-index.header />

    {{-- декоративный фон: большой чек‑икон --}}
    <img src="{{ vite()->icon('/backgrounds/envelope.png') }}"
         class="pointer-events-none select-none absolute left-1/2 top-1/2
                -translate-x-1/2 -translate-y-[53%] w-[420px] opacity-60" alt="background">

    {{-- стеклянная карточка --}}
    <div class="relative w-[370px] sm:w-[447px]">
        <div class="rounded-[26px] [background:radial-gradient(circle,#2D286480,#211F4180)]
                    border border-white/5 backdrop-blur-[5px] shadow-lg ring-1 ring-white/10
                    px-[24px] py-[24px] space-y-[34px]">

            <h2 class="text-[20px] font-extrabold leading-[40px] text-white">
                {{ __('livewire_auth_email_verified_title') }}
            </h2>

            <x-ui.alert class="mt-[20px]" type="success">
                {{ __('livewire_auth_email_verified_success_message') }}
            </x-ui.alert>

            <div class="flex flex-col sm:flex-row gap-4">
                <form method="GET" action="{{ route('index') }}" class="flex-1">
                    <x-ui.submit-button class="w-full">{{ __('livewire_auth_email_verified_go_to_home') }}</x-ui.submit-button>
                </form>

                <form method="GET" action="{{ route('dashboard') }}" class="flex-1">
                    <x-ui.submit-button class="w-full">
                        {{ __('livewire_auth_email_verified_go_to_dashboard') }}
                    </x-ui.submit-button>
                </form>
            </div>
        </div>

        {{-- ссылка влево, если нужна возвратная навигация --}}
        {{--  <a href="{{ route('index') }}" ...>На главную страницу</a> --}}
    </div>

    {{-- подпись о защите соединения --}}
    <p class="absolute bottom-6 left-1/2 -translate-x-1/2 text-[#8b8a96] text-[16px] flex items-center gap-2">
        <img class="w-3 shrink-0" src="{{ vite()->icon('/advantages/lock.svg') }}" alt="">
        <span class="whitespace-nowrap shrink-0">
            {{ __('livewire_auth_email_verified_connection_secure') }}
        </span>
    </p>
</div>
