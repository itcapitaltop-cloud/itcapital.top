{{--
    Фильтр журнала карточки пользователя. Форма отправляется обычным GET, чтобы фильтр
    жил в адресной строке: ссылки пагинации собираются через withQueryString() и
    сохраняют выбранные значения при переходе по страницам.

    Колонки помечены классом form-group: у MoonShine поля по умолчанию несут
    margin-bottom: 1rem, из-за которого кнопка съезжала на 16px ниже полей, а внутри
    form-group отступ обнуляется и контролам задаётся общая высота 42px — та же, что
    у .btn.

    Инлайновые стили снимают два правила form-group, рассчитанных на вертикальную
    форму: width: 100% (иначе каждая колонка заняла бы отдельную строку) и
    `.form-group + .form-group { margin: 1.25rem 0 }` — из-за него соседние колонки
    всплывали на 20px вверх относительно первой.
--}}
<form class="mb-4 flex flex-wrap items-end gap-4" method="GET" action="{{ $action }}">
    <input type="hidden" name="resourceItem" value="{{ $resourceItem }}">
    <input type="hidden" name="tab" value="logs">
    <input type="hidden" name="journal_tab" value="{{ $journalTab }}">

    @if (! empty($categories))
        <div class="form-group" style="width: auto; margin: 0;">
            <x-moonshine::form.label for="journal-category-{{ $journalTab }}">Категория</x-moonshine::form.label>

            <x-moonshine::form.select
                id="journal-category-{{ $journalTab }}"
                name="journal_category"
                :native="true"
            >
                <x-slot:options>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected((string) $value === (string) $category)>{{ $label }}</option>
                    @endforeach
                </x-slot:options>
            </x-moonshine::form.select>
        </div>
    @endif

    <div class="form-group" style="width: auto; margin: 0;">
        <x-moonshine::form.label for="journal-date-from-{{ $journalTab }}">Дата с</x-moonshine::form.label>

        <x-moonshine::form.input
            id="journal-date-from-{{ $journalTab }}"
            type="date"
            name="journal_date_from"
            :value="$dateFrom"
        />
    </div>

    <div class="form-group" style="width: auto; margin: 0;">
        <x-moonshine::form.label for="journal-date-to-{{ $journalTab }}">Дата по</x-moonshine::form.label>

        <x-moonshine::form.input
            id="journal-date-to-{{ $journalTab }}"
            type="date"
            name="journal_date_to"
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
