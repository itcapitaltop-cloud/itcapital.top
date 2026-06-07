@props([
    'hasMore' => false,
    'colspan' => 4,
    'page' => 0,
])

{{-- Сентинел бесконечной подгрузки журналов ЛК.
     При появлении в зоне видимости дёргает $wire->loadMoreFeed().
     wire:key завязан на номер «страницы», чтобы после догрузки
     элемент пересоздавался и observer навешивался заново. --}}
@if ($hasMore)
    <tr wire:key="feed-loader-{{ $page }}">
        <td colspan="{{ $colspan }}" class="py-3">
            <div
                x-data
                x-init="
                    const observer = new IntersectionObserver((entries) => {
                        if (entries[0].isIntersecting) {
                            observer.disconnect();
                            $wire.loadMoreFeed();
                        }
                    }, { rootMargin: '200px' });
                    observer.observe($el);
                "
                class="flex justify-center"
            >
                <span class="text-white/40 text-sm">{{ __('loading') }}</span>
            </div>
        </td>
    </tr>
@endif
