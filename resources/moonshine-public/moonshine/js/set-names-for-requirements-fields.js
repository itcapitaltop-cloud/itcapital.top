document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('table[data-name="requirements"]');
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    rows.forEach((row, idx) => {
        row.querySelectorAll('input').forEach(input => {
            let base = input.name;
            input.name = `requirements[${idx}][${base}]`;
        });
    });
});
