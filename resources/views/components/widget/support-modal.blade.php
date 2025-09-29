<x-widget.modal condition-name="isSupportModalActive">
    <div class="px-8 pt-8 pb-7 rounded-[22px] bg-[#211F41] shadow-lg relative overflow-hidden">
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-white font-dela text-[20px]">{{ __('technical_support') }}</h3>
            <button class="p-1 -m-1" x-on:click="isSupportModalActive = false">
                <img src="{{ vite()->icon('/actions/cross.svg') }}" alt="Закрыть">
            </button>
        </div>
        <div class="text-[#BDBDBD] text-[16px] mb-8">
            {{ __('technical_support_telegram') }}
        </div>
        <a href="https://t.me/ITCAPITALTOP" target="_blank"
           class="w-full block rounded-xl py-3 text-center font-dela text-[17px] bg-[#B4FF59] text-black hover:bg-[#CAFF79] transition">
            {{ __('open_telegram') }}
        </a>
    </div>
</x-widget.modal>
