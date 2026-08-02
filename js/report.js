document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('signatureCanvas')
    const form = document.getElementById('reportMainForm')
    const hiddenInput = document.getElementById('signatureData')
    const status = document.getElementById('signatureStatus')
    const clearButton = document.getElementById('clearSignature')

    if (!canvas || !form || !hiddenInput) {
        return
    }

    const context = canvas.getContext('2d', { willReadFrequently: true })
    if (!context) {
        if (status) status.textContent = 'Unterschriftsfeld konnte nicht geladen werden.'
        return
    }

    context.lineWidth = 3
    context.lineCap = 'round'
    context.lineJoin = 'round'
    context.strokeStyle = '#111111'

    let drawing = false
    let hasSignature = false

    const getPoint = event => {
        const rect = canvas.getBoundingClientRect()
        return {
            x: (event.clientX - rect.left) * (canvas.width / rect.width),
            y: (event.clientY - rect.top) * (canvas.height / rect.height),
        }
    }

    const syncSignature = () => {
        hiddenInput.value = hasSignature ? canvas.toDataURL('image/png') : ''
    }

    const startDrawing = event => {
        event.preventDefault()
        const point = getPoint(event)
        drawing = true
        hasSignature = true
        canvas.setPointerCapture?.(event.pointerId)
        context.beginPath()
        context.moveTo(point.x, point.y)
        // A single tap must also count as a signature mark.
        context.lineTo(point.x + 0.1, point.y + 0.1)
        context.stroke()
        syncSignature()
        if (status) status.textContent = 'Unterschrift erfasst. Name eintragen und speichern.'
    }

    const continueDrawing = event => {
        if (!drawing) return
        event.preventDefault()
        const point = getPoint(event)
        context.lineTo(point.x, point.y)
        context.stroke()
        syncSignature()
    }

    const stopDrawing = event => {
        if (!drawing) return
        event.preventDefault()
        drawing = false
        context.closePath()
        canvas.releasePointerCapture?.(event.pointerId)
        syncSignature()
    }

    canvas.style.touchAction = 'none'
    canvas.tabIndex = 0
    canvas.addEventListener('pointerdown', startDrawing)
    canvas.addEventListener('pointermove', continueDrawing)
    canvas.addEventListener('pointerup', stopDrawing)
    canvas.addEventListener('pointercancel', stopDrawing)
    canvas.addEventListener('pointerleave', stopDrawing)

    clearButton?.addEventListener('click', () => {
        context.clearRect(0, 0, canvas.width, canvas.height)
        drawing = false
        hasSignature = false
        hiddenInput.value = ''
        if (status) status.textContent = 'Unterschrift wurde gelöscht.'
    })

    form.addEventListener('submit', event => {
        syncSignature()
        const submitter = event.submitter
        if (submitter?.value === 'sign') {
            const signedBy = form.querySelector('[name="signedBy"]')
            if (!hasSignature || hiddenInput.value === '') {
                event.preventDefault()
                if (status) status.textContent = 'Bitte zuerst eine Unterschrift zeichnen.'
                canvas.focus()
                return
            }
            if (signedBy && signedBy.value.trim() === '') {
                event.preventDefault()
                if (status) status.textContent = 'Bitte den Namen der unterschreibenden Person eintragen.'
                signedBy.focus()
            }
        }
    })
})
