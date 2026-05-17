document.addEventListener('DOMContentLoaded', function () {
    // Находим все div.pagination
    const paginators = document.querySelectorAll('div.pagination');
    if (!paginators.length) return;

    // Берём последний пагинатор (снизу)
    const paginator = paginators[paginators.length - 1];
    const paginatorClone = paginator.cloneNode(true);

    // Находим таблицу ресурсов по .table-responsive
    const table = document.querySelector('.table-responsive');
    if (table && paginatorClone) {
        table.parentNode.insertBefore(paginatorClone, table);
    }
});
