document.addEventListener('DOMContentLoaded', () => {
    const DELAY = 1000;                 // мс до показа полного текста
    let timer;                          // активный таймер

    // навели курсор
    document.body.addEventListener(
        'mouseenter',
        e => {
            const td = e.target.closest('td.has-copy-tooltip');
            if (!td) return;

            // показываем только при реальном обрезании
            if (td.scrollWidth <= td.clientWidth) return;

            timer = setTimeout(() => td.classList.add('show-overflow'), DELAY);
        },
        true
    );

    // ушли курсором
    document.body.addEventListener(
        'mouseleave',
        e => {
            const td = e.target.closest('td.has-copy-tooltip');
            if (!td) return;

            clearTimeout(timer);
            td.classList.remove('show-overflow');
        },
        true
    );
});
