<div x-data="{ isPwdModal:false, isBeneficiaryModal:false, isDeleteBeneficiaryModal:false, editEmail:false, activeSettingsTab:'profile' }"
     x-on:beneficiary-saved.window="isBeneficiaryModal=false; isSettingsModal=true; activeSettingsTab='beneficiaries'"
     x-on:beneficiary-form-opened.window="isSettingsModal=false; isBeneficiaryModal=true"
     x-on:beneficiary-delete-confirmation-opened.window="isSettingsModal=false; isDeleteBeneficiaryModal=true"
     x-on:beneficiary-deleted.window="isDeleteBeneficiaryModal=false; isSettingsModal=true; activeSettingsTab='beneficiaries'">

    <x-widget.modal class="p-6" condition-name="isSettingsModal" maxWidth="md">
        <form wire:submit.prevent="save" method="post" action="#" class="space-y-8 w-auto md:w-[380px]">

            <div class="flex justify-between items-center">
                <h2 class="font-dela text-[20px] text-white">{{ __('livewire_user_settings_settings') }}</h2>
                <button type="button" x-on:click="isSettingsModal=false">
                    <img src="{{ vite()->icon('/actions/cancel-large.svg') }}" alt="">
                </button>
            </div>

            <div class="grid grid-cols-2 gap-1 rounded-[10px] bg-[#17162D] p-1" role="tablist">
                <button type="button" role="tab" x-on:click="activeSettingsTab='profile'"
                        x-bind:aria-selected="activeSettingsTab === 'profile'"
                        x-bind:class="activeSettingsTab === 'profile' ? 'bg-[#433F8E] text-white' : 'text-white/60 hover:text-white'"
                        class="rounded-[8px] px-3 py-2 text-[14px] transition">
                    {{ __('livewire_user_settings_profile_tab') }}
                </button>
                <button type="button" role="tab" x-on:click="activeSettingsTab='beneficiaries'"
                        x-bind:aria-selected="activeSettingsTab === 'beneficiaries'"
                        x-bind:class="activeSettingsTab === 'beneficiaries' ? 'bg-[#433F8E] text-white' : 'text-white/60 hover:text-white'"
                        class="rounded-[8px] px-3 py-2 text-[14px] transition">
                    {{ __('livewire_user_settings_beneficiaries') }}
                </button>
            </div>

            @csrf
            <div x-show="activeSettingsTab === 'profile'" class="flex flex-col gap-8" role="tabpanel">
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
                    'zh' => vite()->icon('/flags/zh.svg'),
                ]"
                :value="$locale"
                placeholder="Русский"
                />

                <div class="flex justify-end gap-3">
                    <x-ui.button type="button" x-on:click="isSettingsModal=false">{{ __('livewire_user_settings_cancel') }}</x-ui.button>
                    <x-ui.submit-button>{{ __('livewire_user_settings_save') }}</x-ui.submit-button>
                </div>
            </div>

            <div x-cloak x-show="activeSettingsTab === 'beneficiaries'" class="flex flex-col gap-4" role="tabpanel">
                @if ($beneficiaries->isNotEmpty())
                    <div class="flex max-h-[360px] flex-col gap-2 overflow-y-auto">
                        @foreach ($beneficiaries as $beneficiary)
                            <div wire:key="beneficiary-{{ $beneficiary->id }}"
                                 class="flex items-center justify-between gap-3 rounded-[8px] border border-[#2E1D78] bg-[#17162D] p-3">
                                <div class="min-w-0 text-white">
                                    <div class="truncate font-medium">{{ $beneficiary->full_name }}</div>
                                    <div class="truncate text-[13px] text-white/60">{{ $beneficiary->phone }}</div>
                                    <div class="truncate text-[13px] text-white/40">{{ $beneficiary->social_url }}</div>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <x-ui.button type="button" class="px-[6px]"
                                                 wire:click="startEditingBeneficiary({{ $beneficiary->id }})"
                                                 wire:loading.attr="disabled" wire:target="startEditingBeneficiary({{ $beneficiary->id }})"
                                                 aria-label="{{ __('livewire_user_settings_edit') }}"
                                                 title="{{ __('livewire_user_settings_edit') }}">
                                        <img src="{{ vite()->icon('/actions/edit.svg') }}" class="h-[18px] w-[18px]" alt="">
                                    </x-ui.button>
                                    <x-ui.button type="button" class="px-[6px]"
                                                 wire:click="startDeletingBeneficiary({{ $beneficiary->id }})"
                                                 wire:loading.attr="disabled" wire:target="startDeletingBeneficiary({{ $beneficiary->id }})"
                                                 aria-label="{{ __('livewire_user_settings_delete') }}"
                                                 title="{{ __('livewire_user_settings_delete') }}">
                                        <img src="{{ vite()->icon('/actions/cross.svg') }}" class="h-[18px] w-[18px]" alt="">
                                    </x-ui.button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-[8px] border border-dashed border-[#2E1D78] p-5 text-center text-[14px] text-white/60">
                        {{ __('livewire_user_settings_no_beneficiaries') }}
                    </div>
                @endif

                <div class="flex justify-end gap-3">
                    <x-ui.button type="button" x-on:click="isSettingsModal=false">{{ __('livewire_user_settings_cancel') }}</x-ui.button>
                    <x-ui.button type="button" wire:click="startAddingBeneficiary" wire:loading.attr="disabled"
                                 wire:target="startAddingBeneficiary">
                        {{ __('livewire_user_settings_add_beneficiary') }}
                    </x-ui.button>
                </div>
            </div>
        </form>
    </x-widget.modal>

    <x-widget.modal class="p-6" condition-name="isBeneficiaryModal" maxWidth="md">
        <form wire:submit.prevent="saveBeneficiary" class="space-y-8 w-auto md:w-[380px]" method="post" action="#">
            <div class="flex justify-between items-center gap-4">
                <h2 class="font-dela text-[20px] text-white">
                    {{ $editingBeneficiaryId ? __('livewire_user_settings_edit_beneficiary') : __('livewire_user_settings_add_beneficiary') }}
                </h2>
                <button type="button" x-on:click="isBeneficiaryModal=false; isSettingsModal=true">
                    <img src="{{ vite()->icon('/actions/cancel-large.svg') }}" alt="">
                </button>
            </div>

            <x-ui.input name="beneficiaryFullName" input-class="py-[5px] px-[12px]"
                        placeholder="{{ __('livewire_user_settings_beneficiary_full_name_placeholder') }}">
                {{ __('livewire_user_settings_beneficiary_full_name') }}
            </x-ui.input>

            <x-ui.input name="beneficiaryPhone" input-class="py-[5px] px-[12px]"
                        placeholder="{{ __('livewire_user_settings_beneficiary_phone_placeholder') }}">
                {{ __('livewire_user_settings_beneficiary_phone') }}
            </x-ui.input>

            <x-ui.input name="beneficiarySocialUrl" input-class="py-[5px] px-[12px]"
                        placeholder="https://t.me/username">
                {{ __('livewire_user_settings_beneficiary_social_url') }}
            </x-ui.input>

            <div class="flex justify-end gap-3">
                <x-ui.button type="button" x-on:click="isBeneficiaryModal=false; isSettingsModal=true">
                    {{ __('livewire_user_settings_cancel') }}
                </x-ui.button>
                <x-ui.submit-button wire:target="saveBeneficiary">
                    {{ __('livewire_user_settings_save') }}
                </x-ui.submit-button>
            </div>
        </form>
    </x-widget.modal>

    <x-widget.modal class="p-6" condition-name="isDeleteBeneficiaryModal" maxWidth="md">
        <div class="flex w-auto flex-col gap-8 md:w-[380px]">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-dela text-[20px] text-white">{{ __('livewire_user_settings_delete_beneficiary_title') }}</h2>
                <button type="button" x-on:click="isDeleteBeneficiaryModal=false; isSettingsModal=true">
                    <img src="{{ vite()->icon('/actions/cancel-large.svg') }}" alt="">
                </button>
            </div>

            <div class="rounded-[8px] border border-[#2E1D78] bg-[#17162D] p-4 text-white">
                <div class="font-medium">{{ $deletingBeneficiaryName }}</div>
                <p class="mt-2 text-[14px] text-white/60">
                    {{ __('livewire_user_settings_delete_beneficiary_confirm') }}
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <x-ui.button type="button" x-on:click="isDeleteBeneficiaryModal=false; isSettingsModal=true">
                    {{ __('livewire_user_settings_cancel') }}
                </x-ui.button>
                <x-ui.button type="button" wire:click="deleteBeneficiary" wire:loading.attr="disabled"
                             wire:target="deleteBeneficiary">
                    <span wire:loading.remove wire:target="deleteBeneficiary">{{ __('livewire_user_settings_delete') }}</span>
                    <span wire:loading wire:target="deleteBeneficiary">{{ __('livewire_user_settings_deleting') }}</span>
                </x-ui.button>
            </div>
        </div>
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
