
@props(['tabs' => []])

@php($first = array_key_first($tabs))
<div {{ $attributes->merge(['class' => 'text-white']) }}
     x-data="{ tab:'{{ $first }}' }">

    <nav class="flex w-full md:w-auto -mb-[2px]">
        @foreach($tabs as $key => $label)
            <button type="button"
                    x-on:click="tab='{{ $key }}'"
                    class="flex-1 md:flex-none px-[15px] py-[8px] md:px-[24px] md:py-[10px] font-dela text-[16px] lg:text-[20px] -ml-[2px] first:ml-0
                           border-[2px] border-[#2E1D78]
                           first:rounded-tl-[24px] last:rounded-tr-[24px]
                           transition-colors leading-[2]"
                    :class="tab==='{{ $key }}'
                        ? 'text-white bg-[#2D2864]/30'
                        : 'text-white/40 hover:text-white border-[#2E1D78]/40'">
                {{ $label }}
            </button>
        @endforeach
    </nav>

    <div class="rounded-[24px] rounded-tl-none rounded-tr-none md:rounded-tr-[24px] border-[2px] border-[#2E1D78]
                bg-[radial-gradient(50%_105%_at_90%_97%,#2D2864_0%,#211F41_100%)]
                p-4 md:p-8">
        @foreach($tabs as $key => $label)
            <div x-show="tab==='{{ $key }}'" x-cloak>
                {{ ${$key} ?? '' }}
            </div>
        @endforeach
    </div>
</div>
