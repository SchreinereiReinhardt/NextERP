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


document.addEventListener('DOMContentLoaded', () => {
    const net = document.getElementById('offerImportNet')
    const vatRate = document.getElementById('offerImportVatRate')
    const vatAmount = document.getElementById('offerImportVatAmount')
    const gross = document.getElementById('offerImportGross')
    const check = document.getElementById('offerImportAmountCheck')
    if (!net || !vatRate || !vatAmount || !gross || !check) return

    let manualVat = vatAmount.value !== ''
    let manualGross = gross.value !== ''
    const number = element => Number.parseFloat(element.value || '0')
    const validate = () => {
        const n = number(net)
        const v = number(vatAmount)
        const g = number(gross)
        const ok = Math.abs((n + v) - g) <= 0.02
        check.textContent = ok
            ? '✓ Summen plausibel: Netto + USt.-Betrag = Brutto'
            : '⚠ Summen stimmen nicht überein: Netto + USt.-Betrag muss Brutto ergeben'
        check.classList.toggle('erp-text-danger', !ok)
        return ok
    }
    const calculate = () => {
        const n = number(net)
        const rate = number(vatRate)
        if (!manualVat) vatAmount.value = (n * rate / 100).toFixed(2)
        if (!manualGross) gross.value = (n + number(vatAmount)).toFixed(2)
        validate()
    }
    net.addEventListener('input', calculate)
    vatRate.addEventListener('input', () => { manualVat = false; manualGross = false; calculate() })
    vatAmount.addEventListener('input', () => { manualVat = true; if (!manualGross) gross.value = (number(net) + number(vatAmount)).toFixed(2); validate() })
    gross.addEventListener('input', () => { manualGross = true; validate() })
    net.closest('form')?.addEventListener('submit', event => {
        if (!validate()) {
            event.preventDefault()
            window.alert('Bitte Netto, USt.-Betrag und Brutto kontrollieren. Die Summen stimmen nicht überein.')
        }
    })
    validate()
})
