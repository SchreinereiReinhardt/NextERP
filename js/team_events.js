document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('teamEventForm')
    const start = document.getElementById('teamEventStart')
    const end = document.getElementById('teamEventEnd')
    const error = document.getElementById('teamEventError')
    if (!form || !start || !end) return

    let endWasEdited = false
    const pad = number => String(number).padStart(2, '0')
    const localValue = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
    const setDefaultEnd = () => {
        if (!start.value || endWasEdited) return
        const date = new Date(start.value)
        if (Number.isNaN(date.getTime())) return
        date.setHours(date.getHours() + 1)
        end.value = localValue(date)
    }
    start.addEventListener('change', setDefaultEnd)
    end.addEventListener('input', () => { endWasEdited = true })
    form.addEventListener('submit', event => {
        if (!end.value) setDefaultEnd()
        const startDate = new Date(start.value)
        const endDate = new Date(end.value)
        if (!start.value || !end.value || Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime()) || endDate <= startDate) {
            event.preventDefault()
            error.hidden = false
            error.textContent = 'Die Endzeit muss nach der Startzeit liegen.'
            end.focus()
            return
        }
        error.hidden = true
    })
})
