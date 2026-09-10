@props([
    'withButtons' => true,
    'mainBalance' => 1000,
])

@php
    use App\Enums\Itc\PackageTypeEnum;
    use App\Services\Package\PackageBodyBalanceResolver;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Storage;

    $displayName = $package->packageDefinition?->name ?? $package->type->getName();
    $cardImagePath = $package->packageDefinition?->card_image_path;

    if ($cardImagePath === null) {
        Log::debug('[account.itc.package] fallback to type image', [
            'package_id' => $package->id,
            'package_uuid' => $package->uuid,
            'package_type' => $package->type->value,
            'package_definition_id' => $package->package_definition_id,
        ]);
    }

    // Считаем из уже загруженных агрегатов, а не через PackageBodyBalanceResolver:
    // вызов резолвера на карточку дал бы отдельный запрос на каждый пакет в списке.
    // Формула совпадает с PackageBodyBalanceResolver::availableToUnlock().
    $pendingBodyUnlocks = (float) ($package->pending_body_unlocks_sum_amount ?? 0);

    $packageBody =
        (float) $package->transaction->amount +
        (float) ($package->partner_transfers_sum_amount ?? 0) +
        (float) ($package->reinvest_to_body_sum_amount ?? 0) -
        (float) ($package->balance_withdraws_sum_amount ?? 0);

    $availableBodyToUnlock = max($packageBody - $pendingBodyUnlocks, 0);

    // Список типов берём из резолвера, а не дублируем здесь: иначе новый разрешённый
    // тип пакета появился бы на сервере, но кнопка на карточке молча не отрисовалась бы.
    $canUnlockBody =
        in_array($package->type, PackageBodyBalanceResolver::UNLOCKABLE_TYPES, true) &&
        $availableBodyToUnlock > 0;
@endphp

<x-bg.main class="relative border-none bg-none rounded-none">

    <div x-data="{
        isModalClosePackageActive: false,
        isModalEditBalanceActive: false,
        isTopUpNeeded: false,
        showConfirmReinvest: false,
        showConfirmWithdraw: false,
        showConfirmContinue: false,
<<<<<<< Updated upstream
        showConfirmEditBalance: false
=======
        showConfirmEditBalance: false,
        showConfirmUnlockReinvests: false,
        isModalUnlockBodyActive: false
>>>>>>> Stashed changes
    }" class="flex flex-col md:flex-row gap-[40px] md:items-center items-start">

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
                    <x-ui.button variant="primary"
                        x-on:click="$wire.profitReinvest('{{ $package->uuid }}'); showConfirmReinvest = false">
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
                    <x-ui.button variant="primary"
                        x-on:click="$wire.withdrawProfit('{{ $package->uuid }}'); showConfirmWithdraw = false">
                        {{ __('components_account_itc_package_withdraw_action') }}
                    </x-ui.button>
                </div>
            </div>
        </x-widget.modal>

