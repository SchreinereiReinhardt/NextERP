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


// UX 1.9.42: Projektakte als echte Bereichsnavigation statt langer Sprungmarken-Seite.
document.addEventListener('DOMContentLoaded', () => {
    const nav = document.querySelector('.erp-project-center-nav')
    const center = document.querySelector('.erp-project-center')
    if (!nav || !center) return

    const ids = ['commercial','appointments','reports','time','material','notes','photos','documents','costs','timeline']
    const sections = ids.map(id => document.getElementById(id)).filter(Boolean)
    const grid = center.querySelector('.erp-project-center-grid')
    const overviewOnly = [
        center.querySelector('.erp-project-center-metrics'),
        center.querySelector('.erp-permissions-compact'),
        center.querySelector('.erp-workflow-card'),
    ].filter(Boolean)

    const tabForHash = hash => {
        const id = (hash || '#overview').replace('#','')
        if (id === 'appointments') return 'appointments'
        if (id === 'reports') return 'reports'
        if (id === 'time') return 'time'
        if (id === 'notes') return 'notes'
        if (id === 'documents' || id === 'photos') return 'documents'
        if (['commercial','material','permissions','costs','timeline'].includes(id)) return id
        return 'overview'
    }

    const showTab = tab => {
        overviewOnly.forEach(el => { el.hidden = tab !== 'overview' })
        sections.forEach(el => { el.hidden = true })

        if (tab === 'documents') {
            const photos = document.getElementById('photos')
            const docs = document.getElementById('documents')
            if (photos) photos.hidden = false
            if (docs) docs.hidden = false
        } else if (tab !== 'overview') {
            const target = document.getElementById(tab)
            if (target) target.hidden = false
        }

        if (grid) {
            const visibleChild = Array.from(grid.children).some(el => !el.hidden)
            grid.hidden = !visibleChild
        }

        nav.querySelectorAll('a[href^="#"]').forEach(a => {
            const active = tabForHash(a.getAttribute('href')) === tab
            a.classList.toggle('is-active', active)
            if (active) a.setAttribute('aria-current','page')
            else a.removeAttribute('aria-current')
        })

        const more = nav.querySelector('.erp-project-nav-more')
        if (more) more.removeAttribute('open')
        window.scrollTo({ top: 0, behavior: 'instant' })
    }

    nav.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', event => {
            event.preventDefault()
            const hash = a.getAttribute('href')
            history.replaceState(null, '', hash)
            showTab(tabForHash(hash))
        })
    })

    showTab(tabForHash(window.location.hash))
})
