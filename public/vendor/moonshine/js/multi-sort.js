document.addEventListener('DOMContentLoaded', () => {
    const svgAsc = `
    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" fill-opacity="1" d="m11.47,4.72a0.75,0.75 0 0 1 1.06,0l3.75,3.75a0.75,0.75 0 0 1 -1.06,1.06l-3.22,-3.22l-3.22,3.22a0.75,0.75 0 0 1 -1.06,-1.06l3.75,-3.75z" clip-rule="evenodd"/>
      <path fill-rule="evenodd" fill-opacity=".4" d="m12.53,4.72zm-4.81,9.75a0.75,0.75 0 0 1 1.06,0l3.22,3.22l3.22,-3.22a0.75,0.75 0 1 1 1.06,1.06l-3.75,3.75a0.75,0.75 0 0 1 -1.06,0l-3.75,-3.75a0.75,0.75 0 0 1 0,-1.06z" clip-rule="evenodd"/>
    </svg>`;
    const svgDesc = `
    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" fill-opacity=".4" d="m11.47,4.72a0.75,0.75 0 0 1 1.06,0l3.75,3.75a0.75,0.75 0 0 1 -1.06,1.06l-3.22,-3.22l-3.22,3.22a0.75,0.75 0 0 1 -1.06,-1.06l3.75,-3.75z" clip-rule="evenodd"/>
      <path fill-rule="evenodd" fill-opacity="1" d="m12.53,4.72zm-4.81,9.75a0.75,0.75 0 0 1 1.06,0l3.22,3.22l3.22,-3.22a0.75,0.75 0 1 1 1.06,1.06l-3.75,3.75a0.75,0.75 0 0 1 -1.06,0l-3.75,-3.75a0.75,0.75 0 0 1 0,-1.06z" clip-rule="evenodd"/>
    </svg>`;

    // функция для рендеринга иконок по multi_sort
    function renderMultiIcons() {
        let multi = {};
        try {
            multi = JSON.parse(new URLSearchParams(location.search).get('multi_sort') || '{}');
        } catch {}

        Object.entries(multi).forEach(([col, dir]) => {
            // ищем ссылку заголовка по параметру sort (без дефиса)
            const selector = `th a[href*="sort=${col}"], th a[href*="sort=-${col}"]`;
            document.querySelectorAll(selector).forEach(link => {
                // текст заголовка без SVG
                const text = link.textContent.trim();
                link.innerHTML = '';
                link.classList.add('flex','items-baseline','gap-x-1');
                link.insertAdjacentText('beforeend', text);
                link.insertAdjacentHTML('beforeend', dir === 'asc' ? svgAsc : svgDesc);
            });
        });
    }

    renderMultiIcons();

    document
        .querySelectorAll('th a[href*="sort="]')
        .forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault()

                const params     = new URLSearchParams(location.search)
                const hrefParams = new URLSearchParams(link.getAttribute('href').split('?')[1])
                const rawSort    = hrefParams.get('sort')
                if (!rawSort) return

                let multi = {}
                try {
                    multi = JSON.parse(params.get('multi_sort') || '{}')
                } catch {}
                if (!e.altKey) {
                    // без Ctrl — сброс всех, оставляем только текущий
                    multi = {}
                }

                // выясняем колонку и текущее направление
                const isDesc = rawSort.startsWith('-')
                let col = isDesc ? rawSort.slice(1) : rawSort

                let newSort;
                if (multi[col]) {
                    newSort = multi[col] === 'desc' ? col : '-' + col;
                    multi[col] = multi[col] === 'desc' ? 'asc' : 'desc';
                }
                else {
                    newSort = (isDesc ? "-" : "") + col;
                    multi[col] = isDesc ? 'desc' : 'asc';
                }
                params.set('sort', newSort)

                params.set('multi_sort', JSON.stringify(multi))

                // флаг Ctrl-кликов для PHP-callback
                params.set('multi', e.altKey ? '1' : '0')

                // сброс страницы
                params.delete('page')

                location.search = params.toString()
            })
        })
})
