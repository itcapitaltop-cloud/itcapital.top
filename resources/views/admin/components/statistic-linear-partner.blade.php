<div class="grid md:[grid-template-columns:1fr_2fr] gap-8 mb-10">
    <div>
        <div class="flex items-center gap-2 mb-3">
            @if ($nextRank)
                <span class="text-white font-semibold text-[16px]">
                          {{ __('livewire_partners_progress_for_next_rank', ['nextRank' => $nextRank]) }}
                </span>
            @endif
        </div>

        @foreach ($progressBars as $bar)
            <x-ui.progress :value="$bar['current']" :max="$bar['target']" class="mb-2">
                <div class="flex justify-between">
                    <span>{{ $bar['label'] }}</span>
                    <span>
                        {{ number_format($bar['current'], 0, ',', '') }}
                        /
                        {{ number_format($bar['target'], 0, ',', '') }}
                    </span>
                </div>
            </x-ui.progress>
        @endforeach
    </div>
</div>
