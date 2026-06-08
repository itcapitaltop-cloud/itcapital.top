@props(['definition'])

@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $visualType = in_array($definition->slug, ['standard', 'privilege', 'vip', 'present'], true)
        ? $definition->slug
        : $definition->type->value;
    $cardImage = $definition->card_image_path
        ? Storage::disk('public')->url($definition->card_image_path)
        : vite()->icon('/cards/bg-logo-' . $visualType . '.png');
    $profitPercent = scale($definition->default_profit_percent)->stripTrailingZeros();
    $minStartAmount = scale($definition->min_start_amount)->stripTrailingZeros();
@endphp

<article {{ $attributes->class('min-w-[280px] sm:min-w-0') }}>
    <div class="relative mb-7 sm:mb-[32px] pt-[18px] w-[356px] max-w-full">
        <h3
            class="absolute right-0 top-0 z-20 text-[#B4FF59] font-dela uppercase font-normal leading-[34px] text-[34px] sm:text-[40px] sm:leading-[40px]">
            {{ Str::upper($definition->name) }}
        </h3>

        <div class="relative w-[356px] max-w-full aspect-[356/208] overflow-hidden rounded-[28px]">
            <img src="{{ $cardImage }}" class="absolute inset-0 h-full w-full object-cover"
                alt="{{ $definition->name }}">

            <div class="relative z-10 flex h-full flex-col justify-between p-5 sm:p-6 text-white">
                <div class="flex items-baseline gap-1">
                    <img src="{{ vite()->icon('/currency/itc-white.svg') }}"
                        class="w-[16px] sm:w-[19px] translate-y-[2px]" alt="ITC">
                    <div>
                        <span class="font-dela text-[30px] sm:text-[36px] leading-none">
                            {{ $minStartAmount }}
                        </span>
                        <p class="text-[12px] text-white/50 leading-none tracking-wide font-bold">
                            {{ __('livewire_index_main_package_deposit') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-end justify-between gap-3 pl-3 sm:pl-5">
                    <img src="{{ vite()->icon('/cards/chip.svg') }}" class="w-7 sm:w-8" alt="chip">
                    <span class="text-white/90 tracking-wide text-[12px] sm:text-[14px]">08/11/25</span>
                    <span class="uppercase text-white/90 tracking-wide text-[12px] sm:text-[14px]">
                        {{ Str::upper($definition->name) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <dl class="space-y-5">
        <div>
            <dt class="text-white font-dela text-[32px] font-normal leading-[40px]">
                {{ $profitPercent }}%
            </dt>
            <dd class="text-white/60 font-medium text-[24px] leading-[40px]">
                {{ __('livewire_index_main_package_profitability') }}
            </dd>
        </div>

        <div>
            <dt class="text-white font-dela text-[32px] font-normal leading-[40px]">
                {{ $minStartAmount }}
            </dt>
            <dd class="text-white/60 font-medium text-[24px] leading-[40px]">
                {{ __('livewire_index_main_package_start') }}
            </dd>
        </div>

        @if ($definition->duration_months !== null && $definition->duration_months > 0)
            <div>
                <dt class="text-white font-dela text-[32px] font-normal leading-[40px]">
                    {{ trans_choice('livewire_index_main_package_months', $definition->duration_months, ['count' => $definition->duration_months]) }}
                </dt>
                <dd class="text-white/60 font-medium text-[24px] leading-[40px]">
                    {{ __('livewire_index_main_package_in_work') }}
                </dd>
            </div>
        @endif
    </dl>
</article>
