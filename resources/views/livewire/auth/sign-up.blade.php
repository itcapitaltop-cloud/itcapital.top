<div class="relative min-h-screen overflow-hidden bg-[#17162d] flex flex-col items-center justify-center">
    <x-index.header />

    <div class=" absolute
                    left-1/2 top-1/2 w-[450px] md:w-[410px]max-w-none
                    -translate-x-[50%] md:-translate-x-[130%] -translate-y-[50%] md:-translate-y-[90%]">
        <img src="{{ vite()->icon('/backgrounds/cards-input.png') }}"
             alt="cards background"
             class="pointer-events-none select-none
                    opacity-80" />
        <span
            class="absolute left-[28px] top-[267px] font-ocr uppercase text-[#B26E00]
               inline-block rounded-[3px] lg:rounded-[3px] bg-[#FDE917]/30
               px-[3px] py-[1px] lg:px-[6px] lg:py-[2px]
               text-[11px] lg:text-[11px] tracking-wider"
            x-show="$store.user.first || $store.user.last"
            x-text="$store.helpers.translit($store.user.first + ' ' + $store.user.last)">
        </span>
    </div>
    <div class="relative mt-[115px] w-[370px] sm:w-[447px]">
        <form wire:submit.prevent="submit" method="post" action="#"

              class="rounded-[26px]
                     [background:radial-gradient(circle,#2D286480,#211F4180)]
                     border border-white/5 backdrop-blur-[10px] shadow-lg ring-1 ring-white/10
                     px-[24px] py-[18px]">
            @csrf
            <h2 class="text-left leading-[40px] text-[20px] sm:text-[20px] font-extrabold text-white">
                {{ __('livewire_sign_up_page_title') }}
            </h2>

            {{-- поля ввода --}}
            <div class="mt-[34px] grid gap-[38px]">
                <x-ui.input
                    name="username"
                    validate="username"

                    placeholder="{{ __('livewire_sign_up_nickname_placeholder') }}"
                    input-class="py-[5px] px-[12px]"
                    notice="{{ __('livewire_sign_up_nickname_notice') }}"
                    autocomplete="username"
                >
                    {{ __('livewire_sign_up_nickname_label') }}
                </x-ui.input>

                <x-ui.input name="firstName" placeholder="{{ __('livewire_sign_up_first_name_placeholder') }}"
                            x-on:input="$store.user.first = $event.target.value"
                            input-class="py-[5px] px-[12px]">
                    {{ __('livewire_sign_up_first_name_label') }}
                </x-ui.input>

                <x-ui.input name="lastName" placeholder="{{ __('livewire_sign_up_last_name_placeholder') }}"
                            x-on:input="$store.user.last = $event.target.value"
                            input-class="py-[5px] px-[12px]">
                    {{ __('livewire_sign_up_last_name_label') }}
                </x-ui.input>

                <x-ui.input
                    type="email"
                    name="email"
                    placeholder="{{ __('livewire_sign_up_email_placeholder') }}"
                    validate="email"
                    input-class="py-[5px] px-[12px]"
                    autocomplete="email">
                    {{ __('livewire_sign_up_email_label') }}
                </x-ui.input>

                {{-- пароль + глазик --}}
                <x-ui.input type="password"
                    name="password"
                    validate="password"
                    placeholder="{{ __('livewire_sign_up_password_placeholder') }}"
                    confirmWith="passwordConfirm"
                    required
                    input-class="py-[5px] px-[12px]"
                    notice="{{ __('livewire_sign_up_password_notice') }}"
                    autocomplete="new-password">
                    {{ __('livewire_sign_up_password_label') }}
                </x-ui.input>

                <x-ui.input type="password"
                    name="passwordConfirm"
                    validate="password"
                    confirmWith="password"
                    placeholder="{{ __('livewire_sign_up_password_confirm_placeholder') }}"
                    required
                    input-class="py-[5px] px-[12px]"
                    notice="{{ __('livewire_sign_up_password_confirm_notice') }}"
                    autocomplete="new-password">
                    {{ __('livewire_sign_up_password_confirm_label') }}
                </x-ui.input>
            </div>

            <div class="mt-[34px]">
                <x-ui.submit-button class="w-full">
                    {{ __('livewire_sign_up_submit_button') }}
                </x-ui.submit-button>
            </div>

            {{-- единственная ссылка под формой --}}
            <div class="mt-4 text-center text-[16px]">
                <a href="{{ route('login') }}" class="text-lime underline underline-offset-[4px]">
                    {{ __('livewire_sign_up_login_link') }}
                </a>
            </div>
        </form>
    </div>

    <p class="text-[#8b8a96] py-[15px] text-[16px] flex items-center gap-2">
        <img class="w-3 shrink-0" src="{{ vite()->icon('/advantages/lock.svg') }}" alt="">
        <span class="whitespace-nowrap shrink-0">
            {{ __('livewire_sign_up_connection_secure') }}
        </span>
    </p>
</div>

@script
    <script>
        Alpine.store('user', { first: '', last: '' })
    </script>
@endscript
