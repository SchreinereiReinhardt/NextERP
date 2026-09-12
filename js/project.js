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


// Betrio 2.4.9: Projektakte – eindeutige Reiter ohne durchlaufende Karten.
document.addEventListener('DOMContentLoaded', () => {
    const nav = document.querySelector('.erp-project-center-nav')
    const center = document.querySelector('.erp-project-center')
    if (!nav || !center) return

    const tabIds = [
        'offers', 'orders', 'invoices', 'appointments', 'reports', 'time',
        'material', 'notes', 'checklist', 'photos', 'documents', 'permissions', 'costs', 'timeline'
    ]
    const overviewOnlyIds = ['billing', 'payments']

    const overviewOnly = [
        center.querySelector('.erp-project-center-metrics'),
        center.querySelector('.erp-permissions-compact'),
        center.querySelector('.erp-workflow-card'),
        ...overviewOnlyIds.map(id => document.getElementById(id))
    ].filter(Boolean)

    const tabSections = tabIds
        .map(id => document.getElementById(id))
        .filter(Boolean)

    const grids = Array.from(center.querySelectorAll('.erp-project-center-grid'))

    const normalizeTab = hash => {
        const id = (hash || '#overview').replace('#', '')
        return tabIds.includes(id) ? id : 'overview'
    }

    const refreshGrids = () => {
        grids.forEach(grid => {
            const children = Array.from(grid.children)
            grid.hidden = children.length > 0 && !children.some(el => !el.hidden)
        })
    }

    const showTab = tab => {
        const isOverview = tab === 'overview'

        // Globale Projektkarten gehören ausschließlich auf die Übersicht.
        overviewOnly.forEach(el => { el.hidden = !isOverview })

        // Fachbereiche: Übersicht zeigt alle; ein Reiter zeigt nur seinen Bereich.
        tabSections.forEach(el => {
            el.hidden = !isOverview && el.id !== tab
        })

        refreshGrids()

        nav.querySelectorAll('a[href^="#"]').forEach(a => {
            const active = normalizeTab(a.getAttribute('href')) === tab
            a.classList.toggle('is-active', active)
            if (active) a.setAttribute('aria-current', 'page')
            else a.removeAttribute('aria-current')
        })

        const more = nav.querySelector('.erp-project-nav-more')
        if (more) more.removeAttribute('open')
    }

    nav.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', event => {
            event.preventDefault()
            const hash = a.getAttribute('href')
            const tab = normalizeTab(hash)
            history.replaceState(null, '', hash)
            showTab(tab)
        })
    })

    showTab(normalizeTab(window.location.hash))
})
