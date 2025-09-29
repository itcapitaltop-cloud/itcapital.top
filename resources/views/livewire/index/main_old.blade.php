<div class="w-full bg-black overflow-x-hidden relative">
    <div class="absolute -translate-y-1/2 -translate-x-[740px] rotate-[22deg] opacity-75">
        <div class="w-[980px] h-[980px] bg-gradient-to-l from-blue to-black to-30% rounded-full blur-md"></div>
    </div>
    <x-index.header />
    <livewire:quotes.marquee />

    <section class="w-full h-screen flex items-center top-0 left-0 relative z-10">
        <div class="container">
            <div class="flex flex-col-reverse gap-2 sm:gap-6 items-start lg:flex-row lg:items-center justify-between">
                <div>
                    <h1 class="text-white text-2xl xl:text-6xl font-black xl:w-[700px]">
                        {{ __('livewire_index_main_old_welcome_title') }}
                    </h1>
                    <p class="text-gray-300 mt-4 sm:mt-8 sm:w-[540px] xl:w-[546px]">
                        <span class="text-white">
                            {{ __('livewire_index_main_old_welcome_subtitle') }}
                        </span>, {{ __('livewire_index_main_old_welcome_subtitle_2') }}
                    </p>
                    <a href="{{ route('sign-up') }}"
                       class="py-1.5 px-6 bg-blue mt-6 sm:mt-12 rounded-xl inline-block border border-blue">
                        <p class="text-white font-medium">{{ __('livewire_index_main_cta_button') }}</p>
                    </a>
                </div>
                <div>
                    <div class="flex gap-1">
                        <figure class="animate-candle ease [animation-duration:4s]">
                            <img class="w-8 sm:w-12" src="{{ vite()->img('landing/candles/c1.png') }}" alt="">
                        </figure>
                        <figure>
                            <img class="w-8 sm:w-12 animate-candle-reverse ease  [animation-duration:4s]" src="{{ vite()->img('landing/candles/c2.png') }}" alt="">
                        </figure>
                        <figure>
                            <img class="w-8 sm:w-12 animate-candle ease  [animation-duration:3s]" src="{{ vite()->img('landing/candles/c3.png') }}" alt="">
                        </figure>
                        <figure>
                            <img class="w-8 sm:w-12 animate-candle-reverse ease  [animation-duration:8s]" src="{{ vite()->img('landing/candles/c4.png') }}" alt="">
                        </figure>
                        <figure>
                            <img class="w-8 sm:w-12 animate-candle ease  [animation-duration:4s]" src="{{ vite()->img('landing/candles/c5.png') }}" alt="">
                        </figure>
                        <figure>
                            <img class="w-8 sm:w-12 animate-candle-reverse ease  [animation-duration:6s]" src="{{ vite()->img('landing/candles/c6.png') }}" alt="">
                        </figure>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="relative">
        <div class="absolute translate-x-[750px] -translate-y-[400px] right-0 rotate-180 opacity-75">
            <div class="w-[980px] h-[980px] bg-gradient-to-l from-blue to-black to-30% rounded-full blur-md"></div>
        </div>
        <div class="container relative">
            <x-index.h2>{{ __('livewire_index_main_old_main_activity_title') }}</x-index.h2>
            <p class="text-gray-300 sm:w-[540px] md:w-[700px] xl:w-[900px] mx-auto mt-1.5 text-center">
                <span class="text-white">"IT CAPITAL"</span> {{ __('livewire_index_main_old_main_activity_description') }}
            </p>
            <p class="text-white mt-5 text-center">{{ __('livewire_index_main_old_main_activity_tags') }}</p>
            <div class="mt-12">
                <x-index.perspective-panel class="observer-target"
                    gradient-class="w-[300px] h-[300px] blur-[200px] bg-[#7000FF]">
                    <div class="flex flex-col-reverse">
                        <figure class="absolute bottom-0 right-0">
                            <img src="{{ vite()->img('/landing/candle.png') }}" alt="">
                        </figure>
                        <figure class="opacity-0 lg:hidden">
                            <img src="{{ vite()->img('/landing/candle.png') }}" alt="">
                        </figure>
                        <div class="relative">
                            <div class="flex gap-5 items-center">
                                <img alt="" src="{{ vite()->icon('/landing/dollar.png') }}" class="w-12 h-12" />
                                <p class="text-white">{{ __('livewire_index_main_old_prop_trading_title') }}</p>
                            </div>
                            <p class="mt-6 text-gray-300 sm:w-[380px]">
                                {{ __('livewire_index_main_old_prop_trading_step_1') }}
                            </p>
                            <p class="mt-6 text-gray-300 sm:w-[380px]">
                                {{ __('livewire_index_main_old_prop_trading_step_2') }}
                            </p>
                            <p class="mt-6 text-gray-300 sm:w-[380px] lg:w-[240px] xl:w-[380px]">
                                {{ __('livewire_index_main_old_prop_trading_step_3') }}
                            </p>
                            <p class="mt-6 text-gray-300 sm:w-[380px]">
                                {{ __('livewire_index_main_old_prop_trading_step_4') }}
                            </p>
                        </div>
                    </div>
                </x-index.perspective-panel>
            </div>
            <div class="mt-3">
                <x-index.perspective-panel gradient-class="w-[300px] h-[300px] blur-[200px] bg-[#7000FF]">
                    <div class="flex flex-col-reverse">
                        <figure class="absolute bottom-0 right-0">
                            <img src="{{ vite()->img('/landing/mine.png') }}" alt="">
                        </figure>
                        <figure class="opacity-0 lg:hidden">
                            <img src="{{ vite()->img('/landing/mine.png') }}" alt="">
                        </figure>
                        <div class="relative">
                            <div class="flex gap-5 items-center">
                                <img alt="" src="{{ vite()->icon('/landing/bitcoin.png') }}" class="w-12 h-12" />
                                <p class="text-white">{{ __('livewire_index_main_old_crypto_investments_title') }}</p>
                            </div>
                            <p class="mt-6 text-gray-300 sm:w-[380px]">
                                {{ __('livewire_index_main_old_crypto_step_1') }}
                            </p>
                            <p class="mt-6 text-gray-300 sm:w-[380px]">
                                {{ __('livewire_index_main_old_crypto_step_2') }}
                            </p>
                            <p class="mt-6 text-gray-300 sm:w-[380px] lg:w-[300px] xl:w-[380px]">
                                {{ __('livewire_index_main_old_crypto_step_3') }}
                            </p>
                        </div>
                    </div>
                </x-index.perspective-panel>
            </div>
        </div>
    </section>
    <section class="relative">
        <div class="absolute -translate-x-[750px] opacity-75">
            <div class="w-[980px] h-[980px] bg-gradient-to-l from-blue to-black to-30% rounded-full blur-md"></div>
        </div>
        <div class="container mt-24 xl:mt-56 flex flex-col md:flex-row gap-6 items-start justify-between parent-stick relative">
            <div class="lg:hidden">
                <h2 class="text-white font-black text-3xl lg:text-5xl">{{ __('livewire_index_main_old_strengths_title') }}</h2>
                <p class="mt-6 xl:mt-8 text-gray-300">
                    <span class="text-white">IT CAPITAL</span> {{ __('livewire_index_main_old_strengths_description') }}
                </p>
            </div>
            <div class="hidden w-[400px] must-stick lg:block">
                <h2 class="text-white font-black text-5xl">{{ __('livewire_index_main_old_strengths_title') }}</h2>
                <p class="mt-8 text-gray-300">
                    <span class="text-white">IT CAPITAL</span>
                    {{ __('livewire_index_main_old_strengths_description') }}
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex flex-col gap-3 sm:pt-36 flex-1">
                    <x-index.perspective-panel class="aspect-square md:w-52 lg:w-72 lg:h-72"
                                               gradient-class="w-[250px] h-[250px] blur-[100px] bg-red-600">
                        <div>
                            <div class="w-20 h-20 relative flex items-center justify-center overflow-visible">
                                <img class="w-24 h-24" src="{{ vite()->icon('/advantages/line.png') }}" alt="">
                            </div>

                            <p class="text-gray-300 font-medium">
                                <span class="text-red">{{ __('livewire_index_main_old_advantage_1') }}</span> <br>
                                {{ __('livewire_index_main_old_advantage_1_2') }}
                            </p>
                        </div>
                    </x-index.perspective-panel>
                    <x-index.perspective-panel class="aspect-square lg:w-72 lg:h-72"
                                               gradient-class="w-[250px] h-[250px] blur-[100px] bg-[#37FF4B]">
                        <div>
                            <div class="w-20 h-20 relative flex items-center justify-center overflow-visible">
                                <img class="w-24 h-24" src="{{ vite()->icon('/advantages/code.png') }}" alt="">
                            </div>
                            <p class="text-gray-300 font-medium">
                                Разработка <br> <span class="text-green">собственного ПО</span><br> для торговли
                            </p>
                        </div>
                    </x-index.perspective-panel>
                    <x-index.perspective-panel class="aspect-square lg:w-72 lg:h-72"
                                               gradient-class="w-[250px] h-[250px] blur-[100px] bg-[#5337FF]">
                        <div>
                            <div class="w-20 h-20 relative flex items-center justify-center overflow-visible">
                                <img class="w-24 h-24" src="{{ vite()->icon('/advantages/grow.png') }}" alt="">
                            </div>
                            <p class="text-gray-300 font-medium">
                                {{ __('livewire_index_main_old_advantage_3') }} <span class="text-purple">
                                    {{ __('livewire_index_main_old_advantage_3_2') }}
                                </span> {{ __('livewire_index_main_old_advantage_3_3') }}
                            </p>
                        </div>
                    </x-index.perspective-panel>
                </div>
                <div class="flex flex-col gap-3 flex-1">
                    <x-index.perspective-panel class="aspect-square lg:w-72 lg:h-72"
                                               gradient-class="w-[250px] h-[250px] blur-[100px] bg-[#FFC737]">
                        <div>
                            <div class="w-20 h-20 relative flex items-center justify-center overflow-visible">
                                <img class="w-24 h-24" src="{{ vite()->icon('/advantages/society.png') }}" alt="">
                            </div>
                            <p class="text-gray-300 font-medium">
                                <span class="text-yellow">
                                    {{ __('livewire_index_main_old_advantage_4') }}
                                </span><br>
                                {{ __('livewire_index_main_old_advantage_4_2') }}<br>
                                {{ __('livewire_index_main_old_advantage_4_3') }}
                            </p>
                        </div>
                    </x-index.perspective-panel>
                    <x-index.perspective-panel class="aspect-square lg:w-72 lg:h-72"
                                               gradient-class="w-[250px] h-[250px] blur-[100px] bg-[#37FFF3]">
                        <div>
                            <div class="w-20 h-20 relative flex items-center justify-center overflow-visible">
                                <img class="w-24 h-24" src="{{ vite()->icon('/advantages/study.png') }}" alt="">
                            </div>
                            <p class="text-gray-300 font-medium">
                                {{ __('livewire_index_main_old_advantage_5') }} <span class="text-aqua">
                                    {{ __('livewire_index_main_old_advantage_5_2') }}<br>
                                {{ __('livewire_index_main_old_advantage_5_3') }}</span>
                            </p>
                        </div>
                    </x-index.perspective-panel>
                    <x-index.perspective-panel class="aspect-square lg:w-72 lg:h-72"
                                               gradient-class="w-[250px] h-[250px] blur-[100px] bg-[#FF37D3]">
                        <div>
                            <div class="w-20 h-20 relative flex items-center justify-center overflow-visible">
                                <img class="w-24 h-24" src="{{ vite()->icon('/advantages/check.png') }}" alt="">
                            </div>
                            <p class="text-gray-300 font-medium">
                                {{ __('livewire_index_main_old_advantage_6') }} <span class="text-pink">
                                    {{ __('livewire_index_main_old_advantage_6_2') }}<br>
                                {{ __('livewire_index_main_old_advantage_6_3') }}</span>
                            </p>
                        </div>
                    </x-index.perspective-panel>
                </div>
            </div>
        </div>
    </section>
    <section class="mt-24 xl:mt-56">
        <div class="container">
            <x-index.h2>{{ __('livewire_index_main_old_investment_package_title') }}</x-index.h2>
            <p class="text-white mt-5 text-center">
                {{ __('livewire_index_main_old_investment_package_tags') }}
            </p>
            <div class="grid mt-12 grid-cols-4 gap-3 items-stretch">
                <div class="col-span-4 rounded-xl overflow-hidden h-[400px] md:col-span-3">
                    <img class="w-full h-full object-cover" src="{{ vite()->img('/landing/card.png') }}"
                        alt="">
                </div>
                <x-index.perspective-panel class="col-span-4 sm:col-span-2 md:col-span-1"
                    gradient-class="w-[250px] h-[250px] blur-[100px] bg-blue">
                    <img src="{{ vite()->icon('/landing/itc.png') }}" alt="">
                    <p class="text-white mt-6">
                        {{ __('livewire_index_main_old_package_card_1') }} <span class="whitespace-nowrap">«IT Capital»</span>. {{ __('livewire_index_main_old_package_card_1_2') }}
                    </p>
                </x-index.perspective-panel>
                <x-index.perspective-panel class="col-span-4 sm:col-span-2 md:col-span-1"
                    gradient-class="w-[250px] h-[250px] blur-[100px] bg-blue">
                    <img src="{{ vite()->icon('/landing/percent.png') }}" alt="">
                    <p class="text-white mt-6">
                        {{ __('livewire_index_main_old_package_card_2') }}
                    </p>
                </x-index.perspective-panel>
                <x-index.perspective-panel class="col-span-4 sm:col-span-2 md:col-span-1"
                    gradient-class="w-[250px] h-[250px] blur-[100px] bg-blue">
                    <img src="{{ vite()->icon('/landing/check.png') }}" alt="">
                    <p class="text-white text-xl mt-6">
                        {{ __('livewire_index_main_old_package_card_3') }}
                    </p>
                </x-index.perspective-panel>
                <x-index.perspective-panel class="col-span-4 sm:col-span-2"
                    gradient-class="w-[250px] h-[250px] blur-[100px] bg-blue">
                    <img src="{{ vite()->icon('/landing/time.png') }}" alt="">
                    <p class="text-white mt-6">
                        {{ __('livewire_index_main_old_package_card_4') }}
                    </p>
                    <div class="text-white mt-1">
                        {{ __('livewire_index_main_old_package_card_4_2') }}
                    </div>
                    <p class="text-white mt-1">
                        {{ __('livewire_index_main_old_package_card_4_3') }}
                    </p>
                </x-index.perspective-panel>
            </div>
        </div>
    </section>
    {{--    <section class="mt-56"> --}}
    {{--        <div class="container"> --}}
    {{--            <h2 class="text-white font-black text-5xl text-center">Как мы работаем</h2> --}}
    {{--            <p class="mt-1.5 w-[730px] text-center mx-auto text-gray-300"> --}}
    {{--                Lorem ipsum применяется для того, чтобы показать читателю (пользователю), как будет выглядеть страница при использовании того или иного набора оформительских элементов и шрифтов. --}}
    {{--            </p> --}}
    {{--            <p class="text-white mt-5 text-center"> --}}
    {{--                Пакет 1 / Пакет 2 / Пакет 3 --}}
    {{--            </p> --}}
    {{--            <div class="grid grid-cols-4 gap-3 mt-12"> --}}
    {{--                <x-bg.main> --}}
    {{--                    <x-bg.section> --}}
    {{--                        <div class="w-12 h-12 rounded-xl bg-blue-600"></div> --}}
    {{--                        <h3 class="text-gray-300 font-medium text-xl mt-3">Курс 1 акции ITC <br> = 1 USDT. </h3> --}}
    {{--                        <p class="text-white mt-6"> --}}
    {{--                            Средняя доходность составляет 8,2% в месяц или примерно 100% годовых. --}}
    {{--                        </p> --}}
    {{--                    </x-bg.section> --}}
    {{--                </x-bg.main> --}}
    {{--                <x-bg.main class="col-span-3 flex items-center justify-center"> --}}
    {{--                    <p class="text-white">Слайдер</p> --}}
    {{--                </x-bg.main> --}}
    {{--            </div> --}}
    {{--        </div> --}}
    {{--    </section> --}}
    <section class="mt-24 xl:mt-56 relative z-10">
        <div class="container">
            <x-index.h2>{{ __('livewire_index_main_old_partnership_title') }}</x-index.h2>
            <p class="mt-1.5 lg:w-[900px] xl:w-[820px] text-center mx-auto text-gray-300">
                <span class="text-white">IT CAPITAL</span> {{ __('livewire_index_main_old_partnership_description') }}
            </p>
            <p class="text-white mt-5 text-center">
                {{ __('livewire_index_main_old_partnership_tags') }}
            </p>
            <div class="w-full px-3 overflow-x-scroll lg:overflow-x-auto">
                <table
                    class="rounded-lg bg-gray border-collapse overflow-hidden mt-12 table-auto min-w-[540px] lg:w-full">
                    <thead class="bg-gray-400">
                        <tr>
                            <th class="text-white py-3 px-6 font-medium">{{ __('livewire_index_main_old_partner_rank') }}</th>
                            <th class="text-white font-medium">{{ __('livewire_index_main_old_partner_line_1') }}</th>
                            <th class="text-white font-medium">{{ __('livewire_index_main_old_partner_line_2') }}</th>
                            <th class="text-white font-medium">{{ __('livewire_index_main_old_partner_line_3') }}</th>
                            <th class="text-white font-medium">{{ __('livewire_index_main_old_partner_line_4') }}</th>
                            <th class="text-white font-medium">{{ __('livewire_index_main_old_partner_personal_deposit') }}
                            </th>
                            <th class="text-white font-medium">{{ __('livewire_index_main_old_partner_structure_turnover') }}
                            </th>
                            <th class="text-white font-medium">{{ __('livewire_index_main_old_partner_partners_count') }}
                            </th>
                            <th class="text-white font-medium">{{ __('livewire_index_main_old_partner_bonus') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-gray-300 py-3 px-6 text-center">1</td>
                            <td class="text-gray-300 py-3 px-6 text-center">3%</td>
                            <td class="text-gray-300 py-3 px-6 text-center">-</td>
                            <td class="text-gray-300 py-3 px-6 text-center">-</td>
                            <td class="text-gray-300 py-3 px-6 text-center">-</td>
                            <td class="text-gray-300 py-3 px-6 text-center">$100</td>
                            <td class="text-gray-300 py-3 px-6 text-center">-</td>
                            <td class="text-gray-300 py-3 px-6 text-center">-</td>
                            <td class="text-gray-300 py-3 px-6 text-center">-</td>
                        </tr>
                        <tr>
                            <td class="text-gray-300 py-3 px-6 text-center">2</td>
                            <td class="text-gray-300 py-3 px-6 text-center">4%</td>
                            <td class="text-gray-300 py-3 px-6 text-center">0.5%</td>
                            <td class="text-gray-300 py-3 px-6 text-center">-</td>
                            <td class="text-gray-300 py-3 px-6 text-center">-</td>
                            <td class="text-gray-300 py-3 px-6 text-center">$500</td>
                            <td class="text-gray-300 py-3 px-6 text-center">$1000</td>
                            <td class="text-gray-300 py-3 px-6 text-center whitespace-nowrap">
                                {{ __('livewire_index_main_old_partner_line_5') }}
                            </td>
                            <td class="text-gray-300 py-3 px-6 text-center">$15</td>
                        </tr>
                        <tr>
                            <td class="text-gray-300 py-3 px-6 text-center">3</td>
                            <td class="text-gray-300 py-3 px-6 text-center">5%</td>
                            <td class="text-gray-300 py-3 px-6 text-center">1%</td>
                            <td class="text-gray-300 py-3 px-6 text-center">-</td>
                            <td class="text-gray-300 py-3 px-6 text-center">-</td>
                            <td class="text-gray-300 py-3 px-6 text-center">$1000</td>
                            <td class="text-gray-300 py-3 px-6 text-center">$4000</td>
                            <td class="text-gray-300 py-3 px-6 text-center whitespace-nowrap">
                                {{ __('livewire_index_main_old_partner_line_6') }}
                            </td>
                            <td class="text-gray-300 py-3 px-6 text-center">$40</td>
                        </tr>
                        <tr>
                            <td class="text-gray-300 py-3 px-6 text-center">4</td>
                            <td class="text-gray-300 py-3 px-6 text-center">5.5%</td>
                            <td class="text-gray-300 py-3 px-6 text-center">1.5%</td>
                            <td class="text-gray-300 py-3 px-6 text-center">0.5%</td>
                            <td class="text-gray-300 py-3 px-6 text-center">-</td>
                            <td class="text-gray-300 py-3 px-6 text-center">$2000</td>
                            <td class="text-gray-300 py-3 px-6 text-center">$13500</td>
                            <td class="text-gray-300 py-3 px-6 text-center whitespace-nowrap">
                                {!! __('livewire_index_main_old_partner_line_7') !!}
                            </td>
                            <td class="text-gray-300 py-3 px-6 text-center">$130</td>
                        </tr>
                        <tr>
                            <td class="text-gray-300 py-3 px-6 text-center">5</td>
                            <td class="text-gray-300 py-3 px-6 text-center">6%</td>
                            <td class="text-gray-300 py-3 px-6 text-center">2%</td>
                            <td class="text-gray-300 py-3 px-6 text-center">1%</td>
                            <td class="text-gray-300 py-3 px-6 text-center">0.5%</td>
                            <td class="text-gray-300 py-3 px-6 text-center">$4000</td>
                            <td class="text-gray-300 py-3 px-6 text-center">$25000</td>
                            <td class="text-gray-300 py-3 px-6 text-center whitespace-nowrap">
                                {!! __('livewire_index_main_old_partner_line_8') !!}
                            </td>
                            <td class="text-gray-300 py-3 px-6 text-center">$400</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </section>
    <footer class="mt-24 xl:mt-52 mb-6 relative">
        <div class="absolute w-[980px] -scale-y-100 -rotate-180 h-[600px] translate-y-[24px] overflow-hidden bottom-0 translate-x-[750px] right-0 opacity-75">
            <div class="absolute top-0 left-0 -translate-x-[100px] w-[980px] h-[980px] bg-gradient-to-l from-blue to-black to-30% rounded-full blur-md"></div>
        </div>
        <p class="text-gray-300 text-center">IT-Capital 2024</p>
    </footer>
</div>

@script
    <script>
        Alpine.data('gradientBlock', () => ({
            x: 0,
            y: 0,
            degX: 0,
            degY: 0,
            onMove(e) {
                const halfWidth = e.currentTarget.clientWidth / 2
                const halfHeight = e.currentTarget.clientHeight / 2
                const rect = e.currentTarget.getBoundingClientRect()

                this.x = e.clientX - rect.left;
                this.y = e.clientY - rect.top;

                this.degY = ((halfWidth - this.x) / halfWidth) * 2;
                this.degX = ((halfHeight - this.y) / halfHeight) * (-1);
            },
            onLeave() {
                this.degX = 0;
                this.degY = 0;
            }
        }));


        const translate = {
            y: 0
        }

        let lastScrollY = 0

        const parentStick = document.querySelector('.parent-stick')

        const onScroll = () => {
            translate.y += window.scrollY - lastScrollY
            parentStick.querySelector('.must-stick').style.transform = `translateY(${translate.y}px)`
            lastScrollY = window.scrollY

            if (translate.y < 0) {
                translate.y = 0
                window.removeEventListener('scroll', onScroll)
            }

            if (translate.y + parentStick.querySelector('.must-stick').clientHeight > parentStick.clientHeight) {
                translate.y = parentStick.clientHeight - parentStick.querySelector('.must-stick').clientHeight
                window.removeEventListener('scroll', onScroll)
            }
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    lastScrollY = window.scrollY
                    window.addEventListener('scroll', onScroll)
                }
            });
        }, {
            rootMargin: "0px",
            threshold: 0.7,
        })


        observer.observe(parentStick)
    </script>
@endscript
