document.addEventListener('DOMContentLoaded', () => {

    const KEEP_HEAD  = 2;   // первые две страницы всегда видны
    const KEEP_TAIL  = 2;   // последние две страницы всегда видны
    const WINDOW     = 1;   // «окно» вокруг активной (±1)

    /** полноценный рендер одного блока пагинации */
    function render(pag) {
        const list = pag.querySelector('ul.pagination-list');
        if (!list) return;

        /* вернуть исходное состояние */
        list.querySelectorAll('li.pagination-dots').forEach(li => li.remove());
        list.querySelectorAll('li._is-hidden').forEach(li => li.classList.remove('_is-hidden'));

        const pageItems = [...list.querySelectorAll('li.pagination-item')]
            .filter(li => li.querySelector('a.pagination-page') || li.querySelector('span.pagination-page'));
        if (pageItems.length <= KEEP_HEAD + KEEP_TAIL) return;

        /* номер текущей страницы */
        const currentLi  = list.querySelector('a._is-active, span._is-active')?.closest('li');
        const currentNum = currentLi ? Number(currentLi.textContent.trim()) : NaN;

        const lastIdx = pageItems.length - 1;
        const lastNum = Number(pageItems[lastIdx].textContent.trim());

        let prevKeptNum = null;

        pageItems.forEach((li, idx) => {
            const num = Number(li.textContent.trim());

            const keep =
                idx < KEEP_HEAD ||                       // первые две
                idx >= lastIdx - KEEP_TAIL + 1 ||        // последние две
                Math.abs(num - currentNum) <= WINDOW;    // окно вокруг активной

            if (!keep) {
                li.classList.add('_is-hidden');
                return;
            }

            /* вставить «…», если есть пропуск между видимыми номерами */
            if (prevKeptNum !== null && num - prevKeptNum > 1) {
                const dots = document.createElement('li');
                dots.className = 'pagination-item pagination-dots';
                dots.textContent = '…';
                list.insertBefore(dots, li);
            }
            prevKeptNum = num;
        });
    }

    /* запуск и повторный рендер после клика по номеру */
    document.querySelectorAll('div.pagination').forEach(pag => {
        render(pag);

        pag.addEventListener('click', e => {
            if (!e.target.closest('a.pagination-page')) return;
            /* ждём, пока переместится _is-active (если страница грузится AJAX‑ом) */
            setTimeout(() => render(pag), 0);
        });
    });
});
