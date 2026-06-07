{{--
    Переопределение simple-пагинации MoonShine.

    Отличия от стандартной:
    - если листать некуда (вся история на одной странице) — блок не показывается,
      чтобы админ не кликал по «мёртвым» кнопкам;
    - показываем номер текущей страницы — видно, что идёт листание;
    - недоступная кнопка явно погашена (полупрозрачная, некликабельная).
--}}
@php
    $hasPrev = ! $paginator->onFirstPage();
    $hasNext = $paginator->hasMorePages();
    $disabledStyle = 'opacity:.4;cursor:not-allowed;pointer-events:none;';
@endphp

@if ($hasPrev || $hasNext)
    <ul class="pagination-list simple">
        {{-- Назад --}}
        <li>
            @if ($hasPrev)
                <a href="{{ $paginator->previousPageUrl() }}"
                   @if($async ?? false) @click.prevent="asyncRequest" @endif
                   class="pagination-simple"
                >
                    {!! __('moonshine::pagination.previous') !!}
                </a>
            @else
                <span class="pagination-simple disabled" style="{{ $disabledStyle }}">
                    {!! __('moonshine::pagination.previous') !!}
                </span>
            @endif
        </li>

        {{-- Текущая страница --}}
        <li>
            <span class="pagination-simple" style="pointer-events:none;background:transparent;border:none;font-weight:600;">
                {{ __('Страница') }} {{ $paginator->currentPage() }}
            </span>
        </li>

        {{-- Вперёд --}}
        <li>
            @if ($hasNext)
                <a href="{{ $paginator->nextPageUrl() }}"
                   @if($async ?? false) @click.prevent="asyncRequest" @endif
                   class="pagination-simple"
                >
                    {!! __('moonshine::pagination.next') !!}
                </a>
            @else
                <span class="pagination-simple disabled" style="{{ $disabledStyle }}">
                    {!! __('moonshine::pagination.next') !!}
                </span>
            @endif
        </li>
    </ul>
@endif
