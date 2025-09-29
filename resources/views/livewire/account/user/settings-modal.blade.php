<div x-data="{ isPwdModal:false, editEmail:false }">

    <x-widget.modal class="p-6" condition-name="isSettingsModal" maxWidth="md">
        <form wire:submit.prevent="save" method="post" action="#" class="space-y-8 w-auto md:w-[380px]">

            <div class="flex justify-between items-center">
                <h2 class="font-dela text-[20px] text-white">{{ __('livewire_user_settings_settings') }}</h2>
                <button type="button" x-on:click="isSettingsModal=false">
                    <img src="{{ vite()->icon('/actions/cancel-large.svg') }}" alt="">
                </button>
            </div>
            @csrf
            <x-ui.input name="first_name" input-class="py-[5px] px-[12px]"
                        placeholder="{{ __('livewire_user_settings_first_name_placeholder') }}" :value="$first_name"
                        required>
                {{ __('livewire_user_settings_first_name_label') }}
            </x-ui.input>

            <x-ui.input name="last_name" input-class="py-[5px] px-[12px]"
                        placeholder="{{ __('livewire_user_settings_last_name_placeholder') }}" :value="$last_name"
                        required>
                {{ __('livewire_sign_up_last_name_placeholder') }}
            </x-ui.input>

            <div class="flex items-center gap-2">
                <div class="relative flex-grow">

                    <x-ui.input
                        name="email"
                        x-bind:readonly="!editEmail"
                        :value="$email"
                        input-class="pr-[112px] py-[5px] px-[12px] read-only:text-white/60 read-only:pointer-events-none read-only:focus:border-[#2E1D78] read-only:selection:bg-transparent"
                        autocomplete="email">
                        {{ __('livewire_sign_up_email_label') }}
                    </x-ui.input>

                    @if ($pendingEmail)
                        <img src="{{ vite()->icon('/advantages/clock.svg') }}"
                             class="absolute right-[6px] top-1/2 -translate-y-1/2 mt-[11px]" alt="">
                        <button type="button"
                                class="absolute right-[30px] top-1/2 -translate-y-1/2
                                       flex items-center justify-center w-[20px] h-[20px]
                                       rounded-full hover:bg-[#4731AC] transition mt-[11px]"
                                wire:click="resendVerification">
                            <img src="{{ vite()->icon('/actions/redo.svg') }}" class="w-[20px]" alt="">
                        </button>
                        <div class="absolute text-[#F0B84B] mt-0 text-[12px]">
                            {{ __('livewire_user_settings_pending_email_notice', ['email' => $pendingEmail]) }}
                        </div>
                    @endif

                </div>

                <x-ui.button type="button" class="px-[6px] mt-[21px]"
                             x-on:click="
                        if (editEmail) { $wire.call('save'); }
                        editEmail = !editEmail;
                     ">
                    <img x-show="!editEmail" src="{{ vite()->icon('/actions/edit.svg') }}" class="w-[18px]" alt="">
                    <img x-show="editEmail" src="{{ vite()->icon('/actions/check.svg') }}" class="w-[18px]" alt="">
                </x-ui.button>
            </div>

            <x-ui.input name="telegram" input-class="py-[5px] px-[12px]" placeholder="{{ __('livewire_user_settings_telegram_placeholder') }}"
                        validate="telegram" :value="$telegram">
                {{ __('livewire_user_settings_telegram_label') }}
            </x-ui.input>

            <div class="flex items-center gap-2">
                <div class="relative flex-grow">
                    <x-ui.input name="fake_password" type="password" readonly value="********"
                                input-class="py-[5px] px-[12px]">
                        {{ __('livewire_user_settings_password_label') }}
                    </x-ui.input>
                </div>

                <x-ui.button type="button" class="px-[6px] mt-[22px]"
                             x-on:click="isSettingsModal=false; isPwdModal=true">
                    <img src="{{ vite()->icon('/actions/edit.svg') }}" class="w-[18px]" alt="">
                </x-ui.button>
            </div>

            <x-ui.select
                name="locale"
                label="{{ __('livewire_user_settings_interface_language_label') }}"
                :options="[
                            'ru' => 'Русский',
                            'en' => 'English',
                            'zh' => '中文',
                        ]"
                :icons="[
                    'ru' => vite()->icon('/flags/ru.svg'),
                    'en' => vite()->icon('/flags/en.svg'),
                    'zh' => vite()->icon('/flags/zh_CN.svg'),
                ]"
                :value="$locale"
                placeholder="Русский"
            />

            <div class="flex justify-end gap-3">
                <x-ui.button type="button" x-on:click="isSettingsModal=false">{{ __('livewire_user_settings_cancel') }}</x-ui.button>
                <x-ui.submit-button>{{ __('livewire_user_settings_save') }}</x-ui.submit-button>
            </div>
        </form>
    </x-widget.modal>

    <x-widget.modal class="p-6" condition-name="isPwdModal" maxWidth="md">
        <form wire:submit.prevent="save" class="space-y-10 w-[320px]" method="post" action="#">
            <div class="flex justify-between items-center">
                <h2 class="font-dela text-[20px] text-white">{{ __('password_changed') }}</h2>
                <button type="button" x-on:click="isPwdModal=false; isSettingsModal=true">
                    <img src="{{ vite()->icon('/actions/cancel-large.svg') }}" alt="">
                </button>
            </div>

            <x-ui.input
                name="newPassword"
                type="password"
                placeholder="{{ __('livewire_user_settings_new_password_placeholder') }}"
                validate="password"
                required
                notice="{{ __('livewire_user_settings_new_password_notice') }}"
                confirmWith="newPasswordConfirm"
                input-class="py-[5px] px-[12px]"
                autocomplete="new-password">
                {{ __('livewire_user_settings_new_password_label') }}
            </x-ui.input>

            <x-ui.input
                name="newPasswordConfirm"
                type="password"
                placeholder="{{ __('livewire_user_settings_confirm_password_placeholder') }}"
                validate="password"
                required
                notice="{{ __('livewire_user_settings_confirm_password_notice') }}"
                confirmWith="newPassword"
                input-class="py-[5px] px-[12px]"
                autocomplete="new-password">
                {{ __('livewire_user_settings_confirm_password_label') }}
            </x-ui.input>

            <div class="flex justify-end gap-3">
                <x-ui.button type="button" x-on:click="isPwdModal=false; isSettingsModal=true">
                    {{ __('livewire_user_settings_cancel') }}
                </x-ui.button>
                <x-ui.submit-button>
                    {{ __('livewire_user_settings_save') }}
                </x-ui.submit-button>
            </div>
        </form>
    </x-widget.modal>
</div>
