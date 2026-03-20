function isEligibleHeaderSibling(element) {
    if (!element) {
        return false;
    }

    if (element.matches('form, .form, [data-fragment-name="crud-form"]')) {
        return false;
    }

    if (element.querySelector('form, .form, [data-fragment-name="crud-form"]')) {
        return false;
    }

    return Boolean(element.querySelector('.btn, .dropdown'));
}

function wrapHeadingAndFirstBlock() {
    document.querySelectorAll('.layout-content').forEach(section => {
        const h1 = section.querySelector(':scope > h1');
        if (!h1) {
            return;
        }

        if (h1.parentElement.classList.contains('header-row')) {
            return;
        }

        h1.classList.remove('truncate');

        let next = h1.nextElementSibling;
        while (next && next.classList.contains('hidden') && next.classList.contains('remove-after-init')) {
            next = next.nextElementSibling;
        }

        if (!isEligibleHeaderSibling(next)) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'header-row';

        section.insertBefore(wrapper, h1);
        wrapper.append(h1, next);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', wrapHeadingAndFirstBlock);
} else {
    wrapHeadingAndFirstBlock();
}