<<<<<<< Updated upstream
=======
        <x-widget.modal condition-name="showConfirmUnlockReinvests">
            <div class="p-6">
                <div class="mb-4 text-lg font-semibold">
                    {{ __('components_account_itc_package_confirm_unlock_reinvests_question') }}
                </div>
                <div class="mb-4 text-sm font-semibold max-w-[300px] text-white/70">
                    {{ __('components_account_itc_package_confirm_unlock_reinvests_note', [
                        'date' => now()->addMonthNoOverflow()->format('d.m.Y'),
                    ]) }}
                </div>
                <div class="flex gap-2 justify-end">
                    <x-ui.button variant="secondary" @click="showConfirmUnlockReinvests = false">
                        {{ __('components_account_itc_package_cancel') }}
                    </x-ui.button>
                    <x-ui.button variant="primary"
                        x-on:click="$wire.unlockMaturedReinvests('{{ $package->uuid }}'); showConfirmUnlockReinvests = false">
                        {{ __('components_account_itc_package_confirm') }}
                    </x-ui.button>
                </div>
            </div>
        </x-widget.modal>

        @if ($withButtons && $canUnlockBody)
            <x-widget.modal condition-name="isModalUnlockBodyActive" max-width="md"
                class="p-4 md:min-w-[175px] min-w-[125px] md:max-w-[300px]">

                <x-bg.section-slim class="!px-1 !py-2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-white font-dela text-[18px]">
                            {{ __('components_account_itc_package_unlock_body_title') }}
                        </h3>
                        <figure class="cursor-pointer" x-on:click="isModalUnlockBodyActive = false">
                            <img class="icon-white w-4" src="{{ vite()->icon('/actions/cancel.svg') }}" alt="">
                        </figure>
                    </div>
                </x-bg.section-slim>

                <x-bg.section-slim class="!px-1 !py-2">
                    <p class="mb-4 text-sm text-white/70">
                        {{ __('components_account_itc_package_unlock_body_available', [
                            'amount' => scale($availableBodyToUnlock)->stripTrailingZeros()->__toString(),
                        ]) }}
                    </p>

                    <form wire:submit="unlockPackageBodyAmount('{{ $package->uuid }}')"
                        x-on:balance-edited.window="isModalUnlockBodyActive = false">

                        <x-ui.input name="unlockBodyAmount"
                            placeholder="{{ __('components_account_itc_package_unlock_body_amount_label') }}"
                            validate="number" input-class="py-[5px] px-[12px]">
                            {{ __('components_account_itc_package_unlock_body_amount_label') }}
                        </x-ui.input>

                        <p class="mt-4 text-sm text-white/70 max-w-[260px]">
                            {{ __('components_account_itc_package_unlock_body_note', [
                                'date' => now()->addMonthNoOverflow()->format('d.m.Y'),
                            ]) }}
                        </p>

                        <x-ui.submit-button action="unlockPackageBodyAmount" class="w-full mt-8">
                            {{ __('components_account_itc_package_confirm') }}
                        </x-ui.submit-button>
                    </form>
                </x-bg.section-slim>
            </x-widget.modal>
        @endif

