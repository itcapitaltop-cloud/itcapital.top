<div class="w-full overflow-x-hidden relative" x-data="{ openMenu: false }">
    <x-index.header />

    <div class="mt-[95px] min-h-screen bg-[#0D0C1D]">
        <div class="container py-10 md:py-14">

            {{-- Header: title + button --}}
            <div class="flex items-start justify-between gap-4 mb-10">
                <div>
                    <h1 class="text-white font-dela text-[24px] sm:text-[32px] md:text-[40px] leading-tight mb-3">
                        {{ __('reviews_page_title') }}
                    </h1>
                    @if($totalCount > 0)
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-[3px]">
                                @for ($i = 1; $i <= 5; $i++)
                                    <img src="{{ vite()->icon('/main/star.svg') }}" alt="" class="w-4 h-4 {{ $i <= round($averageRating) ? '' : 'opacity-25' }}" />
                                @endfor
                            </div>
                            <span class="text-white text-[16px] font-medium">{{ number_format($averageRating, 1) }}</span>
                            <span class="text-[#BDBDBD] text-[13px]">
                                {{ trans_choice('reviews_page_based_on', $totalCount, ['count' => $totalCount]) }}
                            </span>
                        </div>
                    @endif
                </div>
                @auth
                    <a
                        href="{{ route('reviews.create') }}"
                        class="shrink-0 px-5 py-3 rounded-[8px] bg-[#B4FF59] text-[#17162D] font-medium text-[14px] sm:text-[15px] hover:bg-[#C5FF80] hover:shadow-[0_0_16px_0_#B4FF59] transition-all duration-200 whitespace-nowrap"
                    >
                        {{ __('reviews_page_be_first') }}
                    </a>
                @endauth
            </div>

            {{-- Reviews grid --}}
            @if($reviews->isEmpty())
                <div class="text-center py-16">
                    <p class="text-[#5A5885] text-[16px]">{{ __('reviews_empty') }}</p>
                    @guest
                        <a href="{{ route('login') }}" class="inline-block mt-4 px-6 py-3 rounded-[8px] bg-[#B4FF59] text-[#17162D] font-medium text-[15px] hover:bg-[#C5FF80] transition-colors">
                            {{ __('reviews_form_guest_login') }}
                        </a>
                    @endguest
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    @foreach($reviews as $review)
                        <div class="bg-[#13122B] rounded-[16px] p-6">
                            <div class="flex items-start gap-3 mb-4">
                                {{-- Avatar --}}
                                <div class="w-10 h-10 rounded-full bg-[#1C1B35] flex items-center justify-center shrink-0">
                                    <img src="{{ vite()->icon('/actions/user.svg') }}" alt="" class="w-5 h-5" />
                                </div>
                                {{-- Name + stars + date --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-white font-semibold text-[15px] leading-tight">
                                            {{ $review->user->first_name }} {{ $review->user->last_name }}
                                        </p>
                                        <p class="text-[#5A5885] text-[12px] shrink-0 leading-tight">
                                            {{ $review->created_at->format('d.m.Y') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-[3px] mt-1.5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <img src="{{ vite()->icon('/main/star.svg') }}" alt="" class="w-[14px] h-[14px] {{ $i <= $review->rating ? '' : 'opacity-25' }}" />
                                        @endfor
                                    </div>
                                </div>
                            </div>
                            <p class="text-[#BDBDBD] text-[14px] leading-relaxed">{{ $review->body }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($reviews->hasPages())
                <div class="mt-8">
                    {{ $reviews->links() }}
                </div>
            @endif

        </div>
    </div>
</div>
