document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search)
    const openUuid = params.get('openPackage')
    if (! openUuid) {
        return
    }
    // находим строку по data-row-key и кликаем по её кнопке
    const tr = document.querySelector(`tr[data-row-key="${openUuid}"]`)
    tr?.querySelector('a.btn.btn-primary')?.click()
})
