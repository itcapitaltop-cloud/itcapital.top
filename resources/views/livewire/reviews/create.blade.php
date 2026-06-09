<div class="w-full overflow-x-hidden relative" x-data="{ openMenu: false }">
    <x-index.header />

    <div class="mt-[95px] min-h-screen bg-[#0D0C1D]">
        <div class="container py-10 md:py-16">
            <div class="max-w-[640px]">

                @if($submitted)
                    <div class="py-6">
                        <div class="w-16 h-16 rounded-full bg-[#B4FF59]/10 flex items-center justify-center mb-6">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 6L9 17L4 12" stroke="#B4FF59" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h1 class="text-white font-dela text-[28px] md:text-[36px] mb-3">{{ __('reviews_form_success_title') }}</h1>
                        <p class="text-[#BDBDBD] text-[15px] mb-8 leading-relaxed">{{ __('reviews_form_success_description') }}</p>
                        <a href="{{ route('reviews') }}" class="inline-block px-7 py-[14px] rounded-[8px] bg-[#B4FF59] text-[#17162D] font-medium text-[15px] hover:bg-[#C5FF80] hover:shadow-[0_0_16px_0_#B4FF59] transition-all duration-200">
                            {{ __('reviews_back') }}
                        </a>
                    </div>
                @else
                    <h1 class="text-white font-dela text-[28px] md:text-[36px] mb-8">
                        {{ __('reviews_form_title') }}
                    </h1>

                    {{-- You are reviewing as --}}
                    <div class="flex items-center gap-2 mb-10 text-[#BDBDBD] text-[15px] flex-wrap">
                        <span>{{ __('reviews_form_you_are_as') }}</span>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-[#1C1B35] flex items-center justify-center shrink-0">
                                <img src="{{ vite()->icon('/actions/user.svg') }}" alt="" class="w-4 h-4" />
                            </div>
                            <span class="text-white font-semibold">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                        </div>
                    </div>

                    <form wire:submit="submit" class="flex flex-col gap-8">
                        {{-- Rating --}}
                        <div>
                            <label class="block text-white font-semibold text-[15px] mb-4">{{ __('reviews_form_rating_label') }}</label>
                            <div class="flex items-center gap-3">
                                @for ($star = 1; $star <= 5; $star++)
                                    <button
                                        type="button"
                                        wire:click="$set('rating', {{ $star }})"
                                        class="focus:outline-none transition-transform hover:scale-110 active:scale-95"
                                        aria-label="{{ $star }}"
                                    >
                                        @if ($rating >= $star)
                                            <svg width="30" height="30" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#FFC533"/>
                                            </svg>
                                        @else
                                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="#FFC533" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        @endif
                                    </button>
                                @endfor
                                <span class="text-[#7B7999] text-[14px] ml-1">{{ $rating }} из 5</span>
                            </div>
                            @error('rating')
                                <p class="text-red-400 text-[12px] mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Text --}}
                        <div>
                            <label class="block text-white font-semibold text-[15px] mb-4">{{ __('reviews_form_body_label') }}</label>
                            <textarea
                                wire:model="body"
                                rows="7"
                                maxlength="1000"
                                placeholder="{{ __('reviews_form_body_placeholder') }}"
                                class="w-full bg-[#13122B] border border-[#2D2C50] rounded-[10px] px-4 py-3 text-white text-[14px] placeholder-[#5A5885] focus:outline-none focus:border-[#7B79C0] resize-none transition-colors"
                            ></textarea>
                            @error('body')
                                <p class="text-red-400 text-[12px] mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-[#5A5885] text-[13px] mt-3 leading-relaxed">
                                {{ __('reviews_form_disclaimer') }}
                            </p>
                        </div>

                        <div>
                            <button
                                type="submit"
                                class="px-7 py-[14px] rounded-[8px] bg-[#B4FF59] text-[#17162D] font-medium text-[16px] hover:bg-[#C5FF80] hover:shadow-[0_0_16px_0_#B4FF59] transition-all duration-200 disabled:opacity-60"
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
