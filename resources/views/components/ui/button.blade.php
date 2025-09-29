@props(['disabled' => false])

<button {{ $attributes->merge([
        'type'  => $attributes->get('type', 'button'),
        'class' =>
            // фиолетовый градиент + «hover» как на макете
            'rounded-[8px] bg-[#433F8E] hover:bg-[#564BC4]
             border border-white/20 text-white text-[16px]
             py-[6px] px-[12px] transition disabled:bg-[#404040] disabled:hover:bg-[#404040] disabled:cursor-default'
    ]) }} @disabled($disabled)>
    {{ $slot }}
</button>
