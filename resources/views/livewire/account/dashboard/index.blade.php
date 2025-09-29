<div class="grid gap-6 lg:grid text-white">

    {{-- ▸ Обзор инвестиций --}}
    <x-bg.main >
        {{-- заголовок --}}
        <div class="px-6 pt-[16px] pb-[5px] md:pt-6 md:pb-4">
            <h3 class="text-[20px] font-dela">{{ __('livewire_dashboard_index_investments_overview_title') }}</h3>
        </div>

        {{-- метрики --}}
        <x-bg.section-slim class="grid grid-cols-[auto_minmax(0,1fr)] md:grid-cols-[auto_1fr] gap-y-3 gap-x-auto md:gap-x-[60px]">
            <p class="font-semibold">{{ __('livewire_dashboard_index_total_packages') }}</p>
            <p class="font-extrabold text-right md:text-left">{{ $packagesCount }}</p>

            <p class="font-semibold">{{ __('livewire_dashboard_index_packages_amount') }}</p>
            <p class="font-extrabold whitespace-nowrap text-right md:text-left">
                <img src="{{ vite()->icon('currency/itc.svg') }}" class="inline w-[12px] -mt-0.5" alt="">
                {{ scale($depositTotalAmount) }}
            </p>

            <p class="col-span-2 border-t border-[#2c2c57] my-1"></p>

            <p class="font-semibold whitespace-nowrap">{{ __('livewire_dashboard_index_weekly_yield') }}</p>
            <p class="font-extrabold whitespace-nowrap text-right md:text-left">
                <img src="{{ vite()->icon('currency/itc.svg') }}" class="inline w-[12px] -mt-0.5" alt="">
                {{ scale($yieldWeek)->isNegative() ? '0' : scale($yieldWeek)->stripTrailingZeros() }}
            </p>

            <p class="font-semibold">{{ __('livewire_dashboard_index_total_yield') }}</p>
            <p class="font-extrabold whitespace-nowrap text-right md:text-left">
                <img src="{{ vite()->icon('currency/itc.svg') }}" class="inline w-[12px] -mt-0.5" alt="">
                {{ scale($yieldTotal)->isNegative() ? '0' : scale($yieldTotal)->stripTrailingZeros() }}
            </p>
        </x-bg.section-slim>
    </x-bg.main>



    {{-- ▸ Сводка по партнёрам --}}
    <x-bg.main>
        {{-- заголовок --}}
        <div class="px-6 pt-[16px] pb-[5px] md:pt-6 md:pb-4 flex justify-between">
            <h3 class="text-[20px] font-dela">{{ __('livewire_dashboard_index_partners_summary_title') }}</h3>
            {{-- ссылка реферала --}}
            <div class="flex items-center gap-3 hidden md:flex">
                <x-ui.input name="partnerLink" readonly value="{{ $partnerLink }}" input-class="px-3 py-[8px]" />

                <button
                    x-data
                    x-on:click="navigator.clipboard.writeText('{{ $partnerLink }}').then(() => $dispatch('new-system-notification', {
                            type: 'success',      // в presets уже есть «success»
                            message: '{{ __('livewire_dashboard_index_link_copied_success') }}'
                        }))
                        .catch(() => $dispatch('new-system-notification', {
                            type: 'error',
                            message: '{{ __('livewire_dashboard_index_link_copy_failed') }}'
                        }))"
                    class="inline-flex items-center border border-white/20 gap-2 bg-[#433F8E] hover:bg-[#3c3c70]
                           rounded-[8px] px-3 py-1.5 text-[16px]">
                    <img src="{{ vite()->icon('actions/copy-new.svg') }}" class="w-[12px]" alt="">
                    {{ __('livewire_dashboard_index_copy_button') }}
                </button>
            </div>
        </div>

        {{-- метрики --}}
        <x-bg.section-slim class="grid grid-cols-[auto_minmax(0,1fr)] md:grid-cols-[auto_1fr] gap-y-3 gap-x-auto md:gap-x-[60px] pb-0 md:pb-4">

            <p class="font-semibold whitespace-nowrap">{{ __('livewire_dashboard_index_total_partners') }}</p>
            <p class="font-extrabold whitespace-nowrap text-right md:text-left">{{ $partnersTotal }}</p>

            <p class="font-semibold whitespace-nowrap">{{ __('livewire_dashboard_index_partners_in_lines') }}</p>
            <div class="flex flex-wrap gap-2 justify-end md:justify-start">
                @foreach($partnersInLines as $level => $count)
                    <span
                        class="inline-flex items-center gap-1 text-[16px]">
                        <span
                            class="inline-flex items-center justify-center
                               w-[16px] h-[17px] text-[12px] font-medium
                               border border-white rounded-[4px] select-none opacity-50">
                            {{ $level }}
                        </span>
                        <span class="font-extrabold">{{ $count }}</span>
                    </span>
                @endforeach
            </div>

            <p class="font-semibold whitespace-nowrap"> {{ __('livewire_dashboard_index_growth_week') }}</p>
            <p class="font-extrabold whitespace-nowrap flex items-center gap-1 justify-end md:justify-start">
                {{ $growthWeek }}
                <img src="{{ vite()->icon('currency/arrow-growth.svg') }}" alt="">
            </p>

            <p class="font-semibold whitespace-nowrap">{{ __('livewire_dashboard_index_growth_month') }}</p>
            <p class="font-extrabold whitespace-nowrap flex items-center gap-1 justify-end md:justify-start">
                {{ $growthMonth }}
                <img src="{{ vite()->icon('currency/arrow-growth.svg') }}" alt="">
            </p>

            <p class="col-span-2 border-t border-[#2c2c57] my-1"></p>

            <p class="font-semibold whitespace-nowrap">{{ __('livewire_dashboard_index_weekly_yield') }}</p>
            <div class="font-extrabold whitespace-nowrap flex text-[16px] gap-1 items-center justify-end md:justify-start">
                <img src="{{ vite()->icon('currency/itc-partners.svg') }}" class="inline w-[12px] -mt-0.5" alt="">{{ scale($weekStats['delta'])->isNegative() ? '0' : scale($weekStats['delta'])->stripTrailingZeros() }}
            </div>

            <p class="font-semibold whitespace-nowrap">{{ __('livewire_dashboard_index_partners_monthly_yield') }}</p>
            <div class="font-extrabold whitespace-nowrap flex text-[16px] gap-1 items-center justify-end md:justify-start">
                <img src="{{ vite()->icon('currency/itc-partners.svg') }}" class="inline w-[12px] -mt-0.5" alt="">{{ scale($monthStats['delta'])->isNegative() ? '0' : scale($monthStats['delta'])->stripTrailingZeros() }}
            </div>
        </x-bg.section-slim>

        <x-bg.section-slim class="md:hidden gap-y-3 gap-x-[60px]">
            <p class="col-span-2 border-t border-[#2c2c57] my-1"></p>
            <div>
                <p class="py-[8px] font-semibold">{{ __('livewire_dashboard_index_partner_invite_link_label') }}</p>
                <div class="flex items-center gap-3 justify-end md:justify-start">
                    <x-ui.input name="partnerLink" readonly value="{{ $partnerLink }}" input-class="px-3 py-[8px]" />

                    <button
                        x-data
                        x-on:click="navigator.clipboard.writeText('{{ $partnerLink }}').then(() => $dispatch('new-system-notification', {
                                    type: 'success',
                                    message: '{{ __('livewire_dashboard_index_link_copied_success') }}'
                                }))
                                .catch(() => $dispatch('new-system-notification', {
                                    type: 'error',
                                    message: '{{ __('livewire_dashboard_index_link_copy_failed') }}'
                                }))"
                        class="inline-flex items-center border border-white/20 gap-2 bg-[#433F8E] hover:bg-[#3c3c70]
                                   rounded-[8px] px-3 py-1.5 text-[16px] shrink-0">
                        <img src="{{ vite()->icon('actions/copy-new.svg') }}" class="w-[12px]" alt="">
                        {{ __('livewire_dashboard_index_copy_button') }}
                    </button>
                </div>
            </div>
        </x-bg.section-slim>
    </x-bg.main>

</div>
