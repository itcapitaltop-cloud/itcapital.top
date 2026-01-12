@props(['disabled' => false])

<button
    {{ $attributes->class([
        'rounded-[8px] bg-[#433F8E] hover:bg-[#564BC4]
         border border-white/20
         text-[16px] py-[6px] px-[12px] transition disabled:bg-[#404040] disabled:hover:bg-[#404040] disabled:cursor-default',
        // по умолчанию белый текст, если снаружи не передан другой цвет
        'text-white' => !$attributes->has('class') || !str_contains($attributes->get('class'), 'text-'),
    ]) }}
    type="{{ $attributes->get('type', 'button') }}"
    @disabled($disabled)
>
    {{ $slot }}
</button>
