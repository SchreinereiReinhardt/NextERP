document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('teamEventForm')
    const start = document.getElementById('teamEventStart')
    const end = document.getElementById('teamEventEnd')
    const allDay = document.getElementById('teamEventAllDay')
    const error = document.getElementById('teamEventError')
    const customer = document.getElementById('teamEventCustomer')
    const project = document.getElementById('teamEventProject')
    if (!form || !start || !end) return

    let endWasEdited = false
    let savedTimes = null
    const pad = n => String(n).padStart(2, '0')
    const localValue = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
    const roundNext = () => { const d=new Date(); d.setSeconds(0,0); d.setMinutes(Math.ceil(d.getMinutes()/15)*15); return d }
    const setDefaultTimes = () => { if(start.value) return; const d=roundNext(); start.value=localValue(d); d.setHours(d.getHours()+1); end.value=localValue(d) }
    const setDefaultEnd = () => { if(!start.value||endWasEdited)return; const d=new Date(start.value); if(Number.isNaN(d.getTime()))return; d.setHours(d.getHours()+1); end.value=localValue(d) }
    const syncCustomerFromProject = () => { if(!project?.value||!customer)return; const cid=project.options[project.selectedIndex]?.dataset?.customerId||''; if(cid)customer.value=cid }
    const filterProjects = () => { if(!project||!customer)return; const cid=customer.value; Array.from(project.options).forEach((o,i)=>{if(i===0)return;o.hidden=!!cid&&o.dataset.customerId!==cid&&o.value!==project.value}); if(project.value&&project.options[project.selectedIndex]?.hidden)project.value='' }
    const toggleAllDay = () => {
        if(!allDay) return
        if(allDay.checked){ savedTimes={start:start.value,end:end.value}; const base=start.value?new Date(start.value):new Date(); const a=new Date(base);a.setHours(0,0,0,0);const b=new Date(base);b.setHours(23,59,0,0);start.value=localValue(a);end.value=localValue(b);start.classList.add('is-all-day');end.classList.add('is-all-day') }
        else if(savedTimes){start.value=savedTimes.start;end.value=savedTimes.end;start.classList.remove('is-all-day');end.classList.remove('is-all-day')}
    }
    setDefaultTimes(); syncCustomerFromProject(); filterProjects()
    start.addEventListener('change',()=>{setDefaultEnd();if(allDay?.checked)toggleAllDay()})
    end.addEventListener('input',()=>{endWasEdited=true})
    allDay?.addEventListener('change',toggleAllDay)
    project?.addEventListener('change',()=>{syncCustomerFromProject();filterProjects()})
    customer?.addEventListener('change',filterProjects)
    form.addEventListener('submit', e => { if(!end.value)setDefaultEnd(); const a=new Date(start.value),b=new Date(end.value); if(!start.value||!end.value||Number.isNaN(a.getTime())||Number.isNaN(b.getTime())||b<=a){e.preventDefault();if(error){error.hidden=false;error.textContent='Die Endzeit muss nach der Startzeit liegen.'}end.focus()}else if(error)error.hidden=true })
})
