<div class="w-full overflow-x-hidden relative" x-data="{ openMenu: false }">
    <x-index.header />

    <div class="mt-[95px] min-h-screen bg-[#17162D]">
        <div class="container py-10 md:py-14">

            {{-- Back to homepage --}}
            <a
                href="{{ route('index') }}"
                class="inline-flex items-center gap-2 text-white text-[16px] font-medium mb-6 md:mb-8 hover:text-[#B4FF59] transition-colors duration-200"
            >
                <img class="w-[16px]" src="{{ vite()->icon('/actions/arrow-to-main-left-white.svg') }}" alt="" />
                {{ __('home_page') }}
            </a>

            {{-- Header: title + button --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 mb-10 md:mb-12">
                <h1 class="text-white font-dela text-[24px] sm:text-[32px] md:text-[40px] leading-tight">
                    {{ __('reviews_page_title') }}
                </h1>
                @auth
                    <a
                        href="{{ route('reviews.create') }}"
                        class="self-start shrink-0 inline-flex items-center justify-center px-4 py-3 rounded-[8px] bg-[#B4FF59] text-[#17162D] font-medium text-[14px] hover:bg-[#C5FF80] hover:shadow-[0_0_16px_0_#B4FF59] transition-all duration-200 whitespace-nowrap"
                    >
                        {{ __('reviews_page_be_first') }}
                    </a>
                @endauth
            </div>

            {{-- Reviews grid --}}
            @if($reviews->isEmpty())
                <div class="text-center py-16">
                    <p class="text-[#BDBDBD]/60 text-[16px]">{{ __('reviews_empty') }}</p>
                    @guest
                        <a href="{{ route('login') }}" class="inline-block mt-4 px-6 py-3 rounded-[8px] bg-[#B4FF59] text-[#17162D] font-medium text-[15px] hover:bg-[#C5FF80] transition-colors">
                            {{ __('reviews_form_guest_login') }}
                        </a>
                    @endguest
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-y-8 lg:gap-x-[130px] lg:gap-y-12">
                    @foreach($reviews as $review)
                        <div>
                            {{-- Header: avatar + name + stars (+ date on desktop), left-aligned --}}
                            <div class="flex items-center gap-5 mb-5">
                                <div class="flex items-center gap-2 min-w-0">
                                    <img src="{{ vite()->icon('/actions/user.svg') }}" alt="" class="w-6 h-6 shrink-0" />
                                    <p class="text-white font-medium text-[16px] leading-tight truncate">
                                        {{ $review->user->first_name }} {{ $review->user->last_name }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <x-review-star :filled="$i <= $review->rating" />
                                    @endfor
                                </div>
                                <p class="hidden lg:block text-[#BDBDBD]/50 text-[14px] shrink-0 whitespace-nowrap">
                                    {{ $review->created_at->translatedFormat('d F Y') }}
                                </p>
                            </div>

                            {{-- Body --}}
                            <p class="text-[#BDBDBD] text-[14px] leading-relaxed">{{ $review->body }}</p>

                            {{-- Date (mobile, bottom) --}}
                            <p class="lg:hidden text-[#BDBDBD]/50 text-[14px] mt-4">
                                {{ $review->created_at->translatedFormat('d F Y') }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($reviews->hasPages())
                <div class="mt-12">
                    {{ $reviews->links() }}
                </div>
            @endif

        </div>
    </div>
</div>
