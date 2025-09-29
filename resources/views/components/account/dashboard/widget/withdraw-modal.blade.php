@php use Illuminate\Support\Carbon; @endphp
<x-widget.modal condition-name="isWithdrawModalActive">
    <x-bg.section-slim>
        <div class="flex items-center justify-between">
            <h3 class="text-white text-base">{{ __('components_account_dashboard_widget_deposit_modal_withdraw_title') }}</h3>
            <figure class="cursor-pointer" x-on:click="isWithdrawModalActive = false">
                <img class="icon-white w-3" src="{{ vite()->icon('/actions/cancel.svg') }}" alt="">
            </figure>
        </div>
    </x-bg.section-slim>
    @if(Carbon::now()->isSunday())
        <x-bg.section-slim>
            <form wire:submit="createWithdraw" x-on:withdraw-created.window="isWithdrawModalActive = false">
                <x-ui.input name="withdrawForm.amount" placeholder="Например: 100">
                    {{ __('components_account_dashboard_widget_deposit_modal_withdraw_amount_label') }}
                </x-ui.input>
                <p x-show="$wire.withdrawForm.amount >= 10" class="text-white px-4 pt-1.5">
                    {{ __('components_account_dashboard_widget_deposit_modal_withdraw_amount_to_withdraw') }}
                    <span x-text="($wire.withdrawForm.amount * 0.98 - 2).toFixed(2)"></span>
                </p>
                <x-ui.input class="mt-4" name="withdrawForm.address" placeholder="WALLET_ADDRESS">
                    {{ __('components_account_dashboard_widget_deposit_modal_withdraw_address_label') }} (USDT {{config('wallet.network')}}):
                </x-ui.input>
                <x-ui.submit-button action="createWithdraw" class="w-full mt-4">
                    {{ __('components_account_dashboard_widget_deposit_modal_withdraw_confirm_button') }}
                </x-ui.submit-button>
            </form>
        </x-bg.section-slim>
    @else
        <x-bg.section-slim>
            <p class="text-white text-base">
                {{ __('components_account_dashboard_widget_deposit_modal_withdraw_sunday_only') }}
            </p>
        </x-bg.section-slim>
    @endif

    <x-bg.section-slim>
        <p class="text-white text-base">
            {{ __('components_account_dashboard_widget_deposit_modal_withdraw_minimum_amount') }}
        </p>
        <div class="text-white text-base">
            {{ __('components_account_dashboard_widget_deposit_modal_withdraw_commission') }}
        </div>
    </x-bg.section-slim>
</x-widget.modal>
