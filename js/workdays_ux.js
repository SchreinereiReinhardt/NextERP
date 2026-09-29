document.addEventListener('DOMContentLoaded',()=>{
 const buttons=[...document.querySelectorAll('[data-work-mode]')],panels=[...document.querySelectorAll('[data-work-panel]')];
 buttons.forEach(b=>b.addEventListener('click',()=>{const mode=b.dataset.workMode;buttons.forEach(x=>{x.classList.toggle('is-active',x===b);x.classList.toggle('primary',x===b)});panels.forEach(p=>p.classList.toggle('is-active',p.dataset.workPanel===mode));}));
 document.querySelectorAll('[data-project-search]').forEach(input=>{
  const wrap=input.closest('.erp-project-picker'); const select=wrap?.querySelector('[data-project-select]'); if(!select)return;
  const options=[...select.options].slice(1);
  const filter=()=>{const q=input.value.trim().toLocaleLowerCase('de-DE'); let visible=0; options.forEach(o=>{const hit=!q||(o.dataset.search||o.textContent||'').toLocaleLowerCase('de-DE').includes(q);o.hidden=!hit;o.disabled=!hit;if(hit)visible++;}); if(select.selectedOptions[0]?.disabled)select.value=''; select.size=q?Math.min(Math.max(visible+1,2),8):1;};
  input.addEventListener('input',filter); input.addEventListener('search',filter); select.addEventListener('change',()=>{select.size=1;}); input.addEventListener('blur',()=>setTimeout(()=>{select.size=1;},150));
 });
 const manual=document.querySelector('[data-work-panel="manual"] form');
 if(manual){const start=manual.querySelector('[name="startTime"]'),end=manual.querySelector('[name="endTime"]'),pause=manual.querySelector('[name="breakMinutes"]'),hours=manual.querySelector('[name="hours"]'),out=manual.querySelector('[data-manual-time-result] strong'); const calc=()=>{if(!out||!start?.value||!end?.value){if(out)out.textContent='0,00 h';return;}const mins=v=>{const [h,m]=v.split(':').map(Number);return h*60+m};let d=mins(end.value)-mins(start.value);if(d<0)d+=1440;d=Math.max(0,d-(Number(pause?.value)||0));const h=d/60;out.textContent=h.toLocaleString('de-DE',{minimumFractionDigits:2,maximumFractionDigits:2})+' h';if(hours&&Number(hours.value)===0)hours.dataset.autoHours=h.toFixed(2);};[start,end,pause].forEach(el=>el?.addEventListener('input',calc));calc();}
});
