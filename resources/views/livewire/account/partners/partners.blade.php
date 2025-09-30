<div x-data="{ isModalSentToPartner: false }">
    <x-ui.card-tabs :tabs="[
        'partners' => __('livewire_partners_partners_tab'),
        'log' => __('livewire_partners_log_tab'),
    ]" class="mx-auto">

        <x-slot name="partners">
            <div class="relative">
                <a href="{{ Storage::disk('public')->url('docs/how-works-partners-program.pdf') }}" target="_blank"
                    rel="noopener"
                    class="flex absolute right-0 top-0 text-[#B4FF59] font-semibold text-[14px]
                      underline underline-offset-[3px] decoration-dotted decoration-1
                      hover:text-[#D0FF8A] transition">
                    {{ __('livewire_partners_how_it_works') }}
                    <img src="{{ vite()->icon('/advantages/arrow-down-green.svg') }}" alt="">
                </a>
                <div class="text-white mb-[22px] flex items-center gap-[12px]">
                    <span class="font-dela text-[20px] h-[23px] leading-none">{{ __('livewire_partners_rank') }}</span>
                    <span
                        class="flex text-[24px] items-center justify-center
                             bg-[#B4FF59] text-[#17162D] font-semibold
                             rounded-[4px] w-[32px] h-[30px] leading-none">{{ $rank }}
                    </span>
                </div>
                <div class="grid md:[grid-template-columns:1fr_2fr] gap-8 mb-10">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            @if ($nextRank)
                                <span class="text-white font-semibold text-[16px]">
                                    {{ __('livewire_partners_progress_for_next_rank', ['nextRank' => $nextRank]) }}
                                </span>
                            @endif
                        </div>

                        @foreach ($progressBars as $bar)
                            <x-ui.progress :value="$bar['current']" :max="$bar['target']" class="mb-2">
                                <div class="flex justify-between">
                                    <span>{{ $bar['label'] }}</span>
                                    <span>
                                        {{ number_format($bar['current'], 0, ',', '') }}
                                        /
                                        {{ number_format($bar['target'], 0, ',', '') }}
                                    </span>
                                </div>
                            </x-ui.progress>
                        @endforeach
                    </div>

                    <div class="space-y-2">
                        <p class="text-white font-semibold">{{ __('livewire_partners_referral_link') }}</p>

                        <div class="flex gap-3">
                            <x-ui.input name="partnerLink" readonly wire:ignore class="flex-1"
                                value="{{ $partnerLink }}" input-class="py-[5px] px-[12px]" />

                            <button
                                x-on:click="navigator.clipboard.writeText('{{ $partnerLink }}').then(() => $dispatch('new-system-notification', {
                                        type: 'success',
                                        message: '{{ __('livewire_partners_link_copied_success') }}'
                                    }))
                                    .catch(() => $dispatch('new-system-notification', {
                                        type: 'error',
                                        message: '{{ __('livewire_partners_link_copy_failed') }}'
                                    }))"
                                class="inline-flex items-center border border-white/20 gap-2 bg-[#433F8E] hover:bg-[#3c3c70]
                                    rounded-[8px] px-[12px] py-[5px] text-[16px]">
                                <img src="{{ vite()->icon('actions/copy-new.svg') }}" class="w-4 h-4" alt="">
                                {{ __('livewire_partners_copy') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex mb-[32px] flex-col md:flex-row md:flex-wrap gap-8">
                    <div class="flex-none bg-white/5 rounded-[24px] p-8 space-y-6 border border-white/10">
                        <div class="font-dela text-[20px] text-white leading-none">
                            {{ __('livewire_partners_partner_balance_title') }}</div>
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-end">
                            <div class="flex flex-col items-start mb-[10px] md:mb-0">
                                <div class="flex items-center gap-1">
                                    <img src="{{ vite()->icon('/currency/itc-partners.svg') }}" alt="">
                                    <span class="text-[32px] font-extrabold leading-none">
                                        {{ number_format($partnerBalance, 2, ',', ' ') }}
                                    </span>
                                </div>
                                <span
                                    class="text-white/40 pl-[16px] text-[12px] font-semibold">{{ __('livewire_partners_partner_balance_label') }}</span>
                            </div>
                            <div class="flex gap-[30px] items-end">
                                <div class="flex flex-col items-start">
                                    <div class="flex items-center gap-1">
                                        <span class="text-white/60 text-[16px] font-bold leading-none">+</span>
                                        <img src="{{ vite()->icon('/currency/itc-partners.svg') }}" alt="">
                                        <span class="font-bold text-[16px] leading-none">
                                            {{ number_format($partnerWeek, 2, ',', '') }}
                                        </span>
                                    </div>
                                    <span
                                        class="text-white/40 font-semibold text-[12px] mt-[3px] pl-[13px]">{{ __('livewire_partners_for_week') }}</span>
                                </div>

                                <div class="flex flex-col items-start">
                                    <div class="flex items-center gap-1">
                                        <span class="text-white/60 text-[16px] font-bold leading-none">+</span>
                                        <img src="{{ vite()->icon('/currency/itc-partners.svg') }}" alt="">
                                        <span class="font-bold text-[16px] leading-none">
                                            {{ number_format($partnerMonth, 2, ',', '') }}
                                        </span>
                                    </div>
                                    <span
                                        class="text-white/40 font-semibold text-[12px] mt-[3px] pl-[13px]">{{ __('livewire_partners_for_month') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="inline-flex flex-col w-auto md:flex-row gap-3">
                                <x-ui.button wire:click="sendToMainSelf" :disabled="$partnerBalance <= 0"
                                    class="inline-flex w-auto self-start">
                                    {{ __('livewire_partners_to_main_balance') }}
                                    <img src="{{ vite()->icon('/advantages/arrow-across.svg') }}" class="pl-[5px]"
                                        alt="">
                                </x-ui.button>
                                <x-ui.button x-on:click="isModalSentToPartner = true" :disabled="$partnerBalance <= 0"
                                    class="inline-flex w-auto self-start">
                                    {{ __('livewire_partners_to_partner') }}
                                    <img src="{{ vite()->icon('/advantages/arrow-right.svg') }}" class="pl-[5px]"
                                        alt="">
                                </x-ui.button>
                            </div>
                            <x-ui.button :disabled="$partnerBalance <= 0" wire:click="openPackageModal" wire:target="openPackageModal"
                                wire:loading.attr="disabled" class="flex w-auto">

                                <span wire:loading.remove wire:target="openPackageModal">
                                    {{ __('livewire_partners_add_to_package') }}
                                </span>
                                <span wire:loading wire:target="openPackageModal">
                                    {{ __('livewire_partners_loading') }}
                                </span>

                                <img src="{{ vite()->icon('/advantages/store.svg') }}" class="pl-[5px]" alt="">
                            </x-ui.button>
                        </div>
                    </div>
                    <div class="flex-none bg-white/5 rounded-[24px] p-8 space-y-6 border border-white/10">
                        <div class="font-dela text-[20px] text-white leading-none">
                            {{ __('livewire_partners_regular_bonus_title') }}</div>
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-end">
                            <div class="flex flex-col items-start mb-[10px] md:mb-0">
                                <div class="flex items-center gap-1">
                                    <img src="{{ vite()->icon('/currency/itc-partners.svg') }}" alt="">
                                    <span class="text-[32px] font-extrabold leading-none">
                                        {{ number_format($regularAvailable, 2, ',', '') }}
                                    </span>
                                </div>
                                <span class="text-white/40 pl-[16px] text-[12px] font-semibold">
                                    {{ __('livewire_partners_available_for_withdrawal') }}
                                </span>
                            </div>
                            <div class="flex gap-[30px] items-end">
                                <div class="flex flex-col items-start">
                                    <div class="flex items-center gap-1">
                                        <span class="text-white/60 text-[16px] font-bold leading-none">+</span>
                                        <img src="{{ vite()->icon('/currency/itc-partners.svg') }}" alt="">
                                        <span class="font-bold text-[16px] leading-none">
                                            {{ number_format($regularTotal, 2, ',', '') }}
                                        </span>
                                    </div>
                                    <span
                                        class="text-white/40 font-semibold text-[12px] mt-[3px] pl-[13px]">{{ __('livewire_partners_total_ever') }}</span>
                                </div>
                                <div class="flex flex-col items-start">
                                    <div class="flex items-center gap-1">
                                        <span class="text-white/60 text-[16px] font-bold leading-none">+</span>
                                        <img src="{{ vite()->icon('/currency/itc-partners.svg') }}" alt="">
                                        <span class="font-bold text-[16px] leading-none">
                                            {{ number_format($regularWeek, 2, ',', '') }}
                                        </span>
                                    </div>
                                    <span class="text-white/40 font-semibold text-[12px] mt-[3px] pl-[13px]">
                                        {{ __('livewire_partners_for_week') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <x-ui.button wire:click="regularToPartner" :disabled="$regularAvailable <= 0" class="inline-flex w-auto">
                            {{ __('livewire_partners_regular_to_partner') }}
                            <img src="{{ vite()->icon('/advantages/arrow-right.svg') }}" class="pl-[5px]"
                                alt="">
                        </x-ui.button>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-center">
                    <h3 class="text-[16px] md:text-[20px] font-dela mb-[10px] md:mb-0 self-start md:self-center">
                        {{ __('livewire_partners_my_partners') }}
                    </h3>
                    @if ($partners->isNotEmpty())
                        <div class="w-auto min-w-[335px] md:min-w-[400px]">
                            <x-ui.select wire:key="line-{{ $line }}" name="line" wire:model="line"
                                :options="collect($availableLines)
                                    ->mapWithKeys(fn($n) => [$n => __('livewire_partners_log_line') . $n])
                                    ->all()"
                                placeholder="{{ __('livewire_partners_line_placeholder', ['line' => $line]) }}" />
                        </div>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class=" font-dela text-[16xp] leading-[40px]">
                                @if ($partners->isNotEmpty())
                                    <th class="py-1.5 pr-4 font-thin">{{ __('livewire_partners_table_nickname') }}
                                    </th>
                                @endif
                                <th class="py-1.5 pr-4 font-medium">{{ __('livewire_partners_table_telegram') }}</th>
                                <th class="py-1.5 pr-4 font-thin whitespace-nowrap">
                                    {{ __('livewire_partners_table_start_bonus') }}</th>
                                <th class="py-1.5 pr-4 font-thin whitespace-nowrap">
                                    {{ __('livewire_partners_table_regular_bonus') }}</th>
                                <th class="py-1.5 font-thin whitespace-nowrap">
                                    {{ __('livewire_partners_table_total_profit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($partners as $u)
                                <tr class="font-white">
                                    <td class="py-1.5 pr-4">{{ $u->username }}</td>
                                    <td class="py-1.5 pr-4">
                                        @if ($u->telegram)
                                            <a href="https://t.me/{{ ltrim($u->telegram, '@') }}"
                                                class="text-[#B4FF59] hover:underline" target="_blank">
                                                @ {{ $u->telegram }}
                                            </a>
                                        @else
                                            <span class="text-white/60">—</span>
                                        @endif
                                    </td>
                                    <td class="py-1.5 pr-4">{{ $u->start_bonus ?? '—' }}</td>
                                    <td class="py-1.5 pr-4">{{ $u->regular_bonus ?? '—' }}</td>
                                    <td class="py-1.5">{{ $u->total_profit ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="py-3 text-center text-white/60" colspan="5">
                                        {{ __('livewire_partners_no_invited_partners') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-slot>

        <x-slot name="log">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="font-dela text-[16px] leading-[40px]">
                            <th class="py-1.5 pr-4 font-thin">{{ __('livewire_partners_log_date') }}</th>
                            <th class="py-1.5 pr-4 font-thin">{{ __('livewire_partners_log_user') }}</th>
                            <th class="py-1.5 pr-4 font-thin">{{ __('livewire_partners_log_line') }}</th>
                            <th class="py-1.5 font-thin">{{ __('livewire_partners_log_event') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($logRows as $row)
                            <tr>
                                <td class="py-1.5 pr-4 whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="py-1.5 pr-4 text-[#B4FF59]">{{ '@' . $row['user'] }}</td>
                                <td class="py-1.5 pr-4">{{ $row['level'] }}</td>
                                <td class="py-1.5 whitespace-nowrap">{{ $row['event'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-0">
                                    <div class="min-h-[400px] flex items-center justify-center">
                                        <p class="text-white/60">{{ __('livewire_partners_no_events') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>
    </x-ui.card-tabs>

    <x-widget.modal class="p-6" condition-name="isModalSentToPartner" maxWidth="md">
        <form wire:submit.prevent="sendToPartner" class="space-y-10 w-[320px] md:w-[350px]">
            <div class="flex justify-between items-center">
                <h2 class="font-dela text-[20px] text-white">
                    {{ __('livewire_partners_modal_send_to_partner_title') }}
                </h2>
                <figure class="cursor-pointer pt-[5px]" x-on:click="isModalSentToPartner = false">
                    <img src="{{ vite()->icon('/actions/cancel-large.svg') }}" alt="">
                </figure>
            </div>

            <div class="flex items-center gap-2 text-[16px]">
                <p class="text-white">
                    {{ __('livewire_partners_modal_partner_balance') }}
                </p>
                <div class="flex items-center gap-1">
                    <img src="{{ vite()->icon('/currency/itc-partners.svg') }}" class="w-4 h-4" alt="">
                    <span class="font-extrabold text-white">{{ number_format($partnerBalance, 0, ',', '') }}</span>
                </div>
            </div>

            <x-ui.input name="toPartnerAmount" placeholder="{{ __('livewire_partners_modal_enter_amount') }}"
                validate="number" input-class="py-[5px] px-[12px]">
                {{ __('livewire_partners_modal_transfer_amount_label') }}
            </x-ui.input>

            <x-ui.suggest name="toUsername" placeholder="Введите ник" :list="$nicknames">
                {{ __('livewire_partners_modal_partner_username_label') }}
            </x-ui.suggest>

            <div class="flex justify-end gap-3">
                <x-ui.button type="button" x-on:click="isModalSentToPartner = false">
                    {{ __('livewire_partners_modal_cancel') }}
                </x-ui.button>

                <x-ui.submit-button action="sendToPartner">
                    {{ __('livewire_partners_modal_transfer') }}
                </x-ui.submit-button>
            </div>

        </form>
    </x-widget.modal>
    <x-account.partners.to-package-modal :partner-balance="$partnerBalance" />
</div>
