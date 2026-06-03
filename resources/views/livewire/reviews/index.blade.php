<div class="w-full overflow-x-hidden relative" x-data="{ openMenu: false }">
    <x-index.header />

    <div class="mt-[95px] min-h-screen bg-[#0D0C1D]">
        <div class="container py-10 md:py-14">

            {{-- Заголовок и средний рейтинг --}}
            <div class="mb-10">
                <h1 class="text-white font-dela text-[24px] sm:text-[32px] md:text-[40px] leading-tight mb-4">
                    {{ __('reviews_page_title') }}
                </h1>
                @if($totalCount > 0)
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-[4px]">
                            @for ($i = 1; $i <= 5; $i++)
                                <img src="{{ vite()->icon('/main/star.svg') }}" alt="" class="{{ $i <= round($averageRating) ? '' : 'opacity-30' }}" />
                            @endfor
                        </div>
                        <span class="text-white text-[18px] font-medium">{{ number_format($averageRating, 1) }}</span>
                        <span class="text-[#BDBDBD] text-[14px]">
                            {{ trans_choice('reviews_page_based_on', $totalCount, ['count' => $totalCount]) }}
                        </span>
                    </div>
                @endif
            </div>

            <div class="flex flex-col lg:flex-row gap-10 items-start">

                {{-- Форма отзыва --}}
                @auth
                    <div class="w-full lg:w-[400px] shrink-0">
                        <div class="bg-[#13122B] rounded-[16px] p-6 sm:p-8">
                            @if($submitted)
                                <div class="text-center py-6">
                                    <div class="text-[#B4FF59] text-[40px] mb-3">✓</div>
                                    <p class="text-white font-medium text-[18px] mb-2">{{ __('reviews_form_success_title') }}</p>
                                    <p class="text-[#BDBDBD] text-[14px]">{{ __('reviews_form_success_description') }}</p>
                                </div>
                            @else
                                <h2 class="text-white font-dela text-[20px] mb-6">{{ __('reviews_form_title') }}</h2>

                                <form wire:submit="submit" class="flex flex-col gap-5">
                                    {{-- Рейтинг --}}
                                    <div>
                                        <label class="block text-[#BDBDBD] text-[13px] mb-2">{{ __('reviews_form_rating_label') }}</label>
                                        <div class="flex items-center gap-2">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <button
                                                    type="button"
                                                    wire:click="$set('rating', {{ $star }})"
                                                    class="transition-transform hover:scale-110 focus:outline-none"
                                                >
                                                    <img
                                                        src="{{ vite()->icon('/main/star.svg') }}"
                                                        alt="{{ $star }}"
                                                        class="w-7 h-7 transition-opacity {{ $rating >= $star ? 'opacity-100' : 'opacity-30' }}"
                                                    />
                                                </button>
                                            @endfor
                                        </div>
                                        @error('rating')
                                            <p class="text-red-400 text-[12px] mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Текст --}}
                                    <div>
                                        <label class="block text-[#BDBDBD] text-[13px] mb-2">{{ __('reviews_form_body_label') }}</label>
                                        <textarea
                                            wire:model="body"
                                            rows="5"
                                            maxlength="1000"
                                            placeholder="{{ __('reviews_form_body_placeholder') }}"
                                            class="w-full bg-[#1E1D38] border border-[#2D2C50] rounded-[10px] px-4 py-3 text-white text-[14px] placeholder-[#5A5885] focus:outline-none focus:border-[#B4FF59] resize-none transition-colors"
                                        ></textarea>
                                        @error('body')
                                            <p class="text-red-400 text-[12px] mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button
                                        type="submit"
                                        class="w-full py-[14px] rounded-[8px] bg-[#B4FF59] text-[#17162D] font-medium text-[16px] hover:bg-[#C5FF80] hover:shadow-[0_0_16px_0_#B4FF59] transition-all duration-200"
                                        wire:loading.attr="disabled" wire:target="submit"
                                        wire:loading.class="opacity-60" wire:target="submit"
                                    >
                                        <span wire:loading.remove wire:target="submit">{{ __('reviews_form_submit') }}</span>
                                        <span wire:loading wire:target="submit">{{ __('reviews_form_submitting') }}</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="w-full lg:w-[400px] shrink-0">
                        <div class="bg-[#13122B] rounded-[16px] p-6 sm:p-8 text-center">
                            <p class="text-[#BDBDBD] text-[14px] mb-4">{{ __('reviews_form_guest_prompt') }}</p>
                            <a href="{{ route('login') }}" class="inline-block px-6 py-3 rounded-[8px] bg-[#B4FF59] text-[#17162D] font-medium text-[15px] hover:bg-[#C5FF80] transition-colors">
                                {{ __('reviews_form_guest_login') }}
                            </a>
                        </div>
                    </div>
                @endauth

                {{-- Список отзывов --}}
                <div class="flex-1 w-full">
                    @forelse($reviews as $review)
                        <div class="bg-[#13122B] rounded-[16px] p-6 mb-4">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p class="text-white font-medium text-[15px]">
                                        {{ $review->user->first_name }} {{ $review->user->last_name }}
                                    </p>
                                    <p class="text-[#5A5885] text-[12px] mt-0.5">
                                        {{ $review->created_at->format('d.m.Y') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-[3px]">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <img src="{{ vite()->icon('/main/star.svg') }}" alt="" class="w-4 h-4 {{ $i <= $review->rating ? '' : 'opacity-25' }}" />
                                    @endfor
                                </div>
                            </div>
                            <p class="text-[#BDBDBD] text-[14px] leading-relaxed">{{ $review->body }}</p>
                        </div>
                    @empty
                        <div class="text-center py-16">
                            <p class="text-[#5A5885] text-[16px]">{{ __('reviews_empty') }}</p>
                        </div>
                    @endforelse

                    @if($reviews->hasPages())
                        <div class="mt-6">
                            {{ $reviews->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>
