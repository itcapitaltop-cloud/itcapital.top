<div class="flex flex-col gap-6 md:flex-row">
    <div class="flex-1">
        <x-bg.main>
            <x-bg.section-slim>
                <h3 class="text-white">{{ __('livewire_my_business_rang') }} <span class="text-2xl">{{ $rank }}</span></h3>
            </x-bg.section-slim>
        </x-bg.main>
        <x-bg.main class="mt-6">
            <x-bg.section-slim>
                <h3 class="text-white text-base px-3">{{ __('livewire_my_business_affiliate_link') }}</h3>
            </x-bg.section-slim>
            <x-bg.section-slim>
                <div x-on:click="navigator.clipboard.writeText('{{ $partnerLink }}')" class="flex cursor-pointer items-center gap-3 rounded-lg hover:bg-gray-450 py-1 px-3">
                    <p class="text-white">{{ $partnerLink }}</p>
                    <figure>
                        <img class="w-4 icon-white" src="{{ vite()->icon('actions/copy.svg') }}" alt="">
                    </figure>
                </div>
            </x-bg.section-slim>
        </x-bg.main>
    </div>

    <x-bg.main class="flex-1">
        <x-bg.section-slim>
            <div class="flex items-center justify-between">
                <h3 class="text-white text-base lg:text-xl">{{ __('livewire_my_business_affiliate') }}</h3>
                <select wire:model.live="line">
                    @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}">{{ __('livewire_my_business_line') }} {{ $i }}</option>
                    @endfor
                </select>
            </div>
        </x-bg.section-slim>

        {{--        <p class="text-gray-300 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">Здесь пока пусто...</p>--}}
        <table class="text-white table-auto w-full mt-4">

            <thead>
                <tr>
                    <th class="font-normal text-left text-gray-300 pl-3">{{ __('livewire_sign_up_first_name_label') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($partners as $partner)
                    <tr class="even:bg-gray-450">
                        <td class="pl-3 py-2">{{ $partner?->username}}</td>
                    </tr>
                @endforeach

            </tbody>
        </table>
    </x-bg.main>
</div>
