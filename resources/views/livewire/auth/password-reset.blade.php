<div class="relative w-screen h-dvh bg-[#17162d] flex items-center justify-center">
    <x-index.header />

    <img src="{{ vite()->icon('/backgrounds/lock.svg') }}"
         class="pointer-events-none select-none absolute w-[330px] left-1/2 top-1/2
                -translate-x-1/2 -translate-y-[53%] opacity-60" alt="">

    <div class="relative w-[370px] sm:w-[447px]">
        <form wire:submit.prevent="submit" method="post" action="#"
              class="rounded-[26px] [background:radial-gradient(circle,#2D286480,#211F4180)]
                     border border-white/5 backdrop-blur-[5px] shadow-lg ring-1 ring-white/10
                     px-[24px] py-[18px] space-y-[34px]">
            <h2 class="text-[20px] font-extrabold leading-[40px] text-white">
                {{ __('email_password_reset_title') }}
            </h2>
            @csrf
            <x-ui.input name="email" validate="email" placeholder="Ваш e‑mail" required input-class="py-[5px] px-[12px]" autocomplete="username">
                {{ __('email_password_reset_email') }}
            </x-ui.input>

            <x-ui.input type="password"
                name="password"
                validate="password"
                placeholder="{{ __('livewire_password_new_password_placeholder') }}"
                confirmWith="password_confirmation"
                required
                input-class="py-[5px] px-[12px]"
                notice="{{ __('livewire_password_new_password_notice') }}"
                autocomplete="new-password"
            >
                {{ __('livewire_password_new_password_label') }}
            </x-ui.input>

            <x-ui.input type="password"
                name="password_confirmation"
                validate="password"
                confirmWith="password"
                placeholder="{{ __('livewire_password_confirm_password_placeholder') }}"
                required
                input-class="py-[5px] px-[12px]"
                notice="{{ __('livewire_password_confirm_password_notice') }}"
                autocomplete="new-password">
                 {{ __('livewire_password_confirm_password_label') }}
            </x-ui.input>

            <x-ui.submit-button class="w-full">
                {{ __('livewire_password_submit_button') }}
            </x-ui.submit-button>

        </form>
    </div>

    <p class="absolute bottom-6 left-1/2 -translate-x-1/2 text-[#8b8a96] text-[16px] flex items-center gap-2">
        <img class="w-3 shrink-0" src="{{ vite()->icon('/advantages/lock.svg') }}" alt="">
        <span class="whitespace-nowrap shrink-0">
            {{ __('livewire_password_connection_secure') }}
        </span>
    </p>
</div>
