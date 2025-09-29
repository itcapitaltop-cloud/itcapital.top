document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', function(e) {
        const td = e.target.closest('td.has-copy-tooltip');
        if (! td) {
            return;
        }

        // копируем
        navigator.clipboard.writeText(td.dataset.copy);

        // меняем тултип
        td.setAttribute('data-tooltip', 'Скопировано');

        // через 1.5 сек возвращаем исходный
        setTimeout(function() {
            td.setAttribute('data-tooltip', 'Кликните, чтобы скопировать');
        }, 1500);
    });
});
