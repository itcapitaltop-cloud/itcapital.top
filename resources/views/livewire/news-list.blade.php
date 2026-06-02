<div class="grid gap-6 text-white">
    <x-bg.main>
        <div class="px-6 pt-[16px] pb-[5px] md:pt-6 md:pb-4">
            <h3 class="text-[20px] font-dela">{{ __('Финансовые новости') }}</h3>
        </div>

        <section class="w-full px-4 mt-10 sm:px-5 md:px-6 md:mt-6">
            @forelse($news as $new)
                <article wire:key="news-{{ $new->id }}" class="overflow-hidden">
                    <p class="font-medium text-base text-white opacity-50">
                        {{ $new->published_at->format('j.m, H:i') }} • {{ $new->category->label() }}
                    </p>

                    @foreach($new->translations as $translation)
                        <div class="w-full mt-2">
                            <p class="font-semibold text-2xl text-white mb-4">
                                {{ $translation->title }}
                            </p>

                            <img
                                class="w-[204px] h-[132px] object-cover float-left mr-4"
                                src="{{ \Illuminate\Support\Facades\Storage::url($new->image) }}"
                                alt="{{ $translation->title }}"
                            />

                            <div class="text-[14px] text-white leading-[1.45] font-normal [&_a]:text-[#a8ee56]">
                                <x-markdown>
                                    {{ $translation->content }}
                                </x-markdown>
                            </div>
                        </div>
                    @endforeach
                </article>

                <div class="border-t border-[#28255b] my-5"></div>
            @empty
                <div class="px-6 pb-6 pt-3 md:pb-8">
                    <div class="rounded-[20px] border border-white/10 bg-white/[0.03] px-5 py-8 text-center md:px-8 md:py-10">
                        <p class="mt-5 font-dela text-[20px] leading-tight text-white">
                            {{ __('liveware_news_not_found') }}
                        </p>
                    </div>
                </div>
            @endforelse

            @if($hasMorePages)
                <div
                    x-data
                    x-init="
                        const observer = new IntersectionObserver((entries) => {
                            if (entries[0].isIntersecting) {
                                $wire.loadMore();
                            }
                        }, { rootMargin: '300px' });
                        observer.observe($el);
                    "
                    class="flex justify-center py-6"
                >
                    <span wire:loading wire:target="loadMore" class="text-sm text-white opacity-50">
                        {{ __('Загрузка...') }}
                    </span>
                </div>
            @endif
        </section>
    </x-bg.main>
</div>
