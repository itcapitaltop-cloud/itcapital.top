{{--
    Переопределение пагинации MoonShine в стиле журнала «Настройки пакетов»:
    слева сводка «Показано от X до Y из Z», справа кнопки «Назад»/«Вперёд»
    с индикатором «Страница N из M». Ссылки сохраняют async-поведение таблиц.
--}}
@if ($paginator->total() > 0)
    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
        <span class="opacity-70">
            Показано от {{ $paginator->firstItem() }} до {{ $paginator->lastItem() }} из {{ $paginator->total() }}
        </span>

        @if ($paginator->lastPage() > 1)
            <div class="flex items-center gap-2">
                @if ($paginator->onFirstPage())
                    <span class="btn pointer-events-none opacity-50">Назад</span>
                @else
                    <a class="btn btn-primary"
                       href="{{ $paginator->previousPageUrl() }}"
                       @if($async ?? false) @click.prevent="asyncRequest" @endif
                    >Назад</a>
                @endif

                <span>Страница {{ $paginator->currentPage() }} из {{ $paginator->lastPage() }}</span>

                @if ($paginator->hasMorePages())
                    <a class="btn btn-primary"
                       href="{{ $paginator->nextPageUrl() }}"
                       @if($async ?? false) @click.prevent="asyncRequest" @endif
                    >Вперёд</a>
                @else
                    <span class="btn pointer-events-none opacity-50">Вперёд</span>
                @endif
            </div>
        @endif
    </div>
@endif
