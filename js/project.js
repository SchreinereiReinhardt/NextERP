document.addEventListener('DOMContentLoaded', () => {
    const workflow = document.querySelector('.erp-workflow')
    if (!workflow) return

    const token = window.OC?.requestToken || ''

    workflow.querySelectorAll('form').forEach(form => {
        const tokenInput = form.querySelector('input[name="requesttoken"]')
        if (tokenInput && token) tokenInput.value = token

        form.addEventListener('submit', async event => {
            event.preventDefault()

            const button = event.submitter || form.querySelector('button')
            if (button) button.disabled = true

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: token ? { requesttoken: token } : {},
                    credentials: 'same-origin',
                    redirect: 'follow',
                })

                if (!response.ok) {
                    throw new Error(`Statuswechsel fehlgeschlagen (${response.status})`)
                }

                window.location.reload()
            } catch (error) {
                console.error(error)
                if (window.OC?.Notification?.showTemporary) {
                    OC.Notification.showTemporary('Projektstatus konnte nicht geändert werden.')
                } else {
                    alert('Projektstatus konnte nicht geändert werden.')
                }
                if (button) button.disabled = false
            }
        })
    })
})
