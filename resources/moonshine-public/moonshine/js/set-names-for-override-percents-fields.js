document.addEventListener('DOMContentLoaded', function() {

    const tableOverride = document.querySelector('table[data-name="percentsOverride"]');
    if (!tableOverride) return;

    const rowsOverride = tableOverride.querySelectorAll('tbody tr');
    rowsOverride.forEach((row, idx) => {
        row.querySelectorAll('input, select').forEach(input => {
            let base = input.name;
            input.name = `percentsOverride[${idx}][${base}]`;
        });
    });
});
