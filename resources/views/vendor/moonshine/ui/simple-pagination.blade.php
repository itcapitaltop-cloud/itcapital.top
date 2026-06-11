{{--
    Переопределение simple-пагинации MoonShine (simplePaginate, без общего количества)
    в стиле журнала «Настройки пакетов»: кнопки «Назад»/«Вперёд» и номер текущей
    страницы. Если листать некуда (всё на одной странице) — блок не показывается.
--}}
@php
    $hasPrev = ! $paginator->onFirstPage();
    $hasNext = $paginator->hasMorePages();
@endphp

@if ($hasPrev || $hasNext)
    <div class="mt-4 flex items-center gap-2">
        @if ($hasPrev)
            <a class="btn btn-primary"
               href="{{ $paginator->previousPageUrl() }}"
               @if($async ?? false) @click.prevent="asyncRequest" @endif
            >Назад</a>
        @else
            <span class="btn pointer-events-none opacity-50">Назад</span>
        @endif

        <span>Страница {{ $paginator->currentPage() }}</span>

        @if ($hasNext)
            <a class="btn btn-primary"
               href="{{ $paginator->nextPageUrl() }}"
               @if($async ?? false) @click.prevent="asyncRequest" @endif
            >Вперёд</a>
        @else
            <span class="btn pointer-events-none opacity-50">Вперёд</span>
        @endif
    </div>
@endif
