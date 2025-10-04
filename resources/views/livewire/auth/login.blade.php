<div class="relative w-screen h-dvh overflow-hidden bg-[#17162d] flex items-center justify-center">
    <x-index.header />

    <img src="{{ vite()->icon('/backgrounds/cards.png') }}" alt="cards background"
        class="pointer-events-none select-none absolute left-1/2 top-1/2 w-[700px] max-w-none -translate-x-1/2 -translate-y-[60%] opacity-60" />

    <div class="relative w-[370px] sm:w-[447px]">
        <form wire:submit.prevent="submit" method="post" action="#"
            class="rounded-[26px]
                     [background:radial-gradient(circle,#2D286480,#211F4180)] border border-white/5 backdrop-blur-[5px] shadow-lg ring-1 ring-white/10 px-[24px] py-[18px]">
            @csrf
            <h2 class="text-left leading-[40px] text-[20px] sm:text-[20px] font-extrabold text-white">
                {{ __('livewire_auth_sign_in_page_title') }}
            </h2>

            @if (session('status') === 'password-reset')
                <x-ui.alert type="success" class="mt-[20px]">
                    {{ __('livewire_auth_sign_in_password_reset_success') }}
                </x-ui.alert>
            @endif

            <div class="mt-[34px] grid gap-[34px]">
                <x-ui.input name="login" placeholder="{{ __('livewire_auth_sign_in_login_placeholder') }}"
                    input-class="py-[5px] px-[12px]" autocomplete="username">
                    {{ __('livewire_auth_sign_in_login_label') }}
                </x-ui.input>

                <x-ui.input type="password" name="password"
                    placeholder="{{ __('livewire_auth_sign_in_password_placeholder') }}"
                    input-class="py-[5px] px-[12px]" autocomplete="current-password">
                    {{ __('livewire_auth_sign_in_password_label') }}
                </x-ui.input>
            </div>

            <div class="mt-[40px]">
                <x-ui.submit-button class="w-full">
                    {{ __('livewire_auth_sign_in_submit_button') }}
                </x-ui.submit-button>
            </div>

            <div class="mt-5 flex justify-between text-center gap-2 text-[16px]">
                <a href="{{ route('email-restore') }}" class="text-lime underline underline-offset-[4px]">
                    {{ __('livewire_auth_sign_in_forgot_password') }}
                </a>
                <a href="{{ route('sign-up') }}" class="text-lime underline underline-offset-[4px]">
                    {{ __('livewire_auth_sign_in_create_account') }}
                </a>
            </div>
        </form>

    </div>

    <div>
        <p class="absolute bottom-6 pt-2 left-1/2 -translate-x-1/2 text-[#8b8a96] text-[16px] flex items-center gap-2">
            <img class="w-3 shrink-0" src="{{ vite()->icon('/advantages/lock.svg') }}" alt="">
            <span class="whitespace-nowrap shrink-0">
                {{ __('livewire_auth_sign_in_connection_secure') }}
            </span>
        </p>
    </div>
</div>
