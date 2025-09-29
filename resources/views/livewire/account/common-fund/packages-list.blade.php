<div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach ($packages as $package)
            <div x-data="{ isModalActive: false }">
                <x-bg.main>
                    <x-bg.section>
                        <h3 class="text-white text-center font-bold">ITC
                            @for($i = 0; $i < $package['reinvestCount']; $i++)
                                <span class="text-green">+</span>
                            @endfor
                        </h3>
                    </x-bg.section>
                    <x-bg.section>
                        <x-data.property>
                            <x-slot:name>{{ __('components_account_itc_package_modal_deposit') }}</x-slot:name>
                            <x-slot:value>
                                {{ scaleDecimal($package['amount']) }} ITC
                                @if($package['profitReinvest'] > 0)
                                    <span class="text-green">+ {{
                                        scaleDecimal($package['profitReinvest'])
                                        }} ITC</span>
                                @endif
                            </x-slot:value>
                        </x-data.property>
                        <x-data.property class="mt-2">
                            <x-slot:name>{{ __('components_account_dashboard_widget_deposit_modal_withdraw_amount_to_withdraw') }}</x-slot:name>
                            <x-slot:value>
                                {{ scaleDecimal($package['profit']) }} ITC
                            </x-slot:value>
                        </x-data.property>
                        <form class="mt-6" wire:submit="reinvestProfit('{{ $package['uuid'] }}')">
                            <x-ui.submit-button action="reinvestProfit" :is-disable="true">{{ __('reinvestment_of_profits') }}</x-ui.submit-button>
                        </form>
                        <button x-on:click="isModalActive = true" class="text-white border border-gray-400 rounded-xl mt-3 w-full py-1.5">
                            {{ __('more_details') }}
                        </button>
                    </x-bg.section>
                </x-bg.main>
                <div x-show="isModalActive" x-cloak>
                    <x-account.itc.package-modal :package="$package" />
                </div>
            </div>
        @endforeach
    </div>
</div>
