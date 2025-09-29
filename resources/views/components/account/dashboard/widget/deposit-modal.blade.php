@php
    $depositAddress = config('wallet.deposit_address');
@endphp

<x-widget.modal condition-name="isDepositModalActive">
    <x-bg.section-slim>
        <div class="flex items-center justify-between">
            <h3 class="text-white text-base">{{ __('components_account_dashboard_widget_deposit_modal_title') }}</h3>
            <figure class="cursor-pointer" x-on:click="isDepositModalActive = false">
                <img class="icon-white w-3" src="{{ vite()->icon('/actions/cancel.svg') }}" alt="">
            </figure>
        </div>
    </x-bg.section-slim>

    <x-bg.section-slim>
        <form wire:submit="createDeposit" x-on:deposit-created.window="isDepositModalActive = false">
            <x-ui.input name="depositForm.amount" placeholder="Например: 100">
                {{ __('components_account_dashboard_widget_deposit_modal_subtitle') }}:
            </x-ui.input>

            <h3 class="text-white text-sm mt-4">{{ __('components_account_dashboard_widget_deposit_modal_qr_subtitle') }}:</h3>

            <figure class="mt-3 w-[200px] mx-auto">
                <img src="{{ route('wallet.qr', $depositAddress) }}" class="w-full h-full" alt="">
            </figure>

            <span class="text-white text-[13px] sm:text-[16px] text-center">{{ $depositAddress }}</span>
            <p class="text-[14px] text-gray-300 text-center">
                {{ __('components_account_dashboard_widget_deposit_modal_net') }}: {{config('wallet.network')}}
            </p>

            <x-ui.input class="mt-2" name="depositForm.transactionHash" placeholder="HASH">
                {{ __('components_account_dashboard_widget_deposit_modal_subtitle_hash') }}:
            </x-ui.input>

            <x-ui.submit-button action="createDeposit" class="w-full mt-4">
                {{ __('components_account_dashboard_widget_deposit_modal_submit') }}
            </x-ui.submit-button>
        </form>
    </x-bg.section-slim>
</x-widget.modal>
