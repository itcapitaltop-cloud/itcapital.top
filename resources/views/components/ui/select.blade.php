@props([
    'name',
    'options'     => [],
    'icons'       => [],
    'placeholder' => '',
    'label'       => null,
    'value'       => null,
])

@php($first = array_key_first($options))
@php($current = $value ?? old($name) ?? $first)

<div
    x-data="{
        open: false,
        value: @js($current),
        opts: @js($options),
        icons: @js($icons),
        text() { return this.opts[this.value] ?? @js($placeholder) },
        icon() { return this.icons[this.value] ?? null },
        set(val) {
            this.value = val;
            $wire.set('{{ $name }}', val);
        }
    }"
    x-modelable="value"
    class="relative w-full text-white"
    {{ $attributes }}
>
    @isset($label)
        <label class="block mb-2 font-medium">{{ $label }}</label>
    @endisset

    <button type="button"
            @click="open = !open"
            class="w-full flex justify-between items-center
                   bg-[#17162D] border border-[#2E1D78]
                   py-[5px] px-[12px] text-left rounded-[8px] transition"
            :class="open && 'rounded-b-none'">
        <div class="flex items-center gap-2">
            <template x-if="icon()">
                <img :src="icon()" class="w-5 h-4" alt="">
            </template>
            <span x-text="text()"></span>
        </div>
        <img src="{{ vite()->icon('/actions/arrow-down-mini.svg') }}"
             class="w-4 h-4 transition-transform"
             :class="open && 'rotate-180'" alt="">
    </button>

    <ul x-show="open" x-cloak
        @click.outside="open = false"
        class="absolute left-0 top-full w-full bg-[#17162D]
               border border-[#2E1D78] border-t-0 rounded-b-[8px]
               divide-y divide-[#2E1D78] z-20">
        @foreach($options as $val => $text)
            <li>
                <button type="button"
                        @click="set(@js($val)); open = false"
                        class="w-full flex items-center gap-2
                               py-[5px] px-[12px] text-left hover:bg-[#232347']">
                    @if(isset($icons[$val]))
                        <img src="{{ $icons[$val] }}" class="w-5 h-4" alt="">
                    @endif
                    {{ $text }}
                </button>
            </li>
        @endforeach
    </ul>

    <select name="{{ $name }}" x-model="value" class="hidden">
        @foreach($options as $val => $text)
            <option value="{{ $val }}" @selected($val == $current)>{{ $text }}</option>
        @endforeach
    </select>
</div>

