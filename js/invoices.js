document.addEventListener('DOMContentLoaded',()=>{
 const form=document.getElementById('invoiceCreateForm');
 if(form){const items=document.getElementById('invoiceItems'),add=document.getElementById('invoiceAddItem'),vat=document.getElementById('invoiceVatRate'),net=document.getElementById('invoiceNet'),tax=document.getElementById('invoiceVat'),gross=document.getElementById('invoiceGross');const money=value=>new Intl.NumberFormat('de-DE',{style:'currency',currency:'EUR'}).format(value||0);const renumber=()=>[...items.querySelectorAll('[data-invoice-item]')].forEach((row,i)=>{const pos=row.querySelector('[data-position]');if(pos)pos.textContent=String(i+1);});const calculate=()=>{let sum=0;items.querySelectorAll('[data-invoice-item]').forEach(row=>{const q=parseFloat(row.querySelector('[data-qty]')?.value||'0')||0,p=parseFloat(row.querySelector('[data-price]')?.value||'0')||0,line=q*p,alt=!!row.querySelector('[data-alternative]')?.checked;if(!alt)sum+=line;const target=row.querySelector('[data-line-total]');if(target)target.textContent=money(line);});const rate=parseFloat(vat?.value||'0')||0,vatValue=sum*rate/100;if(net)net.textContent=money(sum);if(tax)tax.textContent=money(vatValue);if(gross)gross.textContent=money(sum+vatValue);};const bind=row=>{const file=row.querySelector('input[type=file]');file?.addEventListener('change',()=>{const n=row.querySelector('[data-image-name]');if(n)n.textContent=file.files?.[0]?.name||'';});row.querySelectorAll('input,textarea').forEach(el=>{el.addEventListener('input',calculate);el.addEventListener('change',calculate);});row.querySelector('[data-remove-item]')?.addEventListener('click',()=>{if(items.querySelectorAll('[data-invoice-item]').length<=1)return;row.remove();renumber();calculate();});};items.querySelectorAll('[data-invoice-item]').forEach(bind);const resetRichText=row=>{row.querySelectorAll('.erp-rte').forEach(wrap=>{const textarea=wrap.querySelector('textarea');if(!textarea)return;textarea.value='';textarea.hidden=false;textarea.classList.remove('erp-rte-source');wrap.replaceWith(textarea);});};add?.addEventListener('click',()=>{const first=items.querySelector('[data-invoice-item]'),row=first.cloneNode(true);resetRichText(row);row.querySelectorAll('input,textarea').forEach(el=>{if(el.matches('[name="quantities[]"]'))el.value='1';else if(el.matches('[name="units[]"]'))el.value='Stk.';else if(el.matches('[name="unitPrices[]"]'))el.value='0';else if(el.type==='checkbox')el.checked=false;else if(el.type==='hidden'&&el.name==='keepImages[]')el.value='0';else el.value='';});const file=row.querySelector('input[type=file]');if(file)file.value='';const imageName=row.querySelector('[data-image-name]');if(imageName)imageName.textContent='';row.querySelector('.erp-image-existing')?.remove();items.appendChild(row);window.BetrioInitRichText?.(row);bind(row);renumber();calculate();row.querySelector('.erp-rte-editor')?.focus();});vat?.addEventListener('input',calculate);calculate();}
 const search=document.getElementById('invoiceSearch'),year=document.getElementById('invoiceYear'),month=document.getElementById('invoiceMonth'),rows=[...document.querySelectorAll('#invoiceTableBody [data-doc-row]')],visibleCount=document.getElementById('invoiceVisibleCount'),noResults=document.getElementById('invoiceNoResults');if(rows.length||search){const filterRows=()=>{const q=(search?.value||'').trim().toLocaleLowerCase('de-DE');let visible=0;rows.forEach(row=>{const matches=(!q||(row.dataset.search||'').includes(q))&&(!year?.value||row.dataset.year===year.value)&&(!month?.value||row.dataset.month===month.value);row.hidden=!matches;if(matches)visible++;});if(visibleCount)visibleCount.textContent=String(visible);if(noResults)noResults.hidden=visible!==0||rows.length===0;};search?.addEventListener('input',filterRows);year?.addEventListener('change',filterRows);month?.addEventListener('change',filterRows);document.getElementById('invoiceShowAll')?.addEventListener('click',()=>{if(search)search.value='';if(year)year.value='';if(month)month.value='';filterRows();});}
});

// Betrio 2.3.14: nur Projekte des ausgewaehlten Kunden anzeigen
document.addEventListener('DOMContentLoaded',()=>{
 const customer=document.getElementById('invoiceCustomerId'), project=document.getElementById('invoiceProjectId');
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

// Betrio 2.3.15: Steuerart und Zahlungsbedingungen
 document.addEventListener('DOMContentLoaded',()=>{
 const taxMode=document.getElementById('invoiceTaxMode'),vat=document.getElementById('invoiceVatRate'),vatWrap=document.getElementById('invoiceCustomVatWrap'),hint=document.getElementById('invoiceTaxHint');
 const payment=document.getElementById('invoicePaymentTerm'),notes=document.getElementById('invoiceNotes'),due=document.getElementById('invoiceDueDate');
 const applyTax=()=>{if(!taxMode||!vat)return;const mode=taxMode.value;let rate=19,text='';if(mode==='standard7')rate=7;else if(mode==='small_business'){rate=0;text='Keine Umsatzsteuer: Steuerbefreiung für Kleinunternehmer gemäß § 19 UStG.';}else if(mode==='reverse_charge_13b'){rate=0;text='Keine Umsatzsteuer: Steuerschuldnerschaft des Leistungsempfängers gemäß § 13b UStG. Nur wählen, wenn die Voraussetzungen tatsächlich vorliegen.';}else if(mode==='custom'){rate=parseFloat(vat.value||'0')||0;text='Individuellen Steuersatz prüfen.';}vat.value=String(rate);if(vatWrap)vatWrap.hidden=mode!=='custom';if(hint)hint.textContent=text;vat.dispatchEvent(new Event('input',{bubbles:true}));};
 const applyPayment=()=>{if(!payment||!payment.value)return;const opt=payment.selectedOptions[0];if(notes&&opt?.dataset.text)notes.value=opt.dataset.text||'';if(due){const base=document.querySelector('input[name="invoiceDate"]')?.value;if(base){const d=new Date(base+'T12:00:00');d.setDate(d.getDate()+(parseInt(opt?.dataset.days||'0',10)||0));due.value=d.toISOString().slice(0,10);}}};
 taxMode?.addEventListener('change',applyTax);payment?.addEventListener('change',applyPayment);document.querySelector('input[name="invoiceDate"]')?.addEventListener('change',()=>{if(payment?.value)applyPayment();});applyTax();if(payment?.value&&notes&&!notes.value.trim())applyPayment();
});
