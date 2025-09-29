@php
    use Illuminate\Support\Facades\Log;
@endphp
<div
    x-data="{
        openNotifications: @entangle('openNotifications').live,
        init() {
            if (window.Echo) {
                window.Echo.private('App.Models.User.{{ auth()->id() }}')
                    .notification((n) => {
                        // обновляем счётчик и, если открыт список, подгружаем первую страницу
                        $wire.refreshCount();
                        if (this.open && $wire.tab === 'unread') $wire.resetPage();
                    });
            }
        }
    }"
    x-on:notifications:toggle.window="openNotifications = !openNotifications;"
    class="absolute top-[60px] {{ $isLanding ? 'md:right-[320px]' : 'md:right-[180px]' }} right-[8px] w-[380px] md:w-[440px] max-h-[80vh] z-[60]"
>
    {{-- Выпадающее окно --}}
    <div
        x-cloak
        x-show="openNotifications"
        x-transition
        @click.outside="
        openNotifications = false;"
            class="z-[60] absolute mt-8 w-[380px] md:w-[440px] max-h-[80vh] overflow-hidden
               bg-[radial-gradient(50%_105%_at_90%_97%,#2D2864B3_0%,#211F41B3_100%)]
               border-[2px] border-white/5 rounded-[24px] text-white shadow-lg backdrop-blur-[5px]"
    >
        {{-- Заголовок/табы --}}
        <div class="flex items-center justify-center px-6 pt-[20px] pb-[23px] text-[12px]">
            <div class="flex items-center gap-2 p-[2px] bg-[#1A1934] rounded-[4px]">
                <button wire:click="switchTab('unread')"
                        class="px-[10px] py-[4px] rounded-[4px]
                               {{ $tab==='unread' ? 'bg-[#433F8E]' : 'bg-transparent hover:bg-[#232347] opacity-50' }}">
                    {{ __('unread') }} <span class="opacity-70"> {{ $unreadCount }}</span>
                </button>
                <button wire:click="switchTab('read')"
                        class="px-[10px] py-[4px] rounded-[4px]
                               {{ $tab==='read' ? 'bg-[#433F8E]' : 'bg-transparent hover:bg-[#232347] opacity-50' }}">
                    {{ __('read') }} <span class="opacity-70"> {{ $readCount }}</span>
                </button>
            </div>

            {{--            @if($unreadCount)--}}
            {{--                <button wire:click="markAllRead"--}}
            {{--                        class="px-[12px] py-[6px] rounded-[8px] bg-[#433F8E] hover:bg-[#564BC4] border border-white/20 text-[14px]">--}}
            {{--                    Отметить все прочитанными--}}
            {{--                </button>--}}
            {{--            @endif--}}
        </div>

        {{-- Список --}}
        <div class="px-4 pb-4 max-h-[65vh] overflow-y-auto space-y-[8px] overflow-x-hidden">
            @forelse($this->items as $n)
                @php
                    $d = $n->data;
//                    Log::channel('source')->debug($d);
                    $isReinvestAction = (($d['action']['type'] ?? null) === 'call') && (($d['action']['name'] ?? null) === 'reinvest');
                    $profitUuid       = $d['action']['params']['uuid'] ?? null;
                    $isReinvested     = $isReinvestAction && $profitUuid && ($reinvestedMap[$profitUuid] ?? false);
                @endphp
                <div
                        @if(is_null($n->read_at))
                            wire:click="markAsRead('{{ $n->id }}')"
                        @endif
                        class="relative rounded-[9px] bg-[#1C1B36] border border-[#2c2c57] p-4 {{ (is_null($n->read_at)) ? 'cursor-pointer' : '' }}"
                >
                    <div class="flex gap-3 items-start">
                        <div class="shrink-0 flex pt-[2px] items-center justify-center">
                            <img src="{{ vite()->icon($d['icon']) }}" alt="">
                        </div>

                        <div class="min-w-0 flex-1">
                            <span class="text-[12px] leading-[1.25] {{$d['display'] ?? 'flex'}} ">
                                {!! $d['title'] ?? '' !!} {!! $d['message'] ?? '' !!}
                            </span>
                            <div class="mt-[3px] text-[10px] opacity-[35%]">
                                {{ $n->created_at->format('d F Y, H:i') }}
                            </div>

                            @if(!empty($d['action']) && !empty($d['action']['type']))

                                <div class="flex items-center gap-2 mt-3">
                                    @if(!empty($d['action']) && ($d['action']['type'] ?? null) === 'route')
                                        <a href="{{ route($d['action']['name'], $d['action']['params'] ?? []) }}"
                                           wire:click="markAsRead('{{ $n->id }}')"
                                           class="relative flex justify-center py-[6px] px-[16px] rounded-[8px] bg-lime text-[12px] text-[#0A0C23]
                                                  transition hover:bg-lime-300 focus:outline-none focus:ring-2 focus:ring-lime-300">
                                            {{ $d['button_text'] ?? 'Открыть' }}
                                        </a>
                                    @elseif(!empty($d['action']) && ($d['action']['type'] ?? null) === 'call')
                                        @if($isReinvestAction && $isReinvested)
                                            <button type="button" disabled
                                                    class="relative flex justify-center py-[6px] px-[16px] rounded-[8px] text-[12px] disabled:bg-[#A2A2A2] disabled:text-[#5D5D5D] disabled:cursor-default">
                                                {{ __('already_reinvested') }}
                                            </button>
                                        @else
                                            <button wire:click="performAction('{{ $n->id }}')"
                                                    class="relative flex justify-center py-[6px] px-[16px] rounded-[8px] bg-lime text-[12px] text-[#0A0C23]
                                                           transition hover:bg-lime-300 focus:outline-none focus:ring-2 focus:ring-lime-300">
                                                {{ $d['button_text'] ?? 'Выполнить' }}
                                            </button>
                                        @endif
                                    @endif

                                    {{--                                @if(is_null($n->read_at))--}}
                                    {{--                                    <button wire:click="markAsRead('{{ $n->id }}')"--}}
                                    {{--                                            class="px-3 py-1.5 rounded-[8px] bg-[#433F8E] hover:bg-[#564BC4] border border-white/20 text-[14px]">--}}
                                    {{--                                        Прочитано--}}
                                    {{--                                    </button>--}}
                                    {{--                                @endif--}}
                                </div>
                            @endif
                        </div>

                        {{-- Индикатор непрочитанного --}}

                    </div>
                    @if(is_null($n->read_at))
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 inline-block w-[8px] h-[8px] rounded-full bg-[#B4FF59]"></span>
                    @endif
                </div>
            @empty
                <div class="px-2 py-6 text-center opacity-70">{{ __('no_notifications') }}</div>
            @endforelse

            {{--            <div class="mt-2">--}}
            {{--                {{ $this->items->links(data: ['scrollTo' => false]) }}--}}
            {{--            </div>--}}
        </div>
    </div>
</div>
