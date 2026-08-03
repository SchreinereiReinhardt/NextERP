document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('contactImportSearch')
    const rows = [...document.querySelectorAll('.erp-import-contact')]

    search?.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase()
        rows.forEach(row => {
            row.hidden = query !== '' && !String(row.dataset.search || '').includes(query)
        })
    })

    document.getElementById('selectVisibleContacts')?.addEventListener('click', () => {
        rows.forEach(row => {
            const checkbox = row.querySelector('input[type="checkbox"]')
            if (!row.hidden && checkbox && !checkbox.disabled) {
                checkbox.checked = true
            }
        })
    })
})
