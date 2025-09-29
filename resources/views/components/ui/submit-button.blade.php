@php($action = $action ?? 'submit')

<button
    wire:target="{{ $action }}"
    wire:loading.class="opacity-60 cursor-wait"
    {{ $attributes->merge(['class' =>
        'relative flex justify-center py-3 px-4 rounded-lg bg-lime text-[15px] text-[#0A0C23] font-semibold
         transition hover:bg-lime-300 focus:outline-none focus:ring-2 focus:ring-lime-300
         disabled:bg-[#A2A2A2] disabled:text-[#5D5D5D] disabled:cursor-not-allowed']) }}
    @disabled($isDisable ?? false)>

    <span wire:loading.remove wire:target="{{ $action }}">
        {{ $slot }}
    </span>
    <span wire:loading wire:target="{{ $action }}">
        {{ __('loading') }}
    </span>
</button>
