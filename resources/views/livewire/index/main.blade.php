<div class="w-full overflow-x-hidden relative" x-data="{ openMenu: false }">
    <x-index.header />

    <livewire:quotes.marquee class="mt-[95px]"/>

    <section class="relative w-full min-h-[420px] sm:min-h-[440px] pt-[52px] pb-[22px] sm:pb-[22px] lg:pb-[22px] overflow-hidden">
        <div class="container">
            {{-- Дублирующий лейбл и заголовок для мобильной версии --}}
            <div class="mb-3 block md:hidden">
                <div class="flex items-center gap-2">
                    <span class="relative flex items-center gap-2 px-3 pb-[4px] pt-[3px] rounded-[6px] bg-[#22223E] text-[12px] text-[#B4FF59] font-medium select-none">
                        {{ __('livewire_index_main_hero_label') }}
                        <span class="inline-block w-3 h-3 rounded-full bg-[#B4FF59] shadow-[0_0_12px_2px_#B4FF59] animate-pulse"></span>
                    </span>
                </div>
                <h1 class="text-white font-dela text-[20px] xs:text-[28px] sm:text-[32px] leading-none mt-2">
                    {{ __('livewire_index_main_hero_title') }}
                </h1>
            </div>
            <div class="flex items-start lg:items-center justify-between">
                {{-- Левая часть: контент --}}
                <div class="relative z-10 flex-1 flex flex-col gap-6">
                    {{-- Лейбл с точкой --}}
                    <div class="hidden md:flex items-center gap-2">
                        <span class="relative flex items-center gap-2 px-3 pb-[4px] pt-[3px] rounded-[6px] bg-[#22223E] text-[12px] text-[#B4FF59] font-medium select-none">
                            {{ __('livewire_index_main_hero_label') }}
                            <span class="inline-block w-3 h-3 rounded-full bg-[#B4FF59] shadow-[0_0_12px_2px_#B4FF59] animate-pulse"></span>
                        </span>
                    </div>
                    {{-- Заголовок --}}
                    <h1 class="text-white font-dela text-[20px] xs:text-[28px] sm:text-[32px] md:text-[36px] lg:text-[44px] xl:text-[40px] leading-none hidden md:block">
                        {{ __('livewire_index_main_hero_title') }}
                    </h1>
                    {{-- Описание --}}
                    <span class="text-[14px] sm:text-[16px] leading-tight text-[#BDBDBD] flex flex-col gap-3 sm:block">
                        <span>
                            {{ __('livewire_index_main_hero_description_1') }}
                        </span>
                        <span>
                            {{ __('livewire_index_main_hero_description_2') }}
                            <span class="text-white underline underline-offset-2 decoration-[#FFFFFF]">
                                {{ __('livewire_index_main_hero_description_2_1') }}
                            </span>
                             {{ __('livewire_index_main_hero_description_2_2') }}
                        </span>
                    </span>
                    {{-- Кнопка + рейтинг --}}
                    <div class="mt-6 flex flex-col gap-5 w-fit hidden sm:flex">
                        <div class="relative group">
                            <a href="{{ route('login') }}"
                               class="relative flex items-center gap-3 px-4 py-4 text-[16px] sm:px-[34px] sm:py-[22.5px] sm:text-[20px] rounded-[8px] bg-[#B4FF59] text-black font-medium leading-[19px] hover:shadow-[0_0_16px_0_#B4FF59] hover:bg-[#C5FF80] focus:ring-2 focus:ring-[#B4FF59] transition-shadow duration-300 ease-in overflow-hidden z-10"
                            >
                                {{-- SVG-блик (как картинка), под текстом --}}
                                <span class="pointer-events-none absolute left-0 top-0 w-full h-full z-0 flex items-center">
                                    <svg width="66" height="64" fill="none" xmlns="http://www.w3.org/2000/svg" class="animate-shine-sweep will-change-transform" viewBox="0 0 66 64">
                                        <path d="M65.4562 0H34.4714L0.815552 64H35.0057L65.4562 0Z" fill="url(#paint0_linear_201_30)"/>
                                        <defs>
                                            <linearGradient id="paint0_linear_201_30" x1="18.979" y1="32" x2="47.1687" y2="45.3862" gradientUnits="userSpaceOnUse">
                                                <stop offset="0" stop-color="#B4FF59"/>
                                                <stop offset="0.5" stop-color="#FFFFFF"/>
                                                <stop offset="1" stop-color="#B4FF59"/>
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                </span>
                                <span class="relative text-[#17162D] z-10">{{ __('livewire_index_main_cta_button') }}</span>
                            </a>
                        </div>
                        {{-- Рейтинг --}}
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-[4px] text-[#FFD600] text-[22px]">
                                    @for($i=0; $i<5; $i++)
                                        <img src="{{ vite()->icon('/main/star.svg') }}" alt="" />
                                    @endfor
                                </div>
                                <span class="text-[#BDBDBD] text-[12px] font-medium ml-1">4.9</span>
                            </div>
                            <div class="text-[#BDBDBD] text-[12px]">
                                {{ __('livewire_index_main_rating_reviews_count') }}
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Правая часть: стрелка и кометы --}}
                <div class="relative mr-[-20px] sm:mr-0 flex-1 flex min-h-[178px] max-h-[178px] min-w-[204px] max-w-[204px] sm:max-w-[240px] md:max-w-[300px] lg:max-w-[414px] xl:max-w-[414px] sm:max-h-[210px] md:max-h-[262px] lg:min-h-[362px] xl:min-h-[362px] lg:max-h-[362px] xl:max-h-[362px] items-center justify-center z-0">
                    {{-- Основная зелёная стрелка --}}
                    <img src="{{ vite()->icon('/main/arrow-hero.svg') }}" alt="arrow" class="relative z-10 select-none pointer-events-none max-w-[133px] md:max-w-none top-[-15px] md:top-0" />
                    <div id="comets-block" class="absolute inset-0 pointer-events-none select-none z-0 overflow-visible"></div>
                </div>
            </div>
            {{-- Кнопка + рейтинг --}}
            <div class="mt-[10px] flex flex-col gap-5 sm:hidden w-fit">
                <div class="relative group">
                    <a href="{{ route('login') }}"
                       class="relative flex items-center gap-3 px-4 py-4 text-[16px] sm:px-[34px] sm:py-[22.5px] sm:text-[20px] rounded-[8px] bg-[#B4FF59] text-black font-medium leading-[19px] hover:bg-[#C5FF80] hover:shadow-[0_0_16px_0_#B4FF59] focus:ring-2 focus:ring-[#B4FF59] transition-shadow duration-300 ease-in overflow-hidden z-10"
                    >
                        {{-- SVG-блик (как картинка), под текстом --}}
                        <span class="pointer-events-none absolute left-0 top-0 w-full h-full z-0 flex items-center">
                            <svg width="66" height="64" fill="none" xmlns="http://www.w3.org/2000/svg" class="animate-shine-sweep will-change-transform" viewBox="0 0 66 64">
                                <path d="M65.4562 0H34.4714L0.815552 64H35.0057L65.4562 0Z" fill="url(#paint0_linear_201_31)"/>
                                <defs>
                                    <linearGradient id="paint0_linear_201_31" x1="18.979" y1="32" x2="47.1687" y2="45.3862" gradientUnits="userSpaceOnUse">
                                        <stop offset="0" stop-color="#B4FF59"/>
                                        <stop offset="0.5" stop-color="#FFFFFF"/>
                                        <stop offset="1" stop-color="#B4FF59"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </span>
                        <span class="relative text-[#17162D] z-10">{{ __('livewire_index_main_cta_button') }}</span>
                    </a>
                </div>
                {{-- Рейтинг --}}
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-[4px] text-[#FFD600] text-[22px]">
                            @for($i=0; $i<5; $i++)
                                <img src="{{ vite()->icon('/main/star.svg') }}" alt="" />
                            @endfor
                        </div>
                        <span class="text-[#BDBDBD] text-[12px] font-medium ml-1">4.9</span>
                    </div>
                    <div class="text-[#BDBDBD] text-[12px]">{{ __('livewire_index_main_rating_reviews_count') }}</div>
                </div>
            </div>
        </div>
    </section>
    <section class="relative w-full min-h-[200px] py-[22px] lg:py-[22px] overflow-visible">
        <div class="container overflow-visible">
            <div class="flex flex-col lg:flex-col items-start justify-between overflow-visible">
                {{-- Левая часть: текст --}}
                <div class="flex items-center justify-between overflow-visible gap-[50px] sm:gap-[112px] pb-[20px]">
                    <div class="w-full max-w-[clamp(320px,80vw,747px)] xl:max-w-none">
                        <h2 class="text-white font-dela text-[20px] sm:text-[24px] xs:text-[28px] md:text-[32px] sm:leading-none leading-tight mb-6">
                            {{ __('livewire_index_main_club_title') }}
                        </h2>
                        <div class="text-[#BDBDBD] text-[16px] mb-6 leading-tight hidden lg:block">
                            {{ __('livewire_index_main_club_description_1') }}
                        </div>
                        <div class="text-[#BDBDBD] text-[16px] leading-tight hidden lg:block">
                            {{ __('livewire_index_main_club_description_2') }}
                        </div>
                    </div>

                    {{-- Правая часть: карточки --}}
                    <div x-data="clubCard" class="relative cursor-pointer flex justify-center items-center mt-[10px] lg:mt-[100px] overflow-visible"
                         @click="window.location.href = '{{ route('login') }}'"
                    >
                        <img
                            src="{{ vite()->icon('/main/glow-cards.svg') }}"
                            class="absolute bottom-[-100px] pointer-events-none select-none z-8 max-w-none hidden lg:block"
                            alt="glow"
                        />
                        <div class=
                                 "
                                 relative
                                 min-w-[156px] max-w-[156px] min-h-[110px] max-h-[110px] mr-[10px]
                                 sm:min-w-[194px] sm:max-w-[194px] sm:min-h-[138px] sm:max-h-[138px] md:mr-[15px]
                                 md:min-w-[233px] md:max-w-[233px] md:min-h-[165px] md:max-h-[165px] md:mr-[20px]
                                 lg:min-w-[271px] lg:max-w-[271px] lg:min-h-[193px] lg:max-h-[193px] lg:mr-[20px]
                                 xl:min-w-[309px] xl:max-w-[309px] xl:min-h-[220px] xl:max-h-[220px]
                                 group select-none">
                            {{-- Задняя (зелёная) карточка --}}
                            <img
                                src="{{ vite()->icon('/main/card-back.svg') }}"
                                class="absolute
                                      -left-[1px] min-w-[167px] -top-[21px]
                                       sm:-left-[2px] sm:min-w-[210px] sm:-top-[27px]
                                       md:-left-[3px] md:min-w-[252px] md:-top-[33px]
                                       lg:-left-[3px] lg:min-w-[292px] lg:-top-[39px]
                                       xl:-left-[4px] xl:-top-[42px]
                                       transition-all duration-500 z-9 origin-top-left -rotate-[3deg] group-hover:-rotate-[13deg]"
                                alt="back-side-card"
                            />
                            {{-- Передняя (жёлтая) карточка --}}
                            <div class="absolute left-0 top-0 z-10">
                                <template x-if="loggedIn && user.first && user.last">
                                    <img
                                        src="{{ vite()->icon('/main/card-golden-logged.svg') }}"
                                        class="w-full h-full pointer-events-none select-none backdrop-blur-lg rounded-[13px]"
                                        alt="front-side-card-logged"
                                    />
                                </template>
                                <template x-if="!loggedIn || !user.first || !user.last">
                                    <img
                                        src="{{ vite()->icon('/main/card-golden.svg') }}"
                                        class="w-full h-full pointer-events-none select-none backdrop-blur-lg rounded-[13px]"
                                        alt="front-side-card"
                                    />
                                </template>
                                <template x-if="loggedIn">
                                    <div class="absolute left-[10px] bottom-[10px] lg:left-[18px] lg:bottom-[19px] w-full flex font-ocr">
                                        <span class="inline-block rounded-[3px] lg:rounded-[6px] bg-[#FDE917] px-[3px] py-[1px] lg:px-[6px] lg:py-[2px] text-[7px] lg:text-[10px] tracking-wider">
                                            <template x-if="user.first && user.last">
                                                <span class="text-[#B26E00]" x-text="$store.helpers.translit(user.first) + ' ' + $store.helpers.translit(user.last)"></span>
                                            </template>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-[#BDBDBD] text-[14px] md:text-[16px] mb-6 leading-tight lg:hidden">
                    {{ __('livewire_index_main_club_description_1') }}
                </div>
                <div class="text-[#BDBDBD] text-[14px] md:text-[16px] leading-tight lg:hidden pb-[20px]">
                    {{ __('livewire_index_main_club_description_2') }}
                </div>
                <div class="flex flex-col lg:flex-row gap-7">
                    {{-- Первая карточка --}}
                    <x-index.perspective-panel class="flex flex-col flex-1 rounded-[18px]" gradient-class="w-[200px] h-[200px] blur-[200px] bg-[#7000FF]">
                        <div class="relative bg-[#211F41] rounded-[18px] border-[2px] border-[#32247d] sm:px-[24px] sm:py-[16px] px-[16px] py-[16px]
                            shadow-lg flex flex-col flex-1
                            text-white pointer-events-none"
                        >
                            {{-- Фоновый SVG --}}
                            <img src="{{ vite()->icon('/main/prop-bg.svg') }}"
                                 class="absolute right-0 bottom-0 pointer-events-none select-none opacity-[0.4] z-0"
                                 alt="bg"
                            />
                            <h3 class="text-[16px] sm:text-[20px] font-extrabold mb-6 relative z-10">
                                {{ __('livewire_index_main_prop_trading_title') }}
                            </h3>
                            <ol class="space-y-4 relative z-10 pb-[20px] max-w-none lg:max-w-[90%]  text-[14px] sm:text-[16px]">
                                <li class="flex items-start gap-3">
                                    <span class="text-[16px] sm:text-[20px] font-black mt-[-2px]">1.</span>
                                    <span>
                                        {{ __('livewire_index_main_prop_trading_step_1') }}
                                    </span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-[16px] sm:text-[20px] font-black mt-[-2px]">2.</span>
                                    <span>
                                        {{ __('livewire_index_main_prop_trading_step_2') }}
                                    </span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-[16px] sm:text-[20px] font-black mt-[-2px]">3.</span>
                                    <span>
                                        {{ __('livewire_index_main_prop_trading_step_3') }}
                                    </span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class=" text-[16px] sm:text-[20px] font-black mt-[-2px]">4.</span>
                                    <span>
                                        {{ __('livewire_index_main_prop_trading_step_4') }}
                                    </span>
                                </li>
                            </ol>
                            {{-- Иконка снизу справа --}}
                            <img src="{{ vite()->icon('/main/verified-check.svg') }}"
                                 class="absolute sm:right-6 right-[16px] w-[24px] sm:w-auto sm:bottom-5 sm:top-unset top-[14px] z-10"
                                 alt="check"
                            />
                        </div>
                    </x-index.perspective-panel>
                    {{-- Вторая карточка --}}
                    <x-index.perspective-panel class="flex flex-col flex-1 rounded-[18px]" gradient-class="w-[200px] h-[200px] blur-[200px] bg-[#7000FF]">
                    <div class="relative bg-[#211F41] rounded-[18px] border-[2px] border-[#32247d] sm:px-[24px] sm:py-[16px] px-[16px] py-[16px]
                        shadow-lg flex flex-col flex-1
                        text-white pointer-events-none"
                    >
                        {{-- Фоновый SVG --}}
                        <img src="{{ vite()->icon('/main/crypto-bg.svg') }}"
                             class="absolute right-0 bottom-0 pointer-events-none select-none opacity-[0.4] z-0"
                             alt="bg"
                        />
                        <h3 class="text-[16px] sm:text-[20px] font-extrabold mb-6 relative z-10">
                            {{ __('livewire_index_main_crypto_investments_title') }}
                        </h3>
                        <ol class="space-y-4 max-w-none lg:max-w-[90%] relative pb-[20px] z-10 text-[14px] sm:text-[16px]">
                            <li class="flex items-start gap-3">
                                <span class="text-[16px] sm:text-[20px] font-black mt-[-2px]">1.</span>
                                <span>
                                    {{ __('livewire_index_main_crypto_step_1') }}
                                </span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-[16px] sm:text-[20px] font-black mt-[-2px]">2.</span>
                                <span>
                                    {{ __('livewire_index_main_crypto_step_2') }}
                                </span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-[16px] sm:text-[20px] font-black mt-[-2px]">3.</span>
                                <span>
                                    {{ __('livewire_index_main_crypto_step_3') }}
                                </span>
                            </li>
                        </ol>
                        {{-- Иконка снизу справа --}}
                        <img src="{{ vite()->icon('/main/verified-check.svg') }}"
                             class="absolute sm:right-6 right-[16px] w-[24px] sm:w-auto sm:bottom-5 sm:top-unset top-[14px] z-10"
                             alt="check"
                        />
                    </div>
                    </x-index.perspective-panel>
                </div>
            </div>
        </div>
    </section>
    <section id="club" class="relative w-full py-[28px] overflow-visible">
        <div class="container">
            <div class="relative mx-auto">
                <!-- Полупрозрачная карточка -->
                <h2 class="text-white font-extrabold sm:text-[20px] text-[16px] md:text-[20px] mb-7 relative z-10">
                    {{ __('livewire_index_main_benefits_title') }}
                </h2>
                <div class="relative rounded-[22px] overflow-hidden bg-[#211F41]">
                    <img src="{{ vite()->icon('/main/card-bg.svg') }}"
                         class="absolute right-0 bottom-0 z-0 pointer-events-none select-none opacity-[0.4]"
                         alt="bg"
                    />
                    <ol class="relative z-10 text-[18px] md:text-[20px]">
                        <li class="sm:px-[20px] sm:py-[20px] px-[16px] py-[16px] rounded-t-[22px] border-[2px] border-[#32247d]">
                            <div class="flex items-center gap-[10px] sm:gap-[10px]">
                                <img src="{{ vite()->icon('/main/coin.png') }}"
                                     class="h-[64px]"
                                     alt="coin"
                                />
                                <div class="flex flex-col">
                                    <span class="font-extrabold text-white sm:text-[20px] text-[16px] leading-tight pb-[4px] lg:pb-[1px]">
                                        {{ __('livewire_index_main_benefit_1_title') }}
                                    </span>
                                    <span class="text-[#887AA4] font-semibold text-[14px] sm:text-[16px] leading-tight">
                                        {{ __('livewire_index_main_benefit_1_desc') }}
                                    </span>
                                </div>
                            </div>
                        </li>
                        <li class="sm:px-[20px] sm:py-[20px] px-[16px] py-[16px] border-[2px] [border-image:linear-gradient(180deg,#2E1D78,#433F8E)_1]">
                            <div class="flex items-center gap-[10px] sm:gap-[10px]">
                                <img src="{{ vite()->icon('/main/key.png') }}"
                                     class="h-[64px]"
                                     alt="coin"
                                />
                                <div class="flex flex-col">
                                    <span class="font-extrabold text-white sm:text-[20px] text-[16px] leading-tight pb-[4px] lg:pb-[1px]">
                                        {{ __('livewire_index_main_benefit_2_title') }}
                                    </span>
                                    <span class="text-[#887AA4] text-[14px] sm:text-[16px] leading-tight">
                                        {{ __('livewire_index_main_benefit_2_desc') }}
                                    </span>
                                </div>
                            </div>
                        </li>
                        <li class="sm:px-[20px] sm:py-[20px] px-[16px] py-[16px] border-[2px] [border-image:linear-gradient(180deg,#2E1D78,#433F8E)_1]">
                            <div class="flex items-center gap-[10px] sm:gap-[10px]">
                                <img src="{{ vite()->icon('/main/book.png') }}"
                                     class="h-[64px]"
                                     alt="coin"
                                />
                                <div class="flex flex-col">
                                    <span class="font-extrabold text-white sm:text-[20px] text-[16px] leading-tight pb-[4px] lg:pb-[1px]">
                                        {{ __('livewire_index_main_benefit_3_title') }}
                                    </span>
                                    <span class="text-[#887AA4] text-[14px] sm:text-[16px] leading-tight">
                                        {{ __('livewire_index_main_benefit_3_desc') }}
                                    </span>
                                </div>
                            </div>
                        </li>
                        <li class="sm:px-[20px] sm:py-[20px] px-[16px] py-[16px] border-[2px] [border-image:linear-gradient(180deg,#2E1D78,#433F8E)_1]">
                            <div class="flex items-center gap-[10px] sm:gap-[10px]">
                                <img src="{{ vite()->icon('/main/book-statue.png') }}"
                                     class="h-[64px]"
                                     alt="coin"
                                />
                                <div class="flex flex-col">
                                    <span class="font-extrabold text-white sm:text-[20px] text-[16px] leading-tight pb-[4px] lg:pb-[1px]">
                                        {{ __('livewire_index_main_benefit_4_title') }}
                                    </span>
                                    <span class="text-[#887AA4] text-[14px] sm:text-[16px] leading-tight">
                                        {{ __('livewire_index_main_benefit_4_desc') }}
                                    </span>
                                </div>
                            </div>
                        </li>
                        <li class="sm:px-[20px] sm:py-[20px] px-[16px] py-[16px] rounded-b-[22px] border-[2px] border-[#32247d]">
                            <div class="flex items-center gap-[10px] sm:gap-[10px]">
                                <img src="{{ vite()->icon('/main/cup.png') }}"
                                     class="h-[64px]"
                                     alt="coin"
                                />
                                <div class="flex flex-col">
                                    <span class="font-extrabold text-white sm:text-[20px] text-[16px] pb-[4px] lg:pb-[2px]">
                                        {{ __('livewire_index_main_benefit_5_title') }}
                                    </span>
                                    <span class="text-[#887AA4] text-[14px] sm:text-[16px] leading-tight">
                                        {{ __('livewire_index_main_benefit_5_desc') }}
                                    </span>
                                </div>
                            </div>
                        </li>
                    </ol>
                </div>
                <!-- Кнопка с бликом -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mt-8 pr-2">
                    <span class="text-[#BDBDBD] text-[16px] max-w-[736px] leading-tight mb-[20px] lg:mb-0">
                        {{ __('livewire_index_main_join_cta_text') }}
                    </span>

                    <a href="{{ route('login') }}" class="relative flex items-center px-[16px] py-[16px] sm:px-[32px] sm:py-[20px] rounded-[8px] bg-[#B4FF59] text-black font-semibold text-[18px] transition-shadow duration-300 ease-in hover:bg-[#C5FF80] hover:shadow-[0_0_16px_0_#B4FF59] focus:ring-2 focus:ring-[#B4FF59] group overflow-hidden">
                        {{-- SVG-блик (как картинка), под текстом --}}
                        <span class="pointer-events-none absolute left-0 top-0 w-full h-full z-0 flex items-center">
                            <svg width="66" height="64" fill="none" xmlns="http://www.w3.org/2000/svg" class="animate-shine-sweep will-change-transform" viewBox="0 0 66 64">
                                <path d="M65.4562 0H34.4714L0.815552 64H35.0057L65.4562 0Z" fill="url(#paint0_linear_201_32)"/>
                                <defs>
                                    <linearGradient id="paint0_linear_201_32" x1="18.979" y1="32" x2="47.1687" y2="45.3862" gradientUnits="userSpaceOnUse">
                                        <stop offset="0" stop-color="#B4FF59"/>
                                        <stop offset="0.5" stop-color="#FFFFFF"/>
                                        <stop offset="1" stop-color="#B4FF59"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </span>
                        <span class="relative z-10 ">{{ __('livewire_index_main_join_button') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <section id="profit" class="relative w-full py-[25px] lg:py-[50px] overflow-visible">
        <div class="container">
            <div class="relative mx-auto">
                <!-- Заголовок и описание -->
                <h2 class="text-white font-dela text-[20px] sm:text-[32px] md:text-[32px] mb-8 leading-none">
                    {{ __('livewire_index_main_passive_income_title') }}
                </h2>
                <div class="text-[#BDBDBD] text-[16px] max-w-[860px] mb-8 leading-tight">
                    {{ __('livewire_index_main_passive_income_intro') }}
                </div>

                <div class="flex flex-col gap-7">
                    <!-- Верхняя большая карточка -->
                    <div class="relative min-h-[369px] lg:min-h-0 rounded-[22px] border-[2px] border-[#3b3571] bg-[radial-gradient(ellipse_50%_100%_at_90%_90%,_#4D2A88_0%,_#232148_40%,_#211F41_100%)] px-4 lg:py-[20px] lg:px-8 py-[20px] lg:pr-[260px] min-h-[200px] mb-2 overflow-hidden">
                        <!-- SVG-иконка справа -->
                        <img src="{{ vite()->icon('/main/income-gen.svg') }}"
                             class="absolute left-[-26px] bottom-[-13px] lg:left-unset lg:right-[-12px] lg:top-[-2px] z-0 pointer-events-none select-none"
                             alt="income"
                        />
                        <!-- Контент -->
                        <div class="relative z-10 lg:max-w-[60%] leading-tight">
                            <div class="text-white text-[16px] lg:text-[20px] mb-[20px] font-extrabold">
                                {{ __('livewire_index_main_income_generator_title') }}
                            </div>
                            <div class="text-[#887AA4] text-[16px] mb-[12px] font-semibold leading-tight">
                                {{ __('livewire_index_main_income_generator_desc') }}
                            </div>
                            <div class="mb-[12px] text-[16px] flex flex-col">
                                <span class="text-white underline underline-offset-[3px] font-extrabold pb-[4px]">
                                    {{ __('livewire_index_main_income_generator_profitability') }}
                                </span>
                                <span class="text-[#887AA4] font-semibold">
                                    {{ __('livewire_index_main_income_generator_yield') }}
                                </span>
                            </div>
                            <div class="mb-[43px] text-[16px] flex flex-col">
                                <span class="underline text-white font-extrabold pb-[6px]">
                                    {{ __('livewire_index_main_income_generator_full_automatic') }}
                                </span>
                                <span class="text-[#887AA4] font-semibold leading-none">
                                    {!! __('livewire_index_main_income_generator_auto') !!}
                                </span>
                            </div>
                        </div>
                        <div class="absolute right-[16px] bottom-[14px] lg:right-[32px] lg:bottom-[24px] flex flex-col items-center z-10">
                            <div class="flex flex-col items-end">
                                <span class="text-white text-[24px] lg:text-[24px] font-dela leading-none">$100</span>
                                <span class="text-[#887AA4] text-[12px] lg:text-[16px] mb-[8px] lg:mb-3 font-semibold">
                                    {{ __('livewire_index_main_income_start_sum') }}
                                </span>
                                <a href="{{ route('login') }}" class="relative flex items-center justify-center bg-[#B4FF59] text-black rounded-[8px] font-semibold text-[14px] lg:text-[16px] px-4 py-[12px] transition-shadow duration-300 ease-in hover:bg-[#C5FF80] group overflow-hidden">
                                    <span class="relative z-10">
                                        {{ __('livewire_index_main_income_generator_button') }}
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-7">
                        <!-- Доверительное управление -->
                        <div class="relative min-h-[441px] lg:min-h-0 flex-1 rounded-[22px] border-[2px] border-[#3b3571] bg-[radial-gradient(ellipse_100%_100%_at_85%_85%,_#4D2A88_0%,_#232148_40%,_#211F41_100%)] px-4 lg:py-[20px] lg:px-8 py-[20px] min-h-[200px] overflow-hidden">
                            <img src="{{ vite()->icon('/main/trust-manage.svg') }}"
                                 class="absolute left-[-26px] bottom-[-13px] lg:left-unset lg:right-[-12px] lg:top-[-2px] z-0 pointer-events-none select-none"
                                 alt="trust"
                            />
                            <div class="relative z-10 leading-tight max-w-[414px]">
                                <div class="text-white text-[16px] lg:text-[20px] mb-[20px] font-extrabold leading-none">
                                    {!! __('livewire_index_main_trust_management_title') !!}
                                </div>
                                <div class="text-[#887AA4] text-[16px] mb-[12px] font-semibold leading-tight">
                                    {{ __('livewire_index_main_trust_management_desc') }}
                                </div>
                                <div class="flex flex-col text-[16px] mb-2 lg:max-w-[65%]">
                                    <span class="text-white underline underline-offset-[4px] font-extrabold pb-[6px]">
                                        {{ __('livewire_index_main_trust_management_full_control') }}
                                    </span>
                                    <span class="text-[#887AA4] font-semibold leading-none">
                                        {{ __('livewire_index_main_trust_management_full_control_desc') }}
                                    </span>
                                </div>
                                <div class="flex flex-col text-[16px] mb-4 lg:max-w-[65%]">
                                    <span class="text-white underline underline-offset-[4px] font-extrabold pb-[6px]">
                                        {{ __('livewire_index_main_trust_management_individual_approach')  }}
                                    </span>
                                    <span class="text-[#887AA4] font-semibold leading-none">
                                        {{ __('livewire_index_main_trust_management_individual_approach_desc') }}
                                    </span>
                                </div>
                            </div>
                            <div class="absolute right-[16px] bottom-[14px] lg:right-[32px] lg:bottom-[24px] flex flex-col items-center z-10">
                                <div class="flex flex-col items-end">
                                    <span class="text-white text-[24px] lg:text-[24px] font-dela leading-none">$30,000</span>
                                    <span class="text-[#887AA4] text-[12px] lg:text-[16px] mb-[8px] lg:mb-3 font-semibold">
                                        {{ __('livewire_index_main_income_start_sum') }}
                                    </span>
                                    <button class="relative flex items-center justify-center bg-[#B4FF59] text-black rounded-[8px] font-semibold text-[14px] lg:text-[16px] px-4 py-[12px] transition-shadow duration-300 ease-in hover:bg-[#C5FF80] group overflow-hidden">
                                        <span class="relative z-10">
                                            {{ __('livewire_index_main_income_generator_button') }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Мультивектор -->
                        <div class="relative min-h-[441px] lg:min-h-0 flex-1 rounded-[22px] border-[2px] border-[#3b3571] bg-[radial-gradient(ellipse_100%_100%_at_85%_85%,_#4D2A88_0%,_#232148_40%,_#211F41_100%)] px-4 lg:py-[20px] lg:px-8 py-[20px] min-h-[200px] overflow-hidden">
                            <img src="{{ vite()->icon('/main/portfolio.svg') }}"
                                 class="absolute left-[-26px] bottom-[-13px] lg:left-unset lg:right-[-12px] lg:top-[-2px] z-0 pointer-events-none select-none"
                                 alt="portfolio"
                            />
                            <div class="relative z-10">
                                <div class="text-white text-[16px] lg:text-[20px] mb-[20px] font-extrabold leading-none">
                                    {!! __('livewire_index_main_multivector_title') !!}
                                </div>
                                <div class="text-[#887AA4] text-[16px] mb-[12px] font-semibold leading-tight">
                                    {{ __('livewire_index_main_multivector_desc') }}
                                </div>
                                <div class="flex flex-col mb-2 lg:max-w-[65%]">
                                    <span class="text-white underline font-extrabold pb-[2px]">
                                        {{ __('livewire_index_main_multivector_flexibility_label') }}
                                    </span>
                                    <span class="text-[#887AA4] font-semibold leading-none">{{ __('livewire_index_main_multivector_flexibility') }}</span>
                                </div>
                                <div class="flex flex-col mb-4 lg:max-w-[65%]">
                                    <span class="text-white underline underline-offset-[4px] font-extrabold pb-[6px]">
                                        {{ __('livewire_index_main_multivector_label') }}
                                    </span>
                                    <span class="text-[#887AA4] font-semibold leading-none">
                                        {{ __('livewire_index_main_multivector_conditions') }}
                                    </span>
                                </div>
                            </div>
                            <div class="absolute right-[16px] bottom-[14px] lg:right-[32px] lg:bottom-[24px] flex flex-col items-center z-10">
                                <div class="flex flex-col items-end">
                                    <span class="text-white text-[24px] lg:text-[24px] font-dela leading-none">$50,000</span>
                                    <span class="text-[#887AA4] text-[12px] lg:text-[16px] mb-[8px] lg:mb-3 font-semibold">
                                        {{ __('livewire_index_main_income_start_sum') }}
                                    </span>
                                    <button class="relative flex items-center justify-center bg-[#B4FF59] text-black rounded-[8px] font-semibold text-[14px] lg:text-[16px] px-4 py-[12px] transition-shadow duration-300 ease-in hover:bg-[#C5FF80] group overflow-hidden">
                                        <span class="relative z-10">
                                            {{ __('livewire_index_main_income_generator_button') }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="academy" class="relative w-full py-[25px] lg:py-[50px] overflow-visible">
        <div class="container">
            <div class="relative mx-auto flex flex-col lg:flex-col gap-10">

                <!-- Левая часть: Текст + Заголовок -->
                <div class="flex-1 min-w-0">
                    <div class="flex lg:flex-row flex-col justify-between">
                        <div class="flex flex-col mb-[20px] lg:mb-0">
                            <img src="{{ vite()->icon('/main/logo-academy.svg') }}"
                                 class="left-0 top-0 z-0 w-[152px] h-[61px] lg:w-[330px] lg:h-[131px] pointer-events-none select-none object-contain mb-[20px] lg:mb-[40px]"
                                 alt=""
                            />
                            <h2 class="text-white font-dela text-[20px] lg:text-[32px] mb-[5px] lg:mb-4 leading-none">
                                {{ __('livewire_index_main_academy_title') }}
                            </h2>
                            <div class="text-white font-extrabold text-[16px] lg:text-[20px] mb-[20px] lg:mb-8 leading-tight">
                                {{ __('livewire_index_main_academy_subtitle') }}
                            </div>
                            <div class="text-[#BDBDBD] text-[16px] lg:mb-10 max-w-[612px] leading-tight">
                                {{ __('livewire_index_main_academy_intro') }}
                            </div>
                        </div>
                        <div class="relative lg:absolute lg:right-0 lg:top-0 flex-1 max-h-[309px] lg:max-h-none lg:w-[516px] lg:h-[382px]">
                            <img src="{{ vite()->icon('/main/certificate.svg') }}"
                                 class="lg:absolute opacity-10 lg:right-0 lg:top-0 flex-1 lg:w-[516px] lg:h-[382px] z-0 pointer-events-none select-none object-contain"
                                 alt=""
                            />
                            <img src="{{ vite()->icon('/main/big-book-statue.png') }}"
                                 class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 flex-1 w-[250px] h-[250px] lg:w-[319px] lg:h-[319px] z-1 pointer-events-none select-none object-contain"
                                 alt=""
                            />
                        </div>
{{--                        <div class="relative flex flex-col justify-between flex-1 max-w-[358px] max-h-[265px] lg:max-w-[516px] lg:h-[382px] rounded-[8px] border-[2px] border-[#B4FF59] bg-[#151624] overflow-hidden shadow-xl bg-cover bg-center" style="background-image: url('{{ vite()->icon('/main/bg-certificate.png') }}');">--}}
{{--                            <!-- Лого слева сверху -->--}}
{{--                            <img src="{{ vite()->icon('/main/logo-academy.svg') }}" alt="" class="absolute left-[24px] top-[20px] h-[36px] z-10">--}}

{{--                            <!-- Текстовые элементы -->--}}
{{--                            <div class="flex flex-col text-white items-center pt-[40px] px-[30px]">--}}
{{--                                <div class="text-[20px] mt-2 font-dela text-[#B4FF59] text-center">IT Capital Академия</div>--}}
{{--                                <div class="mt-1 text-[20px] font-dela text-center">Сертификат об окончании курса</div>--}}
{{--                                <div class="text-[12px] font-dela mb-8 text-center">Индивидуальное обучение торговле</div>--}}
{{--                                <div class="mt-2 text-[12px] font-dela text-center opacity-80">выдан</div>--}}
{{--                                <!-- Полоса для имени -->--}}
{{--                                <div class="w-[300px] mb-6 border-b border-[#FFFFFF] h-[32px]"></div>--}}
{{--                            </div>--}}

{{--                            <!-- Подпись и дата -->--}}
{{--                            <div class="mb-[20px] flex flex-col items-center z-10">--}}
{{--                                <img src="{{ vite()->icon('/main/sign-certificate.svg') }}" alt="" class="w-[100px]">--}}
{{--                                <div class="mt-2 text-[12px] text-white font-dela">25 мая 2025 года</div>--}}
{{--                            </div>--}}

{{--                            <!-- Медалька -->--}}
{{--                            <img src="{{ vite()->icon('/main/medal-certificate.png') }}" alt="" class="absolute right-0 bottom-0 w-[135px] z-10">--}}
{{--                        </div>--}}
                    </div>
                    <!-- Карточки курсов -->
                    <div class="flex flex-col mt-[20px] lg:mt-0 gap-7">
                        <!-- Курс 1 -->
                        <div class="relative min-h-[531px] lg:min-h-0 rounded-[22px] border-[2px] border-[#33267E] bg-[radial-gradient(ellipse_75%_100%_at_90%_90%,_#4D2A88_0%,_#232148_50%,_#211F41_100%)] pt-[20px] lg:pt-[30px] px-4 lg:px-[34px] lg:px-8 py-8 overflow-hidden">
                            <img src="{{ vite()->icon('/main/verified-check.svg') }}"
                                 class="absolute left-[16px] bottom-[16px] top-unset lg:left-unset lg:right-6 lg:top-5 z-11"
                                 alt="check"
                            />
                            <img src="{{ vite()->icon('/main/bg-crypto.svg') }}"
                                 class="absolute max-w-[280px] lg:max-w-none right-0 bottom-0 z-0 pointer-events-none select-none object-contain opacity-10"
                                 alt=""
                            />
                            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center">
                                <div class="flex text-[16px] flex-col lg:max-w-[60%] min-w-0 gap-2">
                                    <div class="text-white text-[16px] lg:text-[20px] mb-[12px] font-extrabold leading-none">
                                        {{ __('livewire_index_main_course_1_title') }}
                                    </div>
                                    <div>
                                        <div class="text-white underline underline-offset-[4px] font-extrabold pb-[4px]">
                                            {{ __('availability') }}</div>
                                        <div class="text-[#887AA4] font-semibold leading-tight">
                                            {{ __('livewire_index_main_course_1_accessibility') }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-white underline underline-offset-[4px] font-extrabold pb-[4px]">
                                            {{ __('livewire_index_main_practical_benefit') }}
                                        </div>
                                        <div class="text-[#887AA4] font-semibold leading-tight">
                                            {{ __('livewire_index_main_course_1_practicality') }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-white underline underline-offset-[3px] font-extrabold pb-[4px]">
                                            {{ __('livewire_index_main_practical_convenience') }}
                                        </div>
                                        <div class="text-[#887AA4] font-semibold leading-tight">
                                            {{ __('livewire_index_main_course_1_convenience') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="absolute right-[14px] bottom-[14px] lg:right-[32px] lg:bottom-[32px] flex flex-col items-end">
                                <div class="py-4 text-right text-[12px] lg:text-[16px]">
                                    <div class="text-white font-extrabold mb-1">
                                            {{ __('livewire_index_main_practical_duration') }}
                                        <span class="underline underline-offset-[4px]">
                                            {{ __('livewire_index_main_practical_duration_week') }}
                                        </span>
                                    </div>
                                    <div class="text-white font-extrabold mb-1">
                                        {{ __('livewire_index_main_practical_every') }}
                                        <span class="underline underline-offset-[4px]">
                                            {{ __('livewire_index_main_practical_every_month') }}
                                        </span></div>
                                    <div class="text-white font-extrabold underline">{{ __('livewire_index_main_practical_free') }}</div>
                                </div>
                                <button
                                    @click="window.open('https://t.me/ITCAPITALTOP', '_blank')"
                                    class="relative flex items-center justify-center bg-[#B4FF59] text-black rounded-[8px] font-semibold text-[14px] lg:text-[16px] px-4 py-[12px] transition-shadow duration-300 ease-in hover:bg-[#C5FF80] group overflow-hidden">
                                    <span class="relative z-10">{{ __('livewire_index_main_course_apply') }}</span>
                                </button>
                            </div>
                        </div>
                        <!-- Курс 2 -->
                        <div class="relative min-h-[510px] lg:min-h-0 rounded-[22px] border-[2px] border-[#33267E] bg-[radial-gradient(ellipse_75%_100%_at_90%_90%,_#4D2A88_0%,_#232148_50%,_#211F41_100%)] pt-[20px] lg:pt-[30px] px-4 lg:px-[34px] lg:px-8 py-8 overflow-hidden">
                            <img src="{{ vite()->icon('/main/verified-check.svg') }}"
                                 class="absolute left-[16px] bottom-[16px] top-unset lg:left-unset lg:right-6 lg:top-5 z-10"
                                 alt="check"
                            />
                            <img src="{{ vite()->icon('/main/bg-group.svg') }}"
                                 class="absolute max-w-[280px] lg:max-w-none right-0 bottom-0 z-0 pointer-events-none select-none object-contain opacity-10"
                                 alt=""
                            />
                            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center">
                                <div class="flex text-[16px] flex-col lg:max-w-[60%] gap-2">
                                    <div class="text-white text-[16px] lg:text-[20px] mb-[12px] font-extrabold leading-none">
                                        {{ __('livewire_index_main_course_2_title') }}
                                    </div>
                                    <div>
                                        <div class="text-white underline underline-offset-[4px] font-extrabold pb-[4px]">
                                            {{ __('livewire_index_main_course_2_label') }}
                                        </div>
                                        <div class="text-[#887AA4] font-semibold leading-tight">
                                            {{ __('livewire_index_main_course_2_method') }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-white underline underline-offset-[4px] font-extrabold pb-[4px]">
                                            {{ __('livewire_index_main_course_2_exam_prep_label') }}
                                        </div>
                                        <div class="text-[#887AA4] font-semibold leading-tight">
                                            {{ __('livewire_index_main_course_2_exam_prep') }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-white underline underline-offset-[4px] font-extrabold pb-[4px]">
                                            {{ __('livewire_index_main_course_2_capital_access_label') }}
                                        </div>
                                        <div class="text-[#887AA4] font-semibold leading-tight">
                                            {{ __('livewire_index_main_course_2_capital_access') }}
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="absolute right-[14px] bottom-[14px] lg:right-[32px] lg:bottom-[32px] flex flex-col items-end">
                                <div class="py-4 text-right text-[12px] lg:text-[16px]">
                                    <div class="text-white font-extrabold mb-1 leading-tight">
                                        {!! __('livewire_index_main_course_2_includes') !!}
                                    </div>
                                    <div class="text-white font-extrabold mb-1">
                                        {{ __('livewire_index_main_practical_duration') }}
                                        <span class="underline underline-offset-[4px]">
                                            {{ __('livewire_index_main_course_2_duration_long') }}
                                        </span></div>
                                </div>
                                <button
                                    @click="window.open('https://t.me/ITCAPITALTOP', '_blank')"
                                    class="relative flex items-center justify-center bg-[#B4FF59] text-black rounded-[8px] font-semibold text-[14px] lg:text-[16px] px-4 py-[12px] transition-shadow duration-300 ease-in hover:bg-[#C5FF80] group overflow-hidden">
                                    <span class="relative z-10">{{ __('livewire_index_main_course_apply') }}</span>
                                </button>
                            </div>
                        </div>
                        <!-- Курс 3 -->
                        <div class="relative min-h-[530px] lg:min-h-0 rounded-[22px] border-[2px] border-[#33267E] bg-[radial-gradient(ellipse_75%_100%_at_90%_90%,_#4D2A88_0%,_#232148_50%,_#211F41_100%)] pt-[20px] lg:pt-[30px] px-4 lg:px-[34px] lg:px-8 py-8 overflow-hidden">
                            <img src="{{ vite()->icon('/main/verified-check.svg') }}"
                                 class="absolute left-[16px] bottom-[16px] top-unset lg:left-unset lg:right-6 lg:top-5 z-10"
                                 alt="check"
                            />
                            <img src="{{ vite()->icon('/main/bg-individual.svg') }}"
                                 class="absolute max-w-[280px] lg:max-w-none right-0 bottom-0 z-0 pointer-events-none select-none object-contain opacity-10"
                                 alt=""
                            />
                            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center">
                                <div class="flex text-[16px] flex-col lg:max-w-[60%] min-w-0 gap-2">
                                    <div class="text-white text-[16px] lg:text-[20px] mb-[12px] font-extrabold leading-none">
                                        {{ __('livewire_index_main_course_3_title') }}
                                    </div>
                                    <div>
                                        <div class="text-white underline underline-offset-[4px] font-extrabold pb-[4px]">
                                            {{ __('livewire_index_main_course_3_personalized_label') }}
                                        </div>
                                        <div class="text-[#887AA4] font-semibold leading-tight">
                                            {{ __('livewire_index_main_course_3_personalized') }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-white underline underline-offset-[4px] font-extrabold pb-[4px]">
                                            {{ __('livewire_index_main_course_3_mentorship_label') }}
                                        </div>
                                        <div class="text-[#887AA4] font-semibold leading-tight">
                                            {{ __('livewire_index_main_course_3_mentorship') }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-white underline underline-offset-[4px] font-extrabold pb-[4px]">
                                            {{ __('livewire_index_main_course_3_professional_label') }}
                                        </div>
                                        <div class="text-[#887AA4] font-semibold leading-tight">
                                            {{ __('livewire_index_main_course_3_professional') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="absolute right-[14px] bottom-[14px] lg:right-[32px] lg:bottom-[32px] flex flex-col items-end">
                                <div class="py-4 text-right font-extrabold text-[12px] lg:text-[16px]">
                                    <div class="text-white mb-1 leading-tight">
                                        {!! __('livewire_index_main_course_3_includes_detailed') !!}
                                    </div>
                                    <div class="text-white mb-1">
                                        {{ __('livewire_index_main_practical_duration') }}
                                        <span class="underline underline-offset-[4px]">
                                            {{ __('livewire_index_main_course_3_duration_year') }}
                                        </span>
                                    </div>
                                </div>
                                <button
                                    @click="window.open('https://t.me/ITCAPITALTOP', '_blank')"
                                    class="relative flex items-center justify-center bg-[#B4FF59] text-black rounded-[8px] font-semibold text-[14px] lg:text-[16px] px-4 py-[12px] transition-shadow duration-300 ease-in hover:bg-[#C5FF80] group overflow-hidden">
                                    <span class="relative z-10">
                                        {{ __('livewire_index_main_course_apply') }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-start lg:items-end lg:flex-row justify-between gap-[30px]">
                        <div class="mt-10">
                            <div class="font-extrabold text-white text-[20px] mb-4">
                                {{ __('livewire_index_main_academy_why_title') }}
                            </div>
                            <ul class="list-none flex flex-col text-[#BDBDBD] text-[16px] space-y-1 gap-2 leading-tight">
                                <li class="flex items-start gap-2">
                                    <img src="{{ vite()->icon('/main/check-mark.png') }}" class="w-[24px] h-[24px]" alt="" />
                                    <span>
                                        {{ __('livewire_index_main_academy_why_1') }}
                                    </span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <img src="{{ vite()->icon('/main/check-mark.png') }}" class="w-[24px] h-[24px]" alt="" />
                                    <span>
                                        {{ __('livewire_index_main_academy_why_2') }}
                                    </span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <img src="{{ vite()->icon('/main/check-mark.png') }}" class="w-[24px] h-[24px]" alt="" />
                                    <span>
                                        {{ __('livewire_index_main_academy_why_3') }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="window.open('https://t.me/ITCAPITALTOP', '_blank')"
                                class="relative flex items-start lg:items-center justify-center px-4 py-4 lg:px-[32px] lg:py-[22px] rounded-[8px] bg-[#B4FF59] text-black font-semibold text-[16px] lg:text-[20px] leading-none transition-shadow duration-300 ease-in hover:shadow-[0_0_16px_0_#B4FF59] focus:ring-2 focus:ring-[#B4FF59] group overflow-hidden w-full">
                                <span class="pointer-events-none absolute left-0 top-0 w-full h-full z-0 flex items-center">
                                    <img src="{{ vite()->icon('/main/glare.svg') }}"
                                         alt=""
                                         class="shine-img animate-shine-sweep"
                                    />
                                </span>
                                <span class="relative z-10 text-nowrap">
                                    {{ __('livewire_index_main_academy_start_button') }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
{{--    <footer class="w-full pb-3 mt-24 lg:mt-24" x-data="{ isSupportModalActive: false }">--}}
{{--        <div class="w-full border-t-[2px] border-[#8A87A2]/40 mb-5"></div>--}}
{{--        <div class="container mx-auto px-6 lg:my-[40px]">--}}
{{--            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between text-white text-[16px] leading-[24px]">--}}
{{--                <!-- Левая сетка -->--}}
{{--                <div class="flex flex-row gap-8 md:gap-24">--}}
{{--                    <div class="flex flex-col gap-2">--}}
{{--                        <a href="#" @click.prevent="isSupportModalActive = true" class="hover:underline">Поддержка</a>--}}
{{--                        <a href="#" class="hover:underline">Условия обслуживания</a>--}}
{{--                        <a href="#" class="hover:underline">Обработка персональных данных</a>--}}
{{--                    </div>--}}
{{--                    <div class="flex flex-col gap-2 justify-items-start lg:justify-end">--}}
{{--                        <a href="#" class="hover:underline">VK</a>--}}
{{--                        <a href="#" class="hover:underline">Telegram</a>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--                <!-- Правая часть (копирайт) -->--}}
{{--                <div class="mt-6 md:mt-0 flex items-center justify-end w-full md:w-auto">--}}
{{--                    <span class="text-[#fff] opacity-80">© IT Capital 2025</span>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <x-widget.support-modal />--}}
{{--    </footer>--}}

    <!-- Keep Tailwind CSS classes: w-12 w-14 w-16 w-20 w-24 w-28 w-32 w-36 w-40 w-48 -->
    <span class="w-12 w-14 w-16 w-18 w-20 w-24 w-28 w-32 w-36 w-40 w-48 hidden"></span>
</div>

@script
    <script>
        // Alpine.store('menu', {
        //     open: false
        // });

        Alpine.data('clubCard', () => ({
            loggedIn: {{ auth()->check() ? 'true' : 'false' }},
            user: {
                first: @json(auth()->user()?->first_name ?? ''),
                last:  @json(auth()->user()?->last_name ?? '')
            }
        }));

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

        document.addEventListener('DOMContentLoaded', () => {
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

            observer.observe(parentStick);
        });
    </script>
@endscript

<script>
    function randomComet() {
        const sizes = window.innerWidth <= 450
            ? [16, 20, 24] // для мобильных
            : [20, 24, 28, 32, 36, 40, 48]; // для десктопа

        const left = window.innerWidth <= 450 ? -40 : -30;

        const minSize = Math.min(...sizes);
        const maxSize = Math.max(...sizes);
        const minOpacity = 0.15;
        const maxOpacity = 1;

        const size = sizes[Math.floor(Math.random() * sizes.length)];
        const opacity = (
            minOpacity +
            ((size - minSize) / (maxSize - minSize)) * (maxOpacity - minOpacity)
        ).toFixed(2);

        return {
            left: Math.round(left + Math.random() * 60),
            top: Math.round(80 + Math.random() * 15),
            size,
            opacity: +opacity,
            duration: +(1.2 + Math.random() * 1.7 * 2).toFixed(2),
            delay: 0,
            key: Math.random().toString(36).substring(2)
        };
    }

    function createCometElement(comet, index, onRestart) {
        // Внешний контейнер для позиционирования
        const span = document.createElement('span');
        span.className = 'absolute';
        span.style.left = `${comet.left}%`;
        span.style.top = `${comet.top}%`;

        // Картинка кометы
        const img = document.createElement('img');
        img.src = "{{ vite()->icon('/main/comet.svg') }}";
        img.className = `w-${comet.size} opacity-[${comet.opacity}] animate-comet-hero`;
        img.style.animationDuration = `${comet.duration}s`;
        img.style.animationDelay = `${comet.delay}s`;
        img.alt = '';
        img.dataset.key = comet.key;

        img.addEventListener('animationend', () => onRestart(index, span));

        span.appendChild(img);
        return span;
    }

    function renderComets(comets, onRestart) {
        const container = document.getElementById('comets-block');
        container.innerHTML = '';
        comets.forEach((comet, i) => {
            if (comet) {
                container.appendChild(createCometElement(comet, i, onRestart));
            }
        });
    }

    // --- Инициализация ---
    const COUNT = 5;
    let comets = Array.from({ length: COUNT }, randomComet);

    function restartComet(index, oldSpan) {
        // Удаляем старый элемент
        if (oldSpan && oldSpan.parentNode) {
            oldSpan.parentNode.removeChild(oldSpan);
        }
        // Создаем новую комету
        comets[index] = randomComet();
        // Добавляем в DOM
        const container = document.getElementById('comets-block');
        const newSpan = createCometElement(comets[index], index, restartComet);
        container.appendChild(newSpan);
    }

    // Рендер начальных комет
    renderComets(comets, restartComet);
</script>
