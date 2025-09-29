function wrapHeadingAndFirstBlock() {
    document.querySelectorAll('.layout-content').forEach(section => {
        const h1 = section.querySelector(':scope > h1');
        if (!h1) return;
        if (h1.parentElement.classList.contains('header-row')) return;

        h1.classList.remove('truncate');

        // Ищем первый следующий элемент‑брат, НЕ имеющий обоих классов
        let next = h1.nextElementSibling;
        while (next && next.classList.contains('hidden') && next.classList.contains('remove-after-init')) {
            next = next.nextElementSibling;
        }
        if (!next) return;

        // создаём flex‑обёртку
        const wrapper = document.createElement('div');
        wrapper.className = 'header-row';

        section.insertBefore(wrapper, h1);
        wrapper.append(h1, next);

        // observeOverflow(wrapper);
    });
}

function observeOverflow(el) {
    const ro = new ResizeObserver(entries => {
        for (const { target } of entries) {
            const overflow = target.scrollWidth > target.clientWidth;
            target.classList.toggle('is-block', overflow);
        }
    });
    ro.observe(el);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', wrapHeadingAndFirstBlock);
} else {
    wrapHeadingAndFirstBlock();
}
