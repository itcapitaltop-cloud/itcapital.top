// document.addEventListener('DOMContentLoaded', function() {
//
//     const tableCommon = document.querySelector('table[data-name="percentsCommon"]');
//     if (!tableCommon) return;
//
//     const rowsCommon = tableCommon.querySelectorAll('tbody tr');
//     rowsCommon.forEach((row, idx) => {
//         row.querySelectorAll('input, select').forEach(input => {
//             let base = input.name;
//             input.name = `percentsCommon[${idx}][${base}]`;
//         });
//     });
// });

(function () {
    const TABLE_SELECTOR = 'table[data-name="percentsCommon"]';
    const MODAL_EVENT    = 'modal-toggled-edit-global-percents-modal'; // имя вашей модалки

    function renameInputs(form) {
        const table = form.querySelector(TABLE_SELECTOR);
        if (!table) return;

        const tbody = table.tBodies && table.tBodies[0];
        if (!tbody) return;

        Array.from(tbody.rows).forEach((row, idx) => {
            row.querySelectorAll('input[name], select[name], textarea[name]').forEach((el) => {
                // Запоминаем «базовое» имя, чтобы не городить percentsCommon[...][percentsCommon...]
                if (!el.dataset.baseName) {
                    const cur = el.getAttribute('name') || '';
                    const m = cur.match(/^percentsCommon\[\d+\]\[([^\]]+)\]$/);
                    el.dataset.baseName = m ? m[1] : cur;
                }
                el.setAttribute('name', `percentsCommon[${idx}][${el.dataset.baseName}]`);
            });
        });
    }

    // 1) Надёжно: перед любым сабмитом формы (в захвате)
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.dataset?.name === "global-percent-form") {
            renameInputs(form);
        }
    }, true);

    // 2) Опционально: при открытии модалки (MoonShine JsEvent::MODAL_TOGGLED)
    // window.addEventListener(MODAL_EVENT, function () {
    //     // небольшая задержка, чтобы успел вставиться HTML
    //     setTimeout(() => {
    //         const form = document.querySelector(FORM_SELECTOR);
    //         if (form) renameInputs(form);
    //     }, 50);
    // });
})();
