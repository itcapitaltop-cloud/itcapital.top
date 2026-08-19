<div
    class="flex flex-col gap-3"
    x-data="{ hasSelectionError: false }"
    x-init="$el.closest('form').addEventListener('submit', (event) => {
        if (!$refs.fields.querySelector('input[type=checkbox]:checked')) {
            event.preventDefault();
            hasSelectionError = true;
        }
    })"
    x-on:change="hasSelectionError = false"
>
    <div class="flex justify-end gap-2">
        <button
            class="btn"
            type="button"
            x-on:click="$refs.fields.querySelectorAll('input[type=checkbox]').forEach((checkbox) => checkbox.checked = false)"
        >
            Отменить все
        </button>

        <button
            class="btn btn-primary"
            type="button"
            x-on:click="$refs.fields.querySelectorAll('input[type=checkbox]').forEach((checkbox) => checkbox.checked = true); hasSelectionError = false"
        >
            Выбрать все
        </button>
    </div>

    <div
        class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300"
        role="alert"
        x-cloak
        x-show="hasSelectionError"
    >
        Выберите хотя бы одно поле для выгрузки.
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700" x-ref="fields">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <tr>
                    <th class="w-16 px-4 py-3 text-center">Выбрать</th>
                    <th class="px-4 py-3">Поле</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach ($fields as $value => $label)
                    <tr wire:key="user-card-export-field-{{ $value }}">
                        <td class="px-4 py-3 text-center">
                            <input
                                id="user-card-export-field-{{ $value }}"
                                class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary dark:border-slate-600 dark:bg-slate-800"
                                type="checkbox"
                                name="fields[]"
                                value="{{ $value }}"
                                checked
                            >
                        </td>
                        <td class="px-4 py-3">
                            <label class="cursor-pointer" for="user-card-export-field-{{ $value }}">{{ $label }}</label>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
