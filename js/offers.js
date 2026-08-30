document.addEventListener('DOMContentLoaded',()=>{
 const list=document.getElementById('offerItems');const add=document.getElementById('offerAddItem');const vatInput=document.getElementById('offerVatRate');
 if(list&&add){
  const money=new Intl.NumberFormat('de-DE',{style:'currency',currency:'EUR'});const number=v=>{const n=parseFloat(String(v??'').replace(',','.'));return Number.isFinite(n)?n:0;};
  const refresh=()=>{const rows=[...list.querySelectorAll('[data-offer-item]')];let net=0;rows.forEach((row,i)=>{row.querySelector('[data-position]').textContent=String(i+1);const total=Math.max(0,number(row.querySelector('[data-qty]')?.value))*Math.max(0,number(row.querySelector('[data-price]')?.value));if(!row.querySelector('[data-alternative]')?.checked)net+=total;const out=row.querySelector('[data-line-total]');if(out)out.textContent=money.format(total);const remove=row.querySelector('[data-remove-item]');if(remove)remove.disabled=rows.length===1;});const vat=Math.max(0,Math.min(100,number(vatInput?.value)));const tax=net*vat/100;document.getElementById('offerNet').textContent=money.format(net);document.getElementById('offerVat').textContent=money.format(tax);document.getElementById('offerGross').textContent=money.format(net+tax);};
  const bind=row=>{const file=row.querySelector('input[type=file]');file?.addEventListener('change',()=>{const n=row.querySelector('[data-image-name]');if(n)n.textContent=file.files?.[0]?.name||'';});row.querySelectorAll('input,textarea').forEach(el=>{el.addEventListener('input',refresh);el.addEventListener('change',refresh);});row.querySelector('[data-remove-item]')?.addEventListener('click',()=>{if(list.querySelectorAll('[data-offer-item]').length>1){row.remove();refresh();}});};
  const resetRichText=row=>{row.querySelectorAll('.erp-rte').forEach(wrap=>{const textarea=wrap.querySelector('textarea');if(!textarea)return;textarea.value='';textarea.hidden=false;textarea.classList.remove('erp-rte-source');wrap.replaceWith(textarea);});};
  add.addEventListener('click',()=>{const source=list.querySelector('[data-offer-item]');const row=source.cloneNode(true);resetRichText(row);row.querySelector('textarea[name="descriptions[]"]').value='';row.querySelector('input[name="quantities[]"]').value='1';row.querySelector('input[name="units[]"]').value='Stk.';row.querySelector('input[name="unitPrices[]"]').value='0';const alt=row.querySelector('[data-alternative]');if(alt)alt.checked=false;const keep=row.querySelector('input[name="keepImages[]"]');if(keep)keep.value='0';const file=row.querySelector('input[type=file]');if(file)file.value='';const imageName=row.querySelector('[data-image-name]');if(imageName)imageName.textContent='';row.querySelector('.erp-image-existing')?.remove();list.appendChild(row);window.BetrioInitRichText?.(row);bind(row);refresh();row.querySelector('.erp-rte-editor')?.focus();});vatInput?.addEventListener('input',refresh);[...list.querySelectorAll('[data-offer-item]')].forEach(bind);refresh();
 }
 const search=document.getElementById('offerSearch'),year=document.getElementById('offerYear'),month=document.getElementById('offerMonth');const rows=[...document.querySelectorAll('#offerTableBody [data-doc-row]')];const visibleCount=document.getElementById('offerVisibleCount'),noResults=document.getElementById('offerNoResults');
 if(rows.length||search){const filterRows=()=>{const q=(search?.value||'').trim().toLocaleLowerCase('de-DE');let visible=0;rows.forEach(row=>{const matches=(!q||(row.dataset.search||'').includes(q))&&(!year?.value||row.dataset.year===year.value)&&(!month?.value||row.dataset.month===month.value);row.hidden=!matches;if(matches)visible++;});if(visibleCount)visibleCount.textContent=String(visible);if(noResults)noResults.hidden=visible!==0||rows.length===0;};search?.addEventListener('input',filterRows);year?.addEventListener('change',filterRows);month?.addEventListener('change',filterRows);document.getElementById('offerShowAll')?.addEventListener('click',()=>{if(search)search.value='';if(year)year.value='';if(month)month.value='';filterRows();});}
});

// Betrio 2.3.14: nur Projekte des ausgewaehlten Kunden anzeigen
document.addEventListener('DOMContentLoaded',()=>{
 const customer=document.getElementById('offerCustomerId'), project=document.getElementById('offerProjectId');
 if(!customer||!project)return;
 const options=[...project.options];
 const filterProjects=()=>{
  const customerId=String(customer.value||'');
  const selected=String(project.value||'');
  let selectedStillValid=selected==='';
  options.forEach((option,index)=>{
   if(index===0){option.hidden=false;option.disabled=false;return;}
   const matches=String(option.dataset.customerId||'')===customerId;
   option.hidden=!matches;option.disabled=!matches;
   if(matches&&String(option.value)===selected)selectedStillValid=true;
  });
  if(!selectedStillValid)project.value='';
 };
 customer.addEventListener('change',filterProjects);filterProjects();
});
