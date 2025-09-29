@props([
    'withButtons' => true,
])

@php use App\Enums\Itc\PackageTypeEnum;use Illuminate\Support\Facades\Log; @endphp

<x-bg.main class="relative border-none bg-none rounded-none">

    <div x-data="{ isModalClosePackageActive: false,
                   isModalEditBalanceActive: false,
                   showConfirmReinvest: false,
                   showConfirmWithdraw: false,
                   showConfirmContinue: false,
                   showConfirmEditBalance: false
                 }" class="flex flex-col md:flex-row gap-6 items-start">

        <x-widget.modal condition-name="isModalClosePackageActive" class="p-4">
            <x-bg.section-slim class="!px-1 !py-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-white font-dela text-[20px]">
                        {{ __('components_account_itc_package_close_package_title') }}
                    </h3>
                </div>
            </x-bg.section-slim>

            <x-bg.section-slim class="!px-1 !py-2">
                <form wire:submit="buyPackage" x-on:bought.window="isModalClosePackageActive = false">
                    <x-ui.submit-button action="#" class="w-full mt-8 bg-[#DA2128] hover:bg-[#ec4249]" disabled>
                        {{ __('components_account_itc_package_confirm') }}
                    </x-ui.submit-button>

                    <x-ui.button action="#" x-on:click="isModalClosePackageActive = false" class="w-full mt-3">
                        {{ __('components_account_itc_package_cancel') }}
                    </x-ui.button>
                </form>
            </x-bg.section-slim>
        </x-widget.modal>

        <x-widget.modal condition-name="showConfirmReinvest">
            <div class="p-6">
                <div class="mb-4 text-lg font-semibold">
                    {{ __('components_account_itc_package_confirm_reinvest_question') }}
                </div>
                <div class="flex gap-2 justify-end">
                    <x-ui.button variant="secondary" @click="showConfirmReinvest = false">
                        {{ __('components_account_itc_package_cancel') }}
                    </x-ui.button>
                    <x-ui.button
                        variant="primary"
                        x-on:click="$wire.profitReinvest('{{ $package->uuid }}'); showConfirmReinvest = false"
                    >
                        {{ __('components_account_itc_package_reinvest_action') }}
                    </x-ui.button>
                </div>
            </div>
        </x-widget.modal>

        <x-widget.modal condition-name="showConfirmWithdraw">
            <div class="p-6">
                <div class="mb-4 text-lg font-semibold">
                    {{ __('components_account_itc_package_confirm_withdraw_question') }}
                </div>
                <div class="flex gap-2 justify-end">
                    <x-ui.button variant="secondary" @click="showConfirmWithdraw = false">
                        {{ __('components_account_itc_package_cancel') }}
                    </x-ui.button>
                    <x-ui.button
                        variant="primary"
                        x-on:click="$wire.withdrawProfit('{{ $package->uuid }}'); showConfirmWithdraw = false"
                    >
                        {{ __('components_account_itc_package_withdraw_action') }}
                    </x-ui.button>
                </div>
            </div>
        </x-widget.modal>

        @if($package->work_to->isPast() && $package->type !== PackageTypeEnum::PRESENT)
            <x-widget.modal condition-name="showConfirmContinue">
                <div class="p-6">
                    <div class="mb-4 text-lg font-semibold">
                        {{ __('components_account_itc_package_restore_profitability_question') }}
                    </div>
                    <div class="flex gap-2 justify-end">
                        <x-ui.button variant="secondary" @click="showConfirmContinue = false">
                            {{ __('components_account_itc_package_cancel') }}
                        </x-ui.button>
                        <x-ui.button
                            variant="primary"
                            x-on:click="$wire.continuePackageWork('{{ $package->uuid }}'); showConfirmContinue = false"
                        >
                            {{ __('components_account_itc_package_continue_action') }}
                        </x-ui.button>
                    </div>
                </div>
            </x-widget.modal>

            <x-widget.modal
                condition-name="isModalEditBalanceActive"
                max-width="md"
                class="p-4 md:min-w-[350px] min-w-[250px]">

                <x-bg.section-slim class="!px-1 !py-2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-white font-dela text-[18px]">
                            {{ __('components_account_itc_package_edit_balance_title') }}
                        </h3>
                        <figure class="cursor-pointer"
                                x-on:click="isModalEditBalanceActive = false">
                            <img class="icon-white w-4"
                                 src="{{ vite()->icon('/actions/cancel.svg') }}"
                                 alt="">
                        </figure>
                    </div>
                </x-bg.section-slim>

                <x-bg.section-slim class="!px-1 !py-2">
                    <form
                        wire:submit="withdrawPackageBalance('{{ $package->uuid }}')"
                        x-on:balance-edited.window="isModalEditBalanceActive = false">

                        <x-ui.input
                            name="withdrawPackageAmount"
                            placeholder="Сумма в ITC"
{{--                            notice="Сумма для вывода на баланс"--}}
                            validate="number"
                            input-class="py-[5px] px-[12px]">
                            {{ __('components_account_itc_package_amount_to_withdraw_to_balance') }}
                        </x-ui.input>

                        <x-ui.submit-button
                            action="withdrawPackageBalance"
                            class="w-full mt-8">
                            {{ __('components_account_itc_package_withdraw_action') }}
                        </x-ui.submit-button>
                    </form>
                </x-bg.section-slim>
            </x-widget.modal>
        @endif

        <div class="w-[356px] h-[208px] rounded-[28px]">
            <img src="{{ vite()->icon('/cards/bg-logo-'. $package->type->value . '.png') }}"
                 class="w-[356px] h-[208px] absolute z-[10]" alt="">
            <div class="relative z-[11] w-[356px] h-[208px]
                        bg-none">
                <div class="relative h-full flex flex-col justify-between p-6 text-white">

                    <div class="grid grid-cols-2 items-baseline gap-x-6 md:gap-x-[50px] gap-y-4">

                        {{-- ► 1‑я строка, 1‑й столбец  ─ депозит --}}
                        <div class="flex items-baseline gap-1">
                            <img src="{{ vite()->icon('/currency/itc-white.svg') }}"
                                 class="w-[19px] translate-y-[2px]" alt="ITC">
                            <div>
                                <span class="text-[30px] md:text-[36px] font-dela leading-none">
                                    {{ $package->type === PackageTypeEnum::PRESENT && $package->zeroing
                                        ? 0
                                        : scale(
                                                $package->transaction->amount
                                                + ($package->partner_transfers_sum_amount ?? 0)
                                                + ($package->reinvest_to_body_sum_amount ?? 0)
                                                - ($package->balance_withdraws_sum_amount ?? 0)
                                            )->stripTrailingZeros()
                                    }}
                                </span>
                                <p class="text-[12px] text-white/50 leading-none tracking-wide">
                                    {{ __('components_account_itc_package_deposit') }}
                                </p>
                            </div>
                        </div>

                        @if ($package->reinvest_profits_sum_amount > 0)
                            <div class="flex flex-col items-baseline gap-1">
                                <span class="text-[16px] md:text-[20px] font-dela leading-none">
                                    +{{ scale($package->reinvest_profits_sum_amount)->stripTrailingZeros() }}
                                </span>
                                <p class="text-[12px] text-white/50 tracking-wide leading-none">
                                    {{ __('components_account_itc_package_reinvested') }}
                                </p>
                            </div>
                        @else
                            <div></div>
                        @endif

                        <div class="flex items-baseline self-baseline">
                            <img src="{{ vite()->icon('/currency/itc-white.svg') }}"
                                 class="w-[19px] invisible translate-y-[2px]" alt="ITC">
                            <div>
                                <p class="text-[16px] md:text-[20px] font-dela leading-none">
                                    {{ scale($package->getCurrentProfitAmount())->stripTrailingZeros() }}
                                </p>
                                <p class="text-[12px] text-white/50 tracking-wide">
                                    {{ __('components_account_itc_package_dividends') }}
                                </p>
                            </div>
                        </div>

                        <div class="self-baseline">
                            <p class="text-[16px] md:text-[20px] font-dela leading-none">
                                {{ scale($package->profits_sum_amount)->stripTrailingZeros() }}
                            </p>
                            <p class="text-[12px] text-white/50 tracking-wide">
                                {{ __('components_account_itc_package_total_dividends') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between px-2 mb-[2px] mt-auto">
                        <img src="{{ vite()->icon('/cards/chip.svg') }}" class="w-8" alt="chip">
                        <span class="uppercase text-white/90 tracking-wide ml-[60px] md:ml-[90px] text-[14px]">
                            {{ $package->work_to?->format('d/m/y') }}
                        </span>
                        <span class="uppercase text-white/90 tracking-wide ml-auto md:mr-0 mr-[20px] text-[14px]">
                            {{ $package->type->getName() }}
                        </span>
                    </div>

                    @if($package->type === PackageTypeEnum::PRESENT && $package->zeroing)
                        <p class="absolute bottom-1 left-6 text-[11px] text-gray-400">
                            {{ __('components_account_itc_package_balance_zeroed') }} {{ $package->zeroing->created_at->format('d.m.Y H:i') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
        @if($withButtons)
            <div class="flex z-[11] flex-col items-start gap-3">

                <x-ui.button
                    :disabled="$package->getCurrentProfitAmount()->isEqualTo('0')"
                    x-on:click="showConfirmReinvest = true"
                    class="!text-[14px] !md:text-[16px]">
                    {{ __('components_account_itc_package_reinvest_dividends') }}
                    <span class="ml-2 text-[14px] md:text-[16px]">({{ scale($package->getCurrentProfitAmount())->stripTrailingZeros()->__toString() }} ITC)</span>
                </x-ui.button>

                <x-ui.button
                    variant="secondary"
                    class="justify-between"
                    :disabled="$package->getCurrentProfitAmount()->isEqualTo('0')"
                    x-on:click="showConfirmWithdraw = true"
                    class="!text-[14px] !md:text-[16px]">
                    {{ __('components_account_itc_package_withdraw_dividends_to_balance') }}
                    <span class="ml-2 text-[14px] md:text-[16px]">({{ scale($package->getCurrentProfitAmount())->stripTrailingZeros()->__toString() }} ITC)</span>
                </x-ui.button>

                @if($package->work_to->isPast() && $package->type !== PackageTypeEnum::PRESENT)
                    <x-ui.button
                        class="!text-[14px] !md:text-[16px]"
                        x-on:click="isModalEditBalanceActive = true">
                        {{ __('components_account_itc_package_edit_balance') }}
                    </x-ui.button>

                    <x-ui.button
                        class="!text-[14px] !md:text-[16px]"
                        x-on:click="showConfirmContinue = true">
                        {{ __('components_account_itc_package_continue_work') }}
                    </x-ui.button>
                @endif


    {{--            <x-ui.button variant="secondary" disabled class="!text-[14px] !md:text-[16px]">--}}
    {{--                Снять доступные реинвесты--}}
    {{--            </x-ui.button>--}}

    {{--            <x-ui.button x-on:click="isModalClosePackageActive = true" class="bg-[#DA2128] hover:bg-[#ec4249] !text-[14px] !md:text-[16px]" variant="danger">--}}
    {{--                Закрыть пакет--}}
    {{--            </x-ui.button>--}}

            </div>
        @endif

    </div>
</x-bg.main>
