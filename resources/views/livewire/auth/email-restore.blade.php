<div class="relative w-screen h-dvh bg-[#17162d] flex items-center justify-center">
    <x-index.header />

    <img src="{{ vite()->icon('/backgrounds/lock.svg') }}"
         class="pointer-events-none select-none absolute w-[330px] left-1/2 top-1/2
                -translate-x-1/2 -translate-y-[53%] opacity-60" alt="">

    <div class="relative w-[370px] sm:w-[447px]" x-data="restore" x-on:cooldown-start.window="restart($event.detail.seconds)" wire:key="restore-box">
        <form wire:submit.prevent="submit" method="post" action="#"
              class="rounded-[26px] [background:radial-gradient(circle,#2D286480,#211F4180)]
                     border border-white/5 backdrop-blur-[5px] shadow-lg ring-1 ring-white/10
                     px-[24px] py-[18px] flex flex-col gap-8">
            @csrf
            <h2 class="text-[20px] font-extrabold leading-[40px] text-white">
                {{ __('livewire_auth_email_restore_page_title') }}
            </h2>

            {{-- flash‑сообщение об успешной отправке --}}
            @if (session('status') === 'reset-link-sent')
                <x-ui.alert type="success">
                    {{ __('livewire_auth_email_restore_success_message') }} {{ old('login', $login ?? '') }}.
                </x-ui.alert>
            @else
                <x-ui.alert type="info">
                    {{ __('livewire_auth_email_restore_info_message') }}
                </x-ui.alert>
            @endif

            <x-ui.input name="login" placeholder="{{ __('livewire_auth_email_restore_email_placeholder') }}" validate="email" required input-class="py-[5px] px-[12px]" autocomplete="username">
                {{ __('livewire_auth_email_restore_email_label') }}
            </x-ui.input>

            <x-ui.submit-button class="w-full" x-bind:disabled="wait > 0">
                {{ __('livewire_auth_email_restore_submit_button') }}
            </x-ui.submit-button>

            <p x-show="wait > 0"
               class="absolute left-0 right-0 top-full mt-[-70px] text-center text-[12px] text-white/30">
                {{ __('livewire_auth_email_restore_cooldown_message') }} <span x-text="wait"></span> {{ __('livewire_auth_email_restore_cooldown_seconds') }}
            </p>

            <div class="mt-1 text-center">
                <a href="{{ route('login') }}"
                   class="text-lime underline underline-offset-[4px]">
                    {{ __('livewire_auth_email_restore_back_to_login') }}
                </a>
            </div>
        </form>
    </div>

    <p class="absolute bottom-6 left-1/2 -translate-x-1/2 text-[#8b8a96] text-[16px] flex items-center gap-2">
        <img class="w-3 shrink-0" src="{{ vite()->icon('/advantages/lock.svg') }}" alt="">
        <span class="whitespace-nowrap shrink-0">
            {{ __('livewire_auth_email_restore_connection_secure') }}
        </span>
    </p>
</div>

@script
    <script>
        Alpine.data('restore', () => ({
            wait: 0, timerId: null,

            restart(sec) {
                this.wait = sec;
                this.tick();
            },

            tick() {
                this.stop();
                if (this.wait > 0) {
                    this.timerId = setInterval(() => {
                        if (this.wait > 0) this.wait--;
                        else this.stop();
                    }, 1000);
                }
            },

            stop() { if (this.timerId) clearInterval(this.timerId); }
        }));

    </script>
@endscript
