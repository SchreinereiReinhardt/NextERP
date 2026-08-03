document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('signatureCanvas')
    const form = document.getElementById('reportSignatureForm')
    const hiddenInput = form?.querySelector('input[name="signatureData"]')
    const status = document.getElementById('signatureStatus')
    const clearButton = document.getElementById('clearSignature')

    if (!canvas || !form || !hiddenInput) return

    const context = canvas.getContext('2d')
    if (!context) {
        if (status) status.textContent = 'Unterschriftsfeld konnte nicht geladen werden.'
        return
    }

    context.lineWidth = 3
    context.lineCap = 'round'
    context.lineJoin = 'round'
    context.strokeStyle = '#111'

    let drawing = false
    let hasSignature = false

    const getPoint = event => {
        const rect = canvas.getBoundingClientRect()
        return {
            x: (event.clientX - rect.left) * (canvas.width / rect.width),
            y: (event.clientY - rect.top) * (canvas.height / rect.height),
        }
    }

    const sync = () => {
        hiddenInput.value = hasSignature ? canvas.toDataURL('image/png') : ''
    }

    const start = event => {
        event.preventDefault()
        drawing = true
        hasSignature = true
        const point = getPoint(event)
        context.beginPath()
        context.moveTo(point.x, point.y)
        context.lineTo(point.x + 0.1, point.y + 0.1)
        context.stroke()
        canvas.setPointerCapture?.(event.pointerId)
        sync()
        if (status) status.textContent = 'Unterschrift erfasst. Jetzt speichern und abschließen.'
    }

    const move = event => {
        if (!drawing) return
        event.preventDefault()
        const point = getPoint(event)
        context.lineTo(point.x, point.y)
        context.stroke()
        sync()
    }

    const stop = event => {
        if (!drawing) return
        event.preventDefault()
        drawing = false
        context.closePath()
        canvas.releasePointerCapture?.(event.pointerId)
        sync()
    }

    canvas.style.touchAction = 'none'
    canvas.tabIndex = 0
    canvas.addEventListener('pointerdown', start)
    canvas.addEventListener('pointermove', move)
    canvas.addEventListener('pointerup', stop)
    canvas.addEventListener('pointercancel', stop)
    canvas.addEventListener('pointerleave', stop)

    clearButton?.addEventListener('click', () => {
        context.clearRect(0, 0, canvas.width, canvas.height)
        drawing = false
        hasSignature = false
        hiddenInput.value = ''
        if (status) status.textContent = 'Unterschrift gelöscht.'
    })

    form.addEventListener('submit', event => {
        hasSignature = hasSignature || canvas.toDataURL('image/png').length > 200
        sync()
        const signedBy = form.querySelector('[name="signedBy"]')
        if (!signedBy || signedBy.value.trim() === '') {
            event.preventDefault()
            if (status) status.textContent = 'Bitte den Namen der unterschreibenden Person eintragen.'
            signedBy?.focus()
            return
        }
        if (!hasSignature || hiddenInput.value === '') {
            event.preventDefault()
            if (status) status.textContent = 'Bitte zuerst eine Unterschrift zeichnen.'
            canvas.focus()
        }
    })
})
