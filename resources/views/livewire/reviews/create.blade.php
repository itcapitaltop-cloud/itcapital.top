<div class="w-full overflow-x-hidden relative" x-data="{ openMenu: false }">
    <x-index.header />

    <div class="mt-[95px] min-h-screen bg-[#17162D]">
        <div class="container py-10 md:py-16">
            <div class="max-w-[900px]">

                @if($submitted)
                    <div class="py-6">
                        <div class="w-16 h-16 rounded-full bg-[#B4FF59]/10 flex items-center justify-center mb-6">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 6L9 17L4 12" stroke="#B4FF59" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h1 class="text-white font-dela text-[24px] sm:text-[32px] md:text-[40px] leading-tight mb-3">{{ __('reviews_form_success_title') }}</h1>
                        <p class="text-[#BDBDBD] text-[15px] mb-8 leading-relaxed">{{ __('reviews_form_success_description') }}</p>
                        <a href="{{ route('reviews') }}" class="inline-flex items-center justify-center px-4 py-3 rounded-[8px] bg-[#B4FF59] text-[#17162D] font-medium text-[14px] hover:bg-[#C5FF80] hover:shadow-[0_0_16px_0_#B4FF59] transition-all duration-200">
                            {{ __('reviews_back') }}
                        </a>
                    </div>
                @else
                    <h1 class="text-white font-dela text-[24px] sm:text-[32px] md:text-[40px] leading-tight mb-8">
                        {{ __('reviews_form_title') }}
                    </h1>

                    {{-- You are reviewing as --}}
                    <div class="mb-8 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                        <p class="text-white text-[16px]">{{ __('reviews_form_you_are_as') }}</p>
                        <div class="flex items-center gap-2">
                            <img src="{{ vite()->icon('/actions/user.svg') }}" alt="" class="w-6 h-6 shrink-0" />
                            <span class="text-white font-medium text-[16px]">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                        </div>
                    </div>

                    <form wire:submit="submit" class="flex flex-col gap-8">
                        {{-- Rating --}}
                        <div>
                            <label class="block text-white font-semibold text-[16px] mb-3">{{ __('reviews_form_rating_label') }}</label>
                            <div class="flex items-center gap-1">
                                @for ($star = 1; $star <= 5; $star++)
                                    <button
                                        type="button"
                                        wire:click="$set('rating', {{ $star }})"
                                        class="focus:outline-none transition-transform hover:scale-110 active:scale-95"
                                        aria-label="{{ $star }}"
                                    >
                                        <x-review-star :filled="$rating >= $star" />
                                    </button>
                                @endfor
                                <span class="text-white font-semibold text-[16px] ml-2">{{ $rating }} {{ __('reviews_form_rating_of') }}</span>
                            </div>
                            @error('rating')
                                <p class="text-red-400 text-[12px] mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Text --}}
                        <div>
                            <label class="block text-white font-semibold text-[16px] mb-3">{{ __('reviews_form_body_label') }}</label>
                            <textarea
                                wire:model="body"
                                rows="8"
                                maxlength="1000"
                                placeholder="{{ __('reviews_form_body_placeholder') }}"
                                class="w-full bg-[#17162D] border border-[#2E1D78] rounded-[8px] px-3 py-2 text-white text-[16px] placeholder-white/30 focus:outline-none focus:border-[#B4FF59]/50 resize-none transition-colors"
                            ></textarea>
                            @error('body')
                                <p class="text-red-400 text-[12px] mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-white/50 text-[14px] mt-3 leading-relaxed">
                                {{ __('reviews_form_disclaimer') }}
                            </p>
                        </div>

                        <div>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center px-4 py-3 rounded-[8px] bg-[#B4FF59] text-[#17162D] font-medium text-[14px] hover:bg-[#C5FF80] hover:shadow-[0_0_16px_0_#B4FF59] transition-all duration-200 disabled:opacity-60"
                                wire:loading.attr="disabled"
                                wire:target="submit"
                                wire:loading.class="opacity-60"
                            >
                                <span wire:loading.remove wire:target="submit">{{ __('reviews_form_submit') }}</span>
                                <span wire:loading wire:target="submit">{{ __('reviews_form_submitting') }}</span>
                            </button>
                        </div>
                    </form>
                @endif

            </div>
        </div>
    </div>
</div>