>>>>>>> Stashed changes
        @if ($package->work_to->isPast() && $package->type !== PackageTypeEnum::PRESENT)
            <x-widget.modal condition-name="showConfirmContinue">
                <div class="p-6">
                    <div class="mb-4 text-lg font-semibold">
                        {{ __('components_account_itc_package_restore_profitability_question') }}
                    </div>
                    <div class="mb-4 text-sm font-semibold w-[300px]">
                        {{ __('components_account_itc_package_restore_profitability_question_desc') }}
                    </div>
                    <div class="flex gap-2 justify-end">
                        <x-ui.button variant="secondary" @click="showConfirmContinue = false">
                            {{ __('components_account_itc_package_cancel') }}
                        </x-ui.button>
                        <x-ui.button variant="primary"
                            x-on:click="$wire.continuePackageWork('{{ $package->uuid }}'); showConfirmContinue = false">
                            {{ __('components_account_itc_package_continue_action') }}
                        </x-ui.button>
                    </div>
                </div>
            </x-widget.modal>

            <x-widget.modal condition-name="isModalEditBalanceActive" max-width="md"
                class="p-4 md:min-w-[350px] min-w-[250px]">

                <x-bg.section-slim class="!px-1 !py-2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-white font-dela text-[18px]">
                            {{ __('components_account_itc_package_edit_balance_title') }}
                        </h3>
                        <figure class="cursor-pointer" x-on:click="isModalEditBalanceActive = false">
                            <img class="icon-white w-4" src="{{ vite()->icon('/actions/cancel.svg') }}" alt="">
                        </figure>
                    </div>
                </x-bg.section-slim>

                <x-bg.section-slim class="!px-1 !py-2">
                    <form wire:submit="withdrawPackageBalance('{{ $package->uuid }}')"
                        x-on:balance-edited.window="isModalEditBalanceActive = false">

                        <x-ui.input name="withdrawPackageAmount" placeholder="Сумма в ITC" validate="number"
                            input-class="py-[5px] px-[12px]">
                            {{ __('components_account_itc_package_amount_to_withdraw_to_balance') }}
                        </x-ui.input>

                        <x-ui.submit-button action="withdrawPackageBalance" class="w-full mt-8">
                            {{ __('components_account_itc_package_withdraw_action') }}
                        </x-ui.submit-button>
                    </form>
                </x-bg.section-slim>
            </x-widget.modal>
        @endif

        <x-widget.modal condition-name="isTopUpNeeded" max-width="md" class="p-4 md:min-w-[350px] min-w-[250px]">

            <x-bg.section-slim class="!px-1 !py-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-white font-dela text-[18px]">
                        {{ __('components_account_itc_package_withdraw_dividends_top_up_need') }}
                    </h3>

                    <figure class="cursor-pointer" x-on:click="isTopUpNeeded = false">
                        <img class="icon-white w-4" src="{{ vite()->icon('/actions/cancel.svg') }}" alt="">
                    </figure>
                </div>
            </x-bg.section-slim>

            <x-bg.section-slim class="!px-1 !py-2">
                <div class="flex flex-col sm:flex-row sm:items-center mb-6 md:mb-[32px] gap-2 sm:gap-0">
                    <p class="sm:mr-[102px] block text-sm md:text-base">
                        {{ __('components_account_dashboard_widget_balance_main_balance') }}
                    </p>
                    <p class="flex gap-2 items-center">
                        <img src="{{ vite()->icon('currency/itc.svg') }}" class="w-[12px]" alt="">
                        <span class="text-sm md:text-base">{{ number_format($mainBalance, 2, '.', '') }}</span>
                    </p>
                </div>

                <form wire:submit="topUpNeeded('{{ $package->uuid }}')"
                    x-on:balance-edited.window="isTopUpNeeded = false">

                    <x-ui.input name="withdrawPackageAmount"
                        placeholder="{{ __('components_account_itc_package_amount_placeholder') }}" validate="number"
                        input-class="py-[5px] px-[12px]">
                        {{ __('components_account_dashboard_widget_deposit_modal_title') }}
                    </x-ui.input>

                    <x-ui.submit-button action="topUpNeeded" class="w-full mt-8">
                        {{ __('components_account_itc_package_confirm') }}
                    </x-ui.submit-button>
                </form>
            </x-bg.section-slim>
        </x-widget.modal>

        <div class="w-[356px] h-[208px] rounded-[28px]">
            <img src="{{ $cardImagePath ? Storage::disk('public')->url($cardImagePath) : vite()->icon('/cards/bg-logo-' . $package->type->value . '.png') }}"
                class="w-[356px] h-[208px] absolute z-[10] object-cover rounded-[28px]" alt="">
            <div class="relative z-[11] w-[356px] h-[208px]
                        bg-none">
                <div class="relative h-full flex flex-col justify-between p-6 text-white">

                    <div class="flex flex-col items-baseline gap-x-6 md:gap-x-[50px] gap-y-4">

                        {{-- ► 1‑я строка, 1‑й столбец  ─ депозит --}}
                        <div class="flex items-baseline gap-1">
                            <img src="{{ vite()->icon('/currency/itc-white.svg') }}" class="w-[19px] translate-y-[2px]"
                                alt="ITC">
                            <div>
                                <span class="text-[30px] md:text-[36px] font-dela leading-none">
                                    {{-- Ожидающие выплаты разблокировки показываются отдельной строкой
                                         под карточкой, поэтому из депозита их нужно вычесть,
                                         иначе одни и те же деньги видны дважды. --}}
                                    {{ $package->type === PackageTypeEnum::PRESENT && $package->zeroing
                                        ? 0
                                        : scale($packageBody - $pendingBodyUnlocks)->stripTrailingZeros() }}
                                </span>
                                <p class="text-[12px] text-white/50 leading-none tracking-wide font-bold">
                                    {{ __('components_account_itc_package_deposit') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-baseline self-baseline mb-4 md:pl-3">
                            @if ($package->reinvest_profits_sum_amount > 0)
                                <div class="flex flex-col items-baseline gap-1">
                                    <span class="text-[16px] md:text-[20px] font-dela leading-none">
                                        +{{ scale($package->reinvest_profits_sum_amount)->stripTrailingZeros() }}
                                    </span>
                                    <p
                                        class="text-[12px] text-white/50 tracking-wide leading-none font-bold md:pl-2 block">
                                        {{ __('components_account_itc_package_reinvested') }}
                                    </p>
                                </div>
                            @else
                                <div></div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 sm:gap-5 md:pl-5">
                        <div class="flex flex-col gap-1 sm:gap-2">
                            <span
                                class="uppercase text-white tracking-wide text-[8px] sm:text-[10px] font-normal opacity-50 text-nowrap">
                                {{ $package->uuid }}
                            </span>
                            <img src="{{ vite()->icon('/cards/chip.svg') }}" class="w-6 sm:w-8" alt="chip">
                        </div>
                        <div class="flex flex-col gap-2 sm:gap-3">
                            <span class="text-white tracking-wide text-[8px] sm:text-[10px] font-normal opacity-50">
                                {{ __('package_open') }}
                            </span>
                            <span class="uppercase text-white/90 tracking-wide text-[12px] sm:text-[14px]">
                                {{ $package->created_at->format('d/m/Y') }}
                            </span>

                        </div>

                        <div class="flex flex-col gap-1 sm:gap-2">
                            <span class="text-white text-right tracking-wide text-[8px] sm:text-[14px] font-normal">
                                {{ $package->month_profit_percent }}%
                            </span>
                            <span class="uppercase text-white/90 tracking-wide text-[14px]">
                                {{ $displayName }}
                            </span>
                        </div>
                    </div>

                    @if ($package->type === PackageTypeEnum::PRESENT && $package->zeroing)
                        <p class="absolute bottom-1 left-6 text-[11px] text-gray-400">
                            {{ __('components_account_itc_package_balance_zeroed') }}
                            {{ $package->zeroing->created_at->format('d.m.Y H:i') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="hidden lg:block flex-shrink-0"
            style="width: 1px; height: 174px; background-color: rgba(255, 255, 255, 0.3);"></div>

        @if ($withButtons)
            <div class="flex-none w-full max-w-[348px] z-[11] flex flex-col items-start gap-3">

                <div class="w-full mb-2">
                    <div class="flex items-baseline justify-between">
                        <div class="flex flex-col gap-3">
                            <p class="text-white font-semibold text-[10px] md:text-base">
                                {{ __('total_dividends_received') }}
                            </p>
                            <p class="text-white font-semibold text-[10px] md:text-base">
                                {{ __('available_dividends') }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-3">
                            <p class="flex items-baseline gap-2 min-w-[64px]">
                                <img src="{{ vite()->icon('currency/itc.svg') }}" class="w-[12px] align-baseline"
                                    alt="">
                                <span class="text-white font-extrabold text-[10px] md:text-base">
                                    {{ scale($package->profits_sum_amount)->stripTrailingZeros() }}
                                </span>
                            </p>
                            <p class="flex items-baseline gap-2 min-w-[64px]">
                                <img src="{{ vite()->icon('currency/itc.svg') }}" class="w-[12px] align-baseline"
                                    alt="">
                                <span class="text-white font-extrabold text-[10px] md:text-base">
                                    {{ $package->getCurrentProfitAmount()->isNegative() ? '0' : scale($package->getCurrentProfitAmount())->stripTrailingZeros() }}
                                </span>
                            </p>
                        </div>


                    </div>
                </div>

                <x-ui.button :disabled="$package->getCurrentProfitAmount()->isNegativeOrZero()" x-on:click="showConfirmReinvest = true"
                    class="!text-[14px] !md:text-[16px]">
                    <span class="text-[14px] md:text-[16px]">
                        {{ __('components_account_itc_package_reinvest_action') }}
                        {{ $package->getCurrentProfitAmount()->isNegative() ? '0' : scale($package->getCurrentProfitAmount())->stripTrailingZeros()->__toString() }}
                        ITC</span>
                </x-ui.button>

                <x-ui.button variant="secondary" class="justify-between" :disabled="$package->getCurrentProfitAmount()->isNegativeOrZero()"
                    x-on:click="showConfirmWithdraw = true" class="!text-[14px] !md:text-[16px]">
                    <span class="flex gap-4 items-center text-[14px] md:text-[16px]">
                        {{ __('components_account_itc_package_withdraw_dividends_to_balance', [
                            'amount' => $package->getCurrentProfitAmount()->isNegative()
                                ? '0'
                                : scale($package->getCurrentProfitAmount())->stripTrailingZeros()->__toString(),
                        ]) }}
                        <svg viewBox="0 0 12.1094 12.1094" xmlns="http://www.w3.org/2000/svg"
                            xmlns:xlink="http://www.w3.org/1999/xlink" width="12.109375" height="12.109375"
                            fill="none" customFrame="#000000">
                            <path id="Vector"
                                d="M11.3592 6.64256L11.3595 0.75L5.46729 0.75M0.75293 11.3566L11.3595 0.75"
                                stroke="rgb(255,255,255)" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="1.500000" />
                        </svg>

                    </span>
                </x-ui.button>

                @if ($package->type !== PackageTypeEnum::PRESENT)
                    <x-ui.button @click="isTopUpNeeded = true" variant="primary"
                        class="!text-[14px] !md:text-[16px]">
                        <span class="text-[14px] md:text-[16px]">
                            {{ __('add_itc_to_package_button') }}
                        </span>
                    </x-ui.button>
                @endif

<<<<<<< Updated upstream
=======
                @if (($package->unlockable_reinvest_profits_count ?? 0) > 0)
                    <x-ui.button variant="secondary" x-on:click="showConfirmUnlockReinvests = true"
                        class="!text-[14px] !md:text-[16px]">
                        <span class="text-[14px] md:text-[16px]">
                            {{ __('components_account_itc_package_unlock_reinvests_action', [
                                'amount' => scale(
                                    $package->unlockable_reinvest_profits_sum_amount ?? 0,
                                )->stripTrailingZeros()->__toString(),
                            ]) }}
                        </span>
                    </x-ui.button>
                @endif

                @if ($canUnlockBody)
                    <x-ui.button variant="secondary" x-on:click="isModalUnlockBodyActive = true"
                        class="!text-[14px] !md:text-[16px]">
                        <span class="text-[14px] md:text-[16px]">
                            {{ __('components_account_itc_package_unlock_body_action') }}
                        </span>
                    </x-ui.button>
                @endif

>>>>>>> Stashed changes
            </div>
        @endif

        <div class="block lg:hidden flex-shrink-0"
            style="width: 100%; height: 1px; background-color: rgba(255, 255, 255, 0.3);"></div>
    </div>
<<<<<<< Updated upstream
=======

    @if (($package->unlocked_reinvest_profits_sum_amount ?? 0) > 0)
        <div class="flex flex-wrap items-baseline gap-2 mt-3 md:pl-5">
            <p class="text-[12px] text-white/50 leading-none tracking-wide font-bold">
                {{ __('components_account_itc_package_unlocked_reinvests_label') }}
            </p>
            <span class="text-white/90 text-[12px] md:text-[14px] tracking-wide">
                {{ __('components_account_itc_package_unlocked_reinvests_payout_at', [
                    'amount' => scale($package->unlocked_reinvest_profits_sum_amount)->stripTrailingZeros()->__toString(),
                    'date' => ($package->unlocked_reinvest_profits_min_payout_at ?? null)
                        ? \Carbon\Carbon::parse($package->unlocked_reinvest_profits_min_payout_at)->format('d.m.Y')
                        : '—',
                ]) }}
            </span>
        </div>
    @endif

    {{-- Пока ничего не разблокировано, строка не рендерится вовсе. --}}
    @if ($pendingBodyUnlocks > 0)
        <div class="flex flex-wrap items-baseline gap-2 mt-3 md:pl-5">
            <p class="text-[12px] text-white/50 leading-none tracking-wide font-bold">
                {{ __('components_account_itc_package_unlocked_body_label') }}
            </p>
            <span class="text-white/90 text-[12px] md:text-[14px] tracking-wide">
                {{ __('components_account_itc_package_unlocked_body_payout_at', [
                    'amount' => scale($pendingBodyUnlocks)->stripTrailingZeros()->__toString(),
                    'date' => ($package->pending_body_unlocks_min_payout_at ?? null)
                        ? \Carbon\Carbon::parse($package->pending_body_unlocks_min_payout_at)->format('d.m.Y')
                        : '—',
                ]) }}
            </span>
        </div>
    @endif
>>>>>>> Stashed changes
</x-bg.main>
