<div class="w-full h-full bg-[#17162d] relative z-10 py-3 cursor-default {{ $class }}">
    <div class="absolute left-0 top-0 w-32 h-full z-10"
         style="background: linear-gradient(90deg, #171736 0%, #171736BB 30%, #17173699 55%, #17173644 75%, transparent 100%);"></div>
    <div class="absolute right-0 top-0 w-32 h-full z-10"
         style="background: linear-gradient(270deg, #171736 0%, #171736BB 30%, #17173699 55%, #17173644 75%, transparent 100%);"></div>

    <div class="relative w-full h-[58px] flex items-center">
        <div
            x-data="marquee"
            x-init="init"
            class="flex gap-3 animate-marquee whitespace-nowrap"
            :style="'animation-duration:' + duration + 's'"
        >
            @foreach($quotes as $quote)
                <div class="min-w-[210px] max-w-[240px] flex-none h-[52px] bg-[#232347] rounded-[3px] px-3 flex items-center gap-3 shadow-lg">
                    <img src="{{ vite()->icon('/quotes/' . $quote['icon']) }}" alt="" />
                    <div class="flex flex-col justify-center h-full gap-[2px] mr-1">
                        <div class="flex gap-2">
                            <span class="text-[#DBDBDB] font-dela text-[12px]">{{ $quote['name'] }}</span>
                            <span class="text-xs {{ $quote['change_float'] > 0 ? 'text-[#97FF1A]' : 'text-[#DBDBDB]' }}">
                                {{ $quote['arrow'] }}
                            </span>
                        </div>
                        <div class="flex gap-1">
                            <span class="text-[#9A9A9A] font-bold
                                {{ $quote['name'] === 'BTC/USD' ? 'text-[11px]' : 'text-[12px]' }}">{{ $quote['price'] }}</span>
                            <span class="{{ $quote['change_float'] > 0 ? 'text-[#97FF1A]' : 'text-[#9A9A9A]' }} {{ $quote['name'] === 'BTC/USD' ? 'text-[11px]' : 'text-[12px]' }}">
                                {{ $quote['change'] }} ({{ $quote['percent'] }})
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
            @foreach($quotes as $quote)
                <div class="min-w-[210px] max-w-[240px] flex-none h-[52px] bg-[#232347] rounded-[3px] px-3 flex items-center gap-3 shadow-lg">
                    <img src="{{ vite()->icon('/quotes/' . $quote['icon']) }}" alt="" />
                    <div class="flex flex-col justify-center h-full gap-[2px] mr-1">
                        <div class="flex gap-2">
                            <span class="text-[#DBDBDB] font-dela text-[12px]">{{ $quote['name'] }}</span>
                            <span class="text-xs {{ $quote['change_float'] > 0 ? 'text-[#97FF1A]' : 'text-[#DBDBDB]' }}">
                                {{ $quote['arrow'] }}
                            </span>
                        </div>
                        <div class="flex gap-1">
                        <span class="text-[#9A9A9A] font-bold
                            {{ $quote['name'] === 'BTC/USD' ? 'text-[11px]' : 'text-[12px]' }}">{{ $quote['price'] }}</span>
                        <span class="{{ $quote['change_float'] > 0 ? 'text-[#97FF1A]' : 'text-[#9A9A9A]' }} {{ $quote['name'] === 'BTC/USD' ? 'text-[11px]' : 'text-[12px]' }}">
                            {{ $quote['change'] }} ({{ $quote['percent'] }})
                        </span>
                        </div>
                    </div>
                </div>
            @endforeach
            @foreach($quotes as $quote)
                <div class="min-w-[210px] max-w-[240px] flex-none h-[52px] bg-[#232347] rounded-[3px] px-3 flex items-center gap-3 shadow-lg">
                    <img src="{{ vite()->icon('/quotes/' . $quote['icon']) }}" alt="" />
                    <div class="flex flex-col justify-center h-full gap-[2px] mr-1">
                        <div class="flex gap-2">
                            <span class="text-[#DBDBDB] font-dela text-[12px]">{{ $quote['name'] }}</span>
                            <span class="text-xs {{ $quote['change_float'] > 0 ? 'text-[#97FF1A]' : 'text-[#DBDBDB]' }}">
                                {{ $quote['arrow'] }}
                            </span>
                        </div>
                        <div class="flex gap-1">
                        <span class="text-[#9A9A9A] font-bold
                            {{ $quote['name'] === 'BTC/USD' ? 'text-[11px]' : 'text-[12px]' }}">{{ $quote['price'] }}</span>
                        <span class="{{ $quote['change_float'] > 0 ? 'text-[#97FF1A]' : 'text-[#9A9A9A]' }} {{ $quote['name'] === 'BTC/USD' ? 'text-[11px]' : 'text-[12px]' }}">
                            {{ $quote['change'] }} ({{ $quote['percent'] }})
                        </span>
                        </div>
                    </div>
                </div>
            @endforeach
            @foreach($quotes as $quote)
                <div class="min-w-[210px] max-w-[240px] flex-none h-[52px] bg-[#232347] rounded-[3px] px-3 flex items-center gap-3 shadow-lg">
                    <img src="{{ vite()->icon('/quotes/' . $quote['icon']) }}" alt="" />
                    <div class="flex flex-col justify-center h-full gap-[2px] mr-1">
                        <div class="flex gap-2">
                            <span class="text-[#DBDBDB] font-dela text-[12px]">{{ $quote['name'] }}</span>
                            <span class="text-xs {{ $quote['change_float'] > 0 ? 'text-[#97FF1A]' : 'text-[#DBDBDB]' }}">
                                {{ $quote['arrow'] }}
                            </span>
                        </div>
                        <div class="flex gap-1">
                        <span class="text-[#9A9A9A] font-bold
                            {{ $quote['name'] === 'BTC/USD' ? 'text-[11px]' : 'text-[12px]' }}">{{ $quote['price'] }}</span>
                        <span class="{{ $quote['change_float'] > 0 ? 'text-[#97FF1A]' : 'text-[#9A9A9A]' }} {{ $quote['name'] === 'BTC/USD' ? 'text-[11px]' : 'text-[12px]' }}">
                            {{ $quote['change'] }} ({{ $quote['percent'] }})
                        </span>
                        </div>
                    </div>
                </div>
            @endforeach
            @foreach($quotes as $quote)
                <div class="min-w-[210px] max-w-[240px] flex-none h-[52px] bg-[#232347] rounded-[3px] px-3 flex items-center gap-3 shadow-lg">
                    <img src="{{ vite()->icon('/quotes/' . $quote['icon']) }}" alt="" />
                    <div class="flex flex-col justify-center h-full gap-[2px] mr-1">
                        <div class="flex gap-2">
                            <span class="text-[#DBDBDB] font-dela text-[12px]">{{ $quote['name'] }}</span>
                            <span class="text-xs {{ $quote['change_float'] > 0 ? 'text-[#97FF1A]' : 'text-[#DBDBDB]' }}">
                                {{ $quote['arrow'] }}
                            </span>
                        </div>
                        <div class="flex gap-1">
                            <span class="text-[#9A9A9A] font-bold
                                {{ $quote['name'] === 'BTC/USD' ? 'text-[11px]' : 'text-[12px]' }}">{{ $quote['price'] }}
                            </span>
                            <span class="{{ $quote['change_float'] > 0 ? 'text-[#97FF1A]' : 'text-[#9A9A9A]' }} {{ $quote['name'] === 'BTC/USD' ? 'text-[11px]' : 'text-[12px]' }}">
                                {{ $quote['change'] }} ({{ $quote['percent'] }})
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@script
    <script>
        Alpine.data('marquee', () => ({
            duration: 120, // сколько секунд на круг — можешь изменить
            init() {}
        }))

    </script>
@endscript
