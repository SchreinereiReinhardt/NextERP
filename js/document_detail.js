document.addEventListener('DOMContentLoaded', () => {
    const customer = document.getElementById('documentCustomer')
    const project = document.getElementById('documentProject')
    if (customer && project) {
        const options = Array.from(project.options)
        const filterProjects = () => {
            const customerId = customer.value
            options.forEach((option, index) => {
                if (index === 0) return
                const matches = !customerId || option.dataset.customerId === customerId
                option.hidden = !matches
                option.disabled = !matches
            })
            if (project.selectedOptions[0]?.disabled) project.value = ''
        }
        customer.addEventListener('change', filterProjects)
        filterProjects()
    }
    const net = document.getElementById('documentNet')
    const vat = document.getElementById('documentVat')
    const gross = document.getElementById('documentGross')
    const updateGross = () => {
        if (!net || !vat || !gross || gross.dataset.manual === '1') return
        const n = Number.parseFloat(net.value || '0')
        const v = Number.parseFloat(vat.value || '0')
        if (net.value !== '' || vat.value !== '') gross.value = (n + v).toFixed(2)
    }
    net?.addEventListener('input', updateGross)
    vat?.addEventListener('input', updateGross)
    gross?.addEventListener('input', () => { if (gross.value !== '') gross.dataset.manual = '1' })
})


const pdfPreview = document.getElementById('documentPdfPreview')
const pdfFallback = document.getElementById('documentPdfFallback')
if (pdfPreview && pdfFallback) {
    pdfPreview.addEventListener('error', () => {
        pdfPreview.classList.add('is-hidden')
        pdfFallback.classList.remove('is-hidden')
    })
}


document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('documentAssignForm')
    if (!form) return
    form.addEventListener('submit', event => {
        const type = form.querySelector('[name="documentType"]')
        const date = form.querySelector('[name="documentDate"]')
        if (!type || type.value === 'unassigned') {
            event.preventDefault()
            type?.focus()
            window.alert('Bitte zuerst eine Dokumentart auswählen.')
            return
        }
        if (!date || !date.value) {
            event.preventDefault()
            date?.focus()
            window.alert('Bitte ein Belegdatum auswählen.')
            return
        }
        const button = form.querySelector('button[type="submit"], button:not([type])')
        if (button) {
            button.disabled = true
            button.textContent = 'Dokument wird abgelegt …'
        }
    })
})
