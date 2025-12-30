@props([
    'package'
])

@php
    use App\Enums\Itc\PackageTypeEnum;
    use Illuminate\Support\Facades\Log;
@endphp

<div class="flex flex-col gap-6 items-center lg:flex-row lg:gap-10">
    {{-- ► Карта пакета --}}
    <div class="relative w-full max-w-[356px] aspect-[356/208] rounded-[20px] sm:rounded-[28px] flex-shrink-0">
        <img src="{{ vite()->icon('/cards/bg-logo-' . $package->type->value . '.png') }}"
             class="w-full h-full absolute z-[10] object-cover rounded-[20px] sm:rounded-[28px]" alt="">
        <div class="relative z-[11] w-full h-full bg-none">
            <div class="relative h-full flex flex-col justify-between p-4 sm:p-6 text-white">

                {{-- ► Депозит --}}
                <div class="flex items-baseline gap-1">
                    <span class="text-[24px] sm:text-[30px] md:text-[36px] font-dela leading-none">
                        {{ $package->type === PackageTypeEnum::PRESENT && $package->zeroing
                            ? 0
                            : scale(
                                $package->transaction->amount +
                                    ($package->partner_transfers_sum_amount ?? 0) +
                                    ($package->reinvest_to_body_sum_amount ?? 0) -
                                    ($package->balance_withdraws_sum_amount ?? 0),
                            )->stripTrailingZeros() }}
                    </span>
                    <img src="{{ vite()->icon('/currency/itc-staking.svg') }}" class="w-4 sm:w-[19px] translate-y-[2px]" alt="ITC">
                </div>

                {{-- ► Нижняя часть карты --}}
                <div class="flex items-end gap-3 sm:gap-5">
                    <div class="flex flex-col gap-1 sm:gap-2">
                        <span class="uppercase text-white tracking-wide text-[8px] sm:text-[10px] font-normal opacity-50">
                            {{ $package->uuid }}
                        </span>
                        <img src="{{ vite()->icon('/cards/chip.svg') }}" class="w-6 sm:w-8" alt="chip">
                    </div>
                    <div class="flex flex-col gap-2 sm:gap-3">
                        <span class="text-white tracking-wide text-[8px] sm:text-[10px] font-normal opacity-50">
                            дата покупки
                        </span>
                        <span class="uppercase text-white/90 tracking-wide text-[12px] sm:text-[14px]">
                            {{ $package->created_at->format('d/m/Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ► Разделительная линия (вертикальная на десктопе) --}}
    <div class="hidden lg:block flex-shrink-0" style="width: 1px; height: 174px; background-color: rgba(255, 255, 255, 0.3);"></div>

    {{-- ► Статистика --}}
    <div class="flex flex-col gap-4 sm:gap-6 text-white w-full lg:w-[400px]">
        <div class="flex justify-between items-center">
            <span class="text-white/70 text-sm sm:text-base">Доходность в месяц</span>
            <span class="text-lg sm:text-xl font-semibold">{{ $package->month_profit_percent }}%</span>
        </div>

        <div class="flex justify-between items-center">
            <span class="text-white/70 text-sm sm:text-base">Доходность за прошлый месяц</span>
            <div class="flex items-baseline gap-1">
                <img src="{{ vite()->icon('/currency/itc-staking.svg') }}" class="w-3 sm:w-4" alt="ITC">
                <span class="text-lg sm:text-xl font-semibold">{{ $package->last_month_profit ?? 0 }}</span>
            </div>
        </div>

        <div class="flex justify-between items-center">
            <span class="text-white/70 text-sm sm:text-base">Доходность общая</span>
            <div class="flex items-baseline gap-1">
                <img src="{{ vite()->icon('/currency/itc-staking.svg') }}" class="w-3 sm:w-4" alt="ITC">
                <span class="text-lg sm:text-xl font-semibold">
                {{ scale($package->profits_sum_amount ?? 0)->stripTrailingZeros() }}
            </span>
            </div>
        </div>
    </div>

    {{-- ► Разделительная линия (горизонтальная на мобильных, внизу статистики) --}}
    <div class="block lg:hidden flex-shrink-0" style="width: 100%; height: 1px; background-color: rgba(255, 255, 255, 0.3);"></div>
</div>
