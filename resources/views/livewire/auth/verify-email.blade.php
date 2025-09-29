<div class="relative w-screen h-dvh bg-[#17162d] flex items-center justify-center">
    <x-index.header />

    {{-- декоративный конверт --}}
    <img src="{{ vite()->icon('/backgrounds/envelope.png') }}"
         class="pointer-events-none select-none absolute left-1/2 top-1/2
                -translate-x-1/2 -translate-y-[53%] w-[500px] opacity-60" alt="background">

    {{-- стеклянная карточка --}}
    <div class="relative w-[370px] sm:w-[447px]">
        <div x-data="verifyEmail(60)"
             class="rounded-[26px] [background:radial-gradient(circle,#2D286480,#211F4180)]
                    border border-white/5 backdrop-blur-[5px] shadow-lg ring-1 ring-white/10
                    px-[24px] py-[24px]">

            <h2 class="text-[20px] font-extrabold leading-[40px] text-white">
                {{ __('livewire_auth_verify_title') }}
            </h2>

            <div class="mt-[20px] flex flex-col gap-4 text-[16px] text-white">
                <span>
                    {{ __('livewire_auth_verify_sent_to') }}
                    <span class="font-bold">{{ Auth::user()->email }}</span>
                    {{ __('livewire_auth_verify_sent_to_1') }}
                </span>

                <div>{{ __('livewire_auth_verify_check_spam') }}</div>
            </div>

            {{-- Alert о повторной отправке --}}
            @if (session('status') === 'verification-link-sent')
                <x-ui.alert type="success" class="mt-6">
                    {{ __('livewire_auth_verify_resend_success') }}
                </x-ui.alert>
            @endif

            {{-- форма повторной отправки --}}
            <form method="POST" action="{{ route('verification.send') }}" class="relative mt-[34px]" @submit="restartTimer">
                @csrf
                <x-ui.submit-button class="w-full" x-bind:disabled="wait > 0">
                    {{ __('livewire_auth_verify_resend_button') }}
                </x-ui.submit-button>

                <p x-show="wait > 0"
                   class="absolute left-0 right-0 top-full mt-2
                   text-center text-[12px] text-white/30">
                    {{ __('livewire_auth_verify_resend_wait') }} <span x-text="wait"></span> {{ __('livewire_auth_verify_resend_wait_seconds') }}
                </p>
            </form>

            {{-- сменить адрес --}}
            <form method="POST" action="{{ route('logout') }}" class="mt-8 text-center">
                @csrf
                <button type="submit" class="text-lime underline underline-offset-[4px]">
                    {{ __('livewire_auth_verify_logout_and_change') }}
                </button>
            </form>
        </div>

        {{-- ссылка на главную --}}
{{--        <a href="{{ route('index') }}"--}}
{{--           class="group text-lime mt-[20px] flex items-center justify-center gap-2 text-[16px]--}}
{{--                  underline underline-offset-[4px]">--}}
{{--            <img src="{{ vite()->icon('/actions/arrow-to-main-left.svg') }}" alt="">--}}
{{--            На главную страницу--}}
{{--        </a>--}}
    </div>

    {{-- подпись о защите соединения --}}
    <p class="absolute bottom-6 left-1/2 -translate-x-1/2 text-[#8b8a96] text-[16px] flex items-center gap-2">
        <img class="w-3 shrink-0" src="{{ vite()->icon('/advantages/lock.svg') }}" alt="">
        <span class="whitespace-nowrap shrink-0">
            {{ __('livewire_auth_verify_connection_secure') }}
        </span>
    </p>
</div>

@script
    <script>
        Alpine.data('verifyEmail', start => ({
            wait: start,
            timerId: null,

            init() { this.start(); },

            start() {
                this.stop();
                if (this.wait > 0)
                    this.timerId = setInterval(() => {
                        if (this.wait > 0) { this.wait--; }
                        else { this.stop(); }
                    }, 1000);
            },

            stop() { if (this.timerId) clearInterval(this.timerId); },
            restartTimer() { this.wait = 60; this.start(); }
        }));
    </script>
@endscript
