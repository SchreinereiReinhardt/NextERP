document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('signatureCanvas')
    const form = document.getElementById('reportMainForm')
    const hidden = document.getElementById('signatureData')
    const status = document.getElementById('signatureStatus')
    if (!canvas || !form || !hidden) return

    const ctx = canvas.getContext('2d')
    ctx.lineWidth = 2.5
    ctx.lineCap = 'round'
    ctx.strokeStyle = '#111827'
    let drawing = false
    let changed = false

    const point = event => {
        const rect = canvas.getBoundingClientRect()
        const source = event.touches ? event.touches[0] : event
        return {
            x: (source.clientX - rect.left) * (canvas.width / rect.width),
            y: (source.clientY - rect.top) * (canvas.height / rect.height),
        }
    }
    const start = event => {
        drawing = true
        changed = true
        const p = point(event)
        ctx.beginPath()
        ctx.moveTo(p.x, p.y)
        if (status) status.textContent = 'Unterschrift erfasst – jetzt Rapport speichern oder abschließen.'
        event.preventDefault()
    }
    const move = event => {
        if (!drawing) return
        const p = point(event)
        ctx.lineTo(p.x, p.y)
        ctx.stroke()
        event.preventDefault()
    }
    const end = event => {
        drawing = false
        event.preventDefault()
    }

    canvas.addEventListener('mousedown', start)
    canvas.addEventListener('mousemove', move)
    canvas.addEventListener('mouseup', end)
    canvas.addEventListener('mouseleave', end)
    canvas.addEventListener('touchstart', start, { passive: false })
    canvas.addEventListener('touchmove', move, { passive: false })
    canvas.addEventListener('touchend', end, { passive: false })

    document.getElementById('clearSignature')?.addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height)
        changed = false
        hidden.value = ''
        if (status) status.textContent = 'Unterschrift wurde gelöscht.'
    })

    form.addEventListener('submit', event => {
        const submitter = event.submitter
        if (changed) hidden.value = canvas.toDataURL('image/png')
        if (submitter?.value === 'sign' && !changed) {
            event.preventDefault()
            if (status) status.textContent = 'Bitte zuerst eine Unterschrift zeichnen.'
            canvas.focus()
        }
    })
})
