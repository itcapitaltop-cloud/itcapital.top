@php
    use Illuminate\Support\Facades\Log;

@endphp
<div class="grid gap-3">
    <x-account.dashboard.widget.balance />
    <x-bg.main>
        <x-bg.section-slim>
            <div class="flex items-center justify-between">
                <h3 class="text-white text-xl">{{ __('livewire_dashboard_index_old_portfolio_title') }}</h3>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-gray-300 text-sm">{{ __('livewire_dashboard_index_old_packages_count_label') }}</h3>
                    <h1 class="text-base text-white">{{ $packagesCount }} {{ __('livewire_dashboard_index_old_total_count_label') }}</h1>
                </div>
                <div>
                    <h3 class="text-gray-300 text-sm">{{ __('livewire_dashboard_index_old_total_amount_label') }}</h3>
                    <h1 class="text-base text-white">{{ scale($depositTotalAmount) }} ITC</h1>
                </div>
            </div>
        </x-bg.section-slim>
    </x-bg.main>
    <x-bg.main class="h-[200px] relative sm:col-span-2 lg:h-[300px]">
        <x-bg.section-slim>
            <h3 class="text-white text-base lg:text-xl"> {{ __('livewire_dashboard_index_old_my_business_title') }}</h3>
        </x-bg.section-slim>
        <x-bg.section-slim>
            <div x-on:click="navigator.clipboard.writeText('{{ $partnerLink }}')"
                 class="flex cursor-pointer items-center gap-3 rounded-lg hover:bg-gray-450 py-1 px-3">
                <p class="text-white">{{ $partnerLink }}</p>
                <figure>
                    <img class="w-4 icon-white" src="{{ vite()->icon('actions/copy.svg') }}" alt="">
                </figure>
            </div>
        </x-bg.section-slim>
        {{--        <p class="text-gray-300 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">Здесь пока пусто...</p>--}}
        <table class="text-white table-auto w-full mt-4">

            <thead>
            <tr>
                <th class="font-normal text-left text-gray-300 pl-3">{{ __('livewire_dashboard_index_old_partner_name_header') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($partners as $partner)
                <tr class="even:bg-gray-450">
                    <td class="pl-3 py-2">{{ $partner->username}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-bg.main>
    <div class="h-[300px] relative overflow-hidden rounded-xl sm:col-span-2 lg:col-span-4 lg:h-[400px]">
        <x-bg.main class="w-full h-full absolute flex flex-col gap-4">
            <x-bg.section-slim>
                <div class="flex items-center justify-between">
                    <h3 class="text-white text-xl">{{ __('livewire_dashboard_index_old_transactions_title') }}</h3>
                </div>
            </x-bg.section-slim>
            <div class="flex-1 w-full h-full relative overflow-y-scroll">
                @if ($transactions->isEmpty())
                    <p class="text-gray-300 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                        {{ __('livewire_dashboard_index_old_transactions_empty') }}
                    </p>
                @else
                    <table class="text-white table-auto w-full">
                        <thead>
                        <tr>
                            <th class="font-normal text-left text-gray-300 pl-3">{{ __('livewire_dashboard_index_old_transactions_amount_header') }}</th>
                            <th class="font-normal text-left text-gray-300">{{ __('livewire_dashboard_index_old_transactions_type_header') }}</th>
                            <th class="font-normal text-left text-gray-300">{{ __('livewire_dashboard_index_old_transactions_status_header') }}</th>
                            <th class="font-normal text-left text-gray-300">{{ __('livewire_dashboard_index_old_transactions_date_header') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($transactions as $transaction)
                            <tr class="even:bg-gray-450">
                                <td class="pl-3 py-2">{{ scale($transaction->amount) }} ITC</td>
                                <td>{{ $transaction->trx_type->getName() }}</td>
                                <td>{{ $transaction->getStatus()->getName() }}</td>
                                <td>{{ $transaction->created_at->format('d.m.Y') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </x-bg.main>
    </div>
</div>
