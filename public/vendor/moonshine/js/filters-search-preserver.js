document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('submit', (e) => {
        const form = e.target;
        const xData = form.getAttribute('x-data') || '';
        // Проверяем, что x-data начинается с "formBuilder(`filters`"
        if (!xData.startsWith('formBuilder(`filters`')) return;
        const urlParams = new URLSearchParams(window.location.search);
        const search = urlParams.get('search');
        if (!search) return;
        if (!form.querySelector('input[name="search"]')) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'search';
            hidden.value = search;
            form.appendChild(hidden);
        }
    });
});
