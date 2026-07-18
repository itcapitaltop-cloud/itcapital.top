<section class="w-full px-4 mt-10 sm:px-5 md:px-6  md:mt-16">
    @foreach($news as $new)
        <article>
            <a href="{{ route('news.index') }}#news-{{ $new->id }}" class="block">
                <p class="font-medium text-xs text-white opacity-50">
                    {{ $new->published_at->format('j.m, H:i') }} • {{ $new->category->label() }}
                </p>
                @foreach($new->translations as $translation)
                <div class="w-full">
                    <div class="flex align-top justify-between mt-1.5">
                        <p class="font-semibold text-sm text-white">
                            {{ $translation->title }}
                        </p>
                        <image class="w-[76px] h-[51px] object-cover shrink-0" src="{{ \Illuminate\Support\Facades\Storage::url($new->image) }}"/>
                    </div>

                    <p class="mt-3 font-normal text-sm text-white">
                        {!!  $position === 'sidebar' ? $translation->web_preview : $translation->mobile_preview  !!}
                    </p>
                </div>
                @endforeach
            </a>
        </article>

        @if($position === 'mobile-menu' || !$loop->last)
            <div class="border-t border-[#28255b] my-5"></div>
        @endif
    @endforeach
</section>
