{{--
    Фильтр вкладок «Ввод» и «Вывод» карточки клиента.

    Форма отправляется обычным GET, чтобы фильтр жил в адресной строке: ссылки
    пагинации собираются через withQueryString() и сохраняют выбранные значения при
    переходе по страницам.

    Имена полей разведены префиксом ($tab = deposits|withdraws), потому что обе вкладки
    делят один URL с журналом и статистикой рефералов — без префикса фильтр одной
    вкладки применялся бы и ко второй.

    Разметка и инлайновые стили повторяют admin/partials/user-journal-filter.blade.php:
    form-group обнуляет margin полей MoonShine, иначе кнопка съезжает ниже полей.
--}}
<form class="mb-4 flex flex-wrap items-end gap-4" method="GET" action="{{ $action }}">
    <input type="hidden" name="resourceItem" value="{{ $resourceItem }}">
    <input type="hidden" name="tab" value="{{ $tab }}">

    <div class="form-group" style="width: auto; margin: 0;">
        <x-moonshine::form.label for="finance-status-{{ $tab }}">Статус заявки</x-moonshine::form.label>

        <x-moonshine::form.select
            id="finance-status-{{ $tab }}"
            name="{{ $tab }}_status"
            :native="true"
        >
            <x-slot:options>
                <option value="">Все</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected((string) $value === (string) $status)>{{ $label }}</option>
                @endforeach
            </x-slot:options>
        </x-moonshine::form.select>
    </div>

    <div class="form-group" style="width: auto; margin: 0;">
        <x-moonshine::form.label for="finance-date-from-{{ $tab }}">Дата с</x-moonshine::form.label>

        <x-moonshine::form.input
            id="finance-date-from-{{ $tab }}"
            type="date"
            name="{{ $tab }}_date_from"
            :value="$dateFrom"
        />
    </div>

    <div class="form-group" style="width: auto; margin: 0;">
        <x-moonshine::form.label for="finance-date-to-{{ $tab }}">Дата по</x-moonshine::form.label>

        <x-moonshine::form.input
            id="finance-date-to-{{ $tab }}"
            type="date"
            name="{{ $tab }}_date_to"
            :value="$dateTo"
        />
    </div>

    <div class="flex items-center gap-2">
        <x-moonshine::form.button type="submit" class="btn-primary">Показать</x-moonshine::form.button>

        @if ($isFiltered)
            <a class="btn" href="{{ $resetUrl }}">Сбросить</a>
        @endif
    </div>
</form>
