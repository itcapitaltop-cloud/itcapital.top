@php
    use Illuminate\Support\Carbon;
    $depositAddress = config('wallet.deposit_address');
    use Illuminate\Support\Facades\Log;
    $withdrawDisabled = !Carbon::now()->isSunday();
@endphp

<x-ui.card-tabs :tabs="[
    'deposit' => __('livewire_finance_tab_deposit'),
    'withdraw' => __('livewire_finance_tab_withdraw'),
    'log' => __('livewire_finance_tab_log'),
]" class="mx-auto">

    {{-- ▸ вкладка «Пополнение счета» --}}
    <x-slot name="deposit">
        <form x-data="{ depositSource: '', helpOpen: false }" wire:submit="createDeposit" @submit.prevent class="grid md:grid-cols-2 gap-10"
            data-network="{{ config('wallet.network') }}">

            <div class="flex justify-end md:hidden">
                <button type="button" class="text-[#B4FF59] text-[14px] underline decoration-dashed underline-offset-2"
                    x-on:click="helpOpen = true">
                    {{ __('livewire_finance_deposit_help_mobile') }}
                </button>
            </div>

            {{-- ▸ левая колонка – форма --}}
            <div class="space-y-6 flex flex-col gap-[16px]">

                {{-- Источник --}}
                <x-ui.select label="{{ __('livewire_finance_deposit_source_label') }}" name="depositForm.depositSource"
                    x-model="depositSource" :options="[
                        'crypto' => '{{ __('livewire_finance_deposit_source_crypto') }}',
                        'fiat' => '{{ __('livewire_finance_deposit_source_fiat') }}',
                    ]"
                    placeholder="{{ __('livewire_finance_deposit_source_crypto') }}" class="" />

                {{-- Сумма --}}
                <x-ui.input name="depositForm.depositAmount" type="text" step="0.01"
                    placeholder="{{ __('livewire_finance_deposit_amount_placeholder') }}"
                    input-class="py-[5px] px-[12px]" validate="number">
                    {{ __('livewire_finance_deposit_amount_label') }}
                </x-ui.input>

                {{-- Адрес кошелька + копия  (только для «Криптовалюта») --}}
                <template x-if="depositSource !== 'fiat'">
                    <div class="flex w-full gap-3">
                        <x-ui.input name="depositForm.depositAddress" validate="wallet" value="{{ $depositAddress }}"
                            readonly class="flex-1" input-class="py-[5px] px-[12px]">
                            {{ __('livewire_finance_deposit_wallet_address_label') }}
                            <span class="text-white/50">{{ __('livewire_finance_deposit_network_label') }}
                                {{ config('wallet.network') }}</span>
                        </x-ui.input>
                        <button type="button"
                            x-on:click="navigator.clipboard.writeText(@js($depositAddress)).then(() => $dispatch('new-system-notification', {
                                        type: 'success',
                                        message: '{{ __('livewire_finance_notification_copy_success') }}'
                                    }))
                                    .catch(() => $dispatch('new-system-notification', {
                                        type: 'error',
                                        message: '{{ __('livewire_finance_notification_copy_failed') }}'
                                    }))"
                            class="shrink-0 rounded-[8px] flex items-center justify-center px-[6px] py-[5px] mt-[22px]
                                   bg-[#433F8E] hover:bg-[#3c3c70] border border-white/20">
                            <img src="{{ vite()->icon('/actions/copy-new.svg') }}" alt="">
                        </button>
                    </div>
                </template>

                {{-- Хеш / Наименование банка --}}
                <x-ui.input name="depositForm.transactionHash"
                    x-bind:placeholder="depositSource === 'fiat'
                        ?
                        '{{ __('livewire_finance_deposit_transaction_hash_placeholder_fiat') }}' :
                        '{{ __('livewire_finance_deposit_transaction_hash_placeholder_crypto') }}'"
                    input-class="py-[5px] px-[12px]">
                    <template x-if="depositSource === 'fiat'">
                        <span>{{ __('livewire_finance_deposit_bank_name_label_fiat') }}</span>
                    </template>
                    <template x-if="depositSource !== 'fiat'">
                        <span>{{ __('livewire_finance_deposit_transaction_hash_label_crypto') }}</span>
                    </template>
                </x-ui.input>

                {{-- Ссылка «Получить реквизиты»  (только для «Фиат») --}}
                <template x-if="depositSource === 'fiat'">
                    <div>
                        <a href="https://t.me/ITCAPITALTOP" target="_blank"
                            class="text-[#B4FF59] w-auto underline underline-offset-2 hover:text-[#C0FFA1]">
                            {{ __('livewire_finance_deposit_get_details_button') }}
                        </a>
                    </div>
                </template>

                {{-- submit --}}
                <div>
                    <x-ui.submit-button class="w-auto" action="createDeposit">
                        {{ __('livewire_finance_deposit_submit_button') }}
                    </x-ui.submit-button>
                </div>
            </div>

            <div x-show="helpOpen" x-transition.opacity
                class="fixed inset-0 z-40 flex items-center justify-center md:hidden">
                {{-- затемнение --}}
                <div class="absolute inset-0 bg-black/60" x-on:click="helpOpen = false"></div>

                {{-- сам блок подсказки --}}
                <div
                    class="relative z-50 max-w-[90%] w-[340px]
                    rounded-[20px] p-6 bg-[#211F41] border border-white/10
                    text-white/80 text-[15px] leading-6 space-y-4">
                    {{-- заголовок + close --}}
                    <div class="flex justify-between items-start">
                        <h3 class="font-semibold text-white">{{ __('livewire_finance_deposit_help_mobile') }}</h3>
                        <button type="button" class="text-white/50" x-on:click="helpOpen = false">✕</button>
                    </div>

                    {{-- инструкции для криптовалюты --}}
                    <template x-if="depositSource !== 'fiat'">
                        <div class="space-y-4">
                            <div>
                                {{ __('livewire_finance_deposit_help_crypto_title') }}
                                <ul class="list-decimal list-inside mt-2">
                                    <li>{{ __('livewire_finance_deposit_help_crypto_step_1') }}</li>
                                    <li>{{ __('livewire_finance_deposit_help_crypto_step_2') }}</li>
                                    <li>{{ __('livewire_finance_deposit_help_crypto_step_3') }}</li>
                                </ul>
                            </div>
                            <div class="flex justify-center">
                                <div class="bg-white p-[10px] w-[124px] rounded-[13px]">
                                    <img src="{{ route('wallet.qr', $depositAddress) }}" alt="">
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- инструкции для фиата --}}
                    <template x-if="depositSource === 'fiat'">
                        <div>
                            {{ __('livewire_finance_deposit_help_fiat_title') }}
                            <ul class="list-decimal list-inside mt-2">
                                <li>{{ __('livewire_finance_deposit_help_fiat_step_1') }}</li>
                                <li>{{ __('livewire_finance_deposit_help_fiat_step_2') }}</li>
                                <li>{{ __('livewire_finance_deposit_help_fiat_step_3') }}</li>
                            </ul>
                        </div>
                    </template>
                </div>
            </div>

            <div class="hidden md:flex flex-col h-full gap-4 leading-6 text-[16px] text-white/30">

                {{-- инструкции для криптовалюты --}}
                <template x-if="depositSource !== 'fiat'">
                    <div class="flex flex-col flex-1 justify-between">
                        <div>
                            {{ __('livewire_finance_deposit_help_crypto_title') }}
                            <ul class="list-decimal list-inside mt-2">
                                <li>{{ __('livewire_finance_deposit_help_crypto_step_1') }}</li>
                                <li>{{ __('livewire_finance_deposit_help_crypto_step_2') }}</li>
                                <li>{{ __('livewire_finance_deposit_help_crypto_step_3') }}</li>
                            </ul>
                        </div>
                        <div class="mt-auto flex items-end justify-end">
                            <div class="bg-white p-[10px] w-[124px] rounded-[13px]">
                                <img src="{{ route('wallet.qr', $depositAddress) }}" alt="">
                            </div>
                        </div>
                    </div>
                </template>

                {{-- инструкции для фиата --}}
                <template x-if="depositSource === 'fiat'">
                    <div>
                        {{ __('livewire_finance_deposit_help_fiat_title_1') }}
                        <ul class="list-decimal list-inside mt-2">
                            <li>{{ __('livewire_finance_deposit_help_fiat_step_1') }}</li>
                            <li>{{ __('livewire_finance_deposit_help_fiat_step_2') }}</li>
                            <li>{{ __('livewire_finance_deposit_help_fiat_step_3') }}</li>
                        </ul>
                    </div>
                </template>

            </div>
        </form>
    </x-slot>

    <x-slot name="withdraw">
        <form x-data="{ withdrawSource: '', helpOpen: false }" wire:submit="createWithdraw" @submit.prevent
            data-network="{{ config('wallet.network') }}" class="grid md:grid-cols-2 gap-10">

            <div class="flex justify-end md:hidden">
                <button type="button" class="text-[#B4FF59] text-[14px] underline decoration-dashed underline-offset-2"
                    x-on:click="helpOpen = true">
                    {{ __('livewire_finance_withdraw_help_mobile') }}
                </button>
            </div>

            <div class="flex flex-col gap-[40px]">
                <x-ui.select label="{{ __('livewire_finance_withdraw_format_label') }}"
                    name="withdrawForm.withdrawSource" x-model="withdrawSource" :options="[
                        'crypto' => '{{ __('livewire_finance_deposit_source_crypto') }}',
                        'fiat' => '{{ __('livewire_finance_deposit_source_fiat') }}',
                    ]"
                    placeholder="{{ __('livewire_finance_deposit_source_crypto') }}" />
                <div>
                    <x-ui.input name="withdrawForm.withdrawAmount" type="text" step="0.01"
                        placeholder="{{ __('livewire_finance_withdraw_amount_placeholder') }}"
                        input-class="py-[5px] px-[12px]" validate="number">
                        {{ __('livewire_finance_withdraw_amount_label') }}
                    </x-ui.input>
                    <p x-show="parseFloat($wire.withdrawForm.withdrawAmount) >= 10" class="text-white px-4 pt-1.5">
                        {{ __('livewire_finance_withdraw_to_receive') }} <span
                            x-text="(parseFloat($wire.withdrawForm.withdrawAmount) * 0.98 - 2).toFixed(2)"></span>
                    </p>
                </div>
                <template x-if="withdrawSource === 'fiat'">
                    <div class="flex flex-col gap-[40px]">
                        {{-- Номер СБП (телефон получателя) --}}
                        <x-ui.input name="withdrawForm.sbpPhone"
                            placeholder="{{ __('livewire_finance_withdraw_sbp_phone_placeholder') }}"
                            input-class="py-[5px] px-[12px]" type="text" validate="phone">
                            {{ __('livewire_finance_withdraw_sbp_phone_label') }}
                        </x-ui.input>

                        {{-- Наименование банка --}}
                        <x-ui.input name="withdrawForm.bankName"
                            placeholder="{{ __('livewire_finance_withdraw_bank_name_placeholder') }}"
                            input-class="py-[5px] px-[12px]">
                            {{ __('livewire_finance_withdraw_bank_name_label') }}
                        </x-ui.input>

                        {{-- ФИО получателя --}}
                        <x-ui.input name="withdrawForm.recipientName"
                            placeholder="{{ __('livewire_finance_withdraw_recipient_name_placeholder') }}"
                            input-class="py-[5px] px-[12px]">
                            {{ __('livewire_finance_withdraw_recipient_name_label') }}
                        </x-ui.input>
                    </div>
                </template>

                <template x-if="withdrawSource !== 'fiat'">
                    <div class="flex w-full gap-3">
                        <x-ui.input name="withdrawForm.address" validate="wallet"
                            placeholder="{{ __('livewire_finance_withdraw_wallet_address_label_placeholder') }}"
                            class="flex-1" input-class="py-[5px] px-[12px]">
                            {{ __('livewire_finance_withdraw_wallet_address_label') }}
                            <span class="text-white/50">{{ __('livewire_finance_deposit_network_label') }}
                                {{ config('wallet.network') }}</span>
                        </x-ui.input>
                    </div>
                </template>

                <div>
                    <x-ui.submit-button action="createWithdraw" :disabled="$withdrawDisabled">
                        {{ __('livewire_finance_withdraw_submit_button') }}
                    </x-ui.submit-button>
                </div>
            </div>

            <div x-show="helpOpen" x-transition.opacity
                class="fixed inset-0 z-40 flex items-center justify-center md:hidden">
                <!-- затемнение -->
                <div class="absolute inset-0 bg-black/60" x-on:click="helpOpen = false"></div>

                <!-- коробка с инструкциями -->
                <div
                    class="relative z-50 max-w-[90%] w-[340px]
                rounded-[20px] p-6 bg-[#211F41] border border-white/10
                text-white/80 text-[15px] leading-6 space-y-4">
                    <div class="flex justify-between items-start">
                        <h3 class="font-semibold text-white">{{ __('livewire_finance_withdraw_help_mobile') }}</h3>
                        <button type="button" class="text-white/50" x-on:click="helpOpen = false">✕</button>
                    </div>

                    @if ($withdrawDisabled)
                        <x-ui.alert type="warning">
                            {{ __('livewire_finance_withdraw_schedule_warning') }}
                        </x-ui.alert>
                    @endif

                    <!-- подсказка для криптовалюты -->
                    <template x-if="withdrawSource !== 'fiat'">
                        <div>
                            {{ __('livewire_finance_withdraw_help_crypto_title') }}
                            <ul class="list-decimal list-inside mt-2">
                                <li>{{ __('livewire_finance_withdraw_help_crypto_step_1') }}</li>
                                <li>{{ __('livewire_finance_withdraw_help_crypto_step_2') }}</li>
                            </ul>
                        </div>
                    </template>

                    <!-- подсказка для фиата -->
                    <template x-if="withdrawSource === 'fiat'">
                        <div>
                            {{ __('livewire_finance_withdraw_help_fiat_title') }}
                            <ul class="list-decimal list-inside mt-2">
                                <li>{{ __('livewire_finance_withdraw_help_fiat_step_1') }}</li>
                                <li>{{ __('livewire_finance_withdraw_help_fiat_step_2') }}</li>
                                <li>{{ __('livewire_finance_withdraw_help_fiat_step_3') }}</li>
                                <li>{{ __('livewire_finance_withdraw_help_fiat_step_4') }}</li>
                            </ul>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ▸ правая колонка – текст подсказки -------------------------------------- --}}
            <div class="hidden md:flex flex flex-col h-full gap-4 leading-6 text-[16px] text-white/30">

                @if ($withdrawDisabled)
                    <x-ui.alert type="warning">
                        {{ __('livewire_finance_withdraw_schedule_warning') }}
                    </x-ui.alert>
                @endif

                {{-- Подсказка для криптовалюты --}}
                <template x-if="withdrawSource !== 'fiat'">
                    <div>
                        {{ __('livewire_finance_withdraw_help_crypto_title') }}
                        <ul class="list-decimal list-inside mt-2">
                            <li>{{ __('livewire_finance_withdraw_help_crypto_step_1') }}</li>
                            <li>{{ __('livewire_finance_withdraw_help_crypto_step_2') }}</li>
                        </ul>
                    </div>
                </template>

                {{-- Подсказка для фиата --}}
                <template x-if="withdrawSource === 'fiat'">
                    <div>
                        {{ __('livewire_finance_withdraw_help_crypto_title_1') }}
                        <ul class="list-decimal list-inside mt-2">
                            <li>{{ __('livewire_finance_withdraw_help_crypto_step_1') }}</li>
                            <li>{{ __('livewire_finance_withdraw_help_fiat_step_2') }}</li>
                            <li>{{ __('livewire_finance_withdraw_help_fiat_step_3') }}</li>
                            <li>{{ __('livewire_finance_withdraw_help_fiat_step_4') }}</li>
                        </ul>
                    </div>
                </template>

            </div>
        </form>
    </x-slot>

    <x-slot name="log">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-white whitespace-nowrap">
                <thead class="text-white">
                    <tr class="border-none font-dela text-[16px] leading-[40px]">
                        <th class="py-2 pr-4">{{ __('livewire_finance_log_date_header') }}</th>
                        <th class="py-2 pr-4">{{ __('livewire_finance_log_amount_header') }}</th>
                        <th class="py-2 pr-4">{{ __('livewire_finance_log_type_header') }}</th>
                        <th class="py-2">{{ __('livewire_finance_log_status_header') }}</th>
                    </tr>
                </thead>
                <tbody class="font-semibold text-[16px]">
                    @forelse($operations as $op)
                        <tr class="border-none">
                            <td class="py-2 pr-4">
                                {{ $op['created_at']->format('d.m.Y, H:i') }}
                            </td>
                            <td class="py-2 pr-4">
                                ${{ number_format($op['amount']) }}
                            </td>
                            <td class="py-2 pr-4">
                                <img src="{{ vite()->icon($op['arrow'] === 'down' ? '/advantages/arrow-down.svg' : '/advantages/arrow-up.svg') }}"
                                    class="inline-block mr-1 align-middle"
                                    alt="{{ $op['arrow'] === 'down' ? '↓' : '↑' }}" />
                                {{ $op['type'] }}
                            </td>
                            <td class="py-2 pl-4">
                                {{ $op['status'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-0"> {{-- убираем внутренние отступы --}}
                                <div class="min-h-[400px] flex items-center justify-center">
                                    <p class="text-white/60">{{ __('livewire_finance_log_empty') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot>

</x-ui.card-tabs>
