(function(){
'use strict';
function norm(v){return (v||'').toLocaleLowerCase('de-DE').normalize('NFD').replace(/[\u0300-\u036f]/g,'');}
function token(){return (window.OC&&OC.requestToken)||document.querySelector('input[name="requesttoken"]')?.value||'';}
function quickDialog(select,input,opts,close){
 var old=document.getElementById('erpQuickCustomerDialog'); if(old)old.remove();
 var overlay=document.createElement('div'); overlay.id='erpQuickCustomerDialog'; overlay.className='erp-quick-customer-overlay';
 overlay.innerHTML='<div class="erp-quick-customer-dialog" role="dialog" aria-modal="true" aria-labelledby="erpQuickCustomerTitle"><div class="erp-section-head"><div><span class="erp-eyebrow">KUNDEN</span><h2 id="erpQuickCustomerTitle">Neuen Kunden anlegen</h2><p class="erp-muted">Der Kunde wird direkt in Betrio gespeichert und für dieses Dokument ausgewählt.</p></div><button type="button" class="erp-icon-button" data-qc-close aria-label="Schließen">×</button></div><div class="erp-form-grid"><div><label>Firma / Kundenname *</label><input data-qc="name" required></div><div><label>Ansprechpartner</label><input data-qc="contactName"></div><div><label>Telefon</label><input data-qc="phone" inputmode="tel"></div><div><label>E-Mail</label><input data-qc="email" type="email"></div><div><label>Straße und Hausnummer</label><input data-qc="street"></div><div><label>PLZ</label><input data-qc="postalCode"></div><div><label>Ort</label><input data-qc="city"></div><div><label>Land</label><input data-qc="country" value="Deutschland"></div></div><div class="erp-quick-customer-error" hidden></div><div class="erp-actions"><button type="button" class="button" data-qc-close>Abbrechen</button><button type="button" class="button primary" data-qc-save>Kunde speichern & auswählen</button></div></div>';
 document.body.appendChild(overlay);
 var name=overlay.querySelector('[data-qc="name"]'); name.value=input.value.trim(); name.focus(); name.select();
 function dismiss(){overlay.remove();}
 overlay.querySelectorAll('[data-qc-close]').forEach(function(b){b.addEventListener('click',dismiss);});
 overlay.addEventListener('mousedown',function(e){if(e.target===overlay)dismiss();});
 overlay.addEventListener('keydown',function(e){if(e.key==='Escape')dismiss();});
 overlay.querySelector('[data-qc-save]').addEventListener('click',async function(){
   var save=this, err=overlay.querySelector('.erp-quick-customer-error'), data=new URLSearchParams();
   overlay.querySelectorAll('[data-qc]').forEach(function(el){data.append(el.dataset.qc,el.value.trim());}); data.append('requesttoken',token());
   if(!data.get('name')){err.textContent='Firma / Kundenname ist Pflicht.';err.hidden=false;name.focus();return;}
   save.disabled=true; save.textContent='Speichert …'; err.hidden=true;
   try{
     var r=await fetch(select.dataset.quickCreateUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','requesttoken':token()},body:data.toString(),credentials:'same-origin'});
     var j=await r.json(); if(!r.ok||!j.ok)throw new Error(j.message||'Kunde konnte nicht gespeichert werden.');
     var o=document.createElement('option');o.value=String(j.customer.id);o.textContent=(j.customer.customerNo?j.customer.customerNo+' · ':'')+j.customer.name;o.selected=true;select.appendChild(o);opts.push(o);input.value=o.textContent;select.dispatchEvent(new Event('change',{bubbles:true}));dismiss();close();
   }catch(e){err.textContent=e.message||'Kunde konnte nicht gespeichert werden.';err.hidden=false;save.disabled=false;save.textContent='Kunde speichern & auswählen';}
 });
}
function enhance(select){
 if(!select || select.dataset.erpCustomerEnhanced==='1') return;
 select.dataset.erpCustomerEnhanced='1';
 var wrap=document.createElement('div'); wrap.className='erp-customer-combobox';
 var row=document.createElement('div');row.className='erp-customer-search-row';
 var input=document.createElement('input'); input.type='search'; input.className='erp-customer-search'; input.autocomplete='off'; input.spellcheck=false; input.placeholder='Kunde suchen …'; input.setAttribute('aria-label','Kunde suchen');
 var toggle=document.createElement('button');toggle.type='button';toggle.className='erp-customer-toggle';toggle.setAttribute('aria-label','Kundenliste öffnen');toggle.textContent='⌄';
 var list=document.createElement('div'); list.className='erp-customer-results'; list.hidden=true; list.setAttribute('role','listbox');
 select.parentNode.insertBefore(wrap,select); wrap.appendChild(row);row.appendChild(input);row.appendChild(toggle); wrap.appendChild(list); wrap.appendChild(select); select.classList.add('erp-customer-native');
 var opts=Array.prototype.slice.call(select.options), active=-1;
 function selectedText(){var o=select.options[select.selectedIndex]; return o ? o.text.trim() : '';}
 input.value=selectedText();
 function close(){list.hidden=true; active=-1;}
 function choose(opt){select.value=opt.value; input.value=opt.text.trim(); close(); select.dispatchEvent(new Event('change',{bubbles:true}));}
 function render(forceAll){
   var q=forceAll?'':norm(input.value.trim()), matches=opts.filter(function(o){return !q || norm(o.text).indexOf(q)!==-1;}).slice(0,40);
   list.innerHTML=''; active=-1;
   matches.forEach(function(o){var b=document.createElement('button');b.type='button';b.className='erp-customer-option';b.setAttribute('role','option');b.textContent=o.text.trim();b.addEventListener('mousedown',function(e){e.preventDefault();choose(o);});list.appendChild(b);});
   if(!matches.length){var empty=document.createElement('div');empty.className='erp-customer-empty';empty.textContent='Kein Kunde gefunden';list.appendChild(empty);}
   if(select.dataset.quickCreateUrl){var add=document.createElement('button');add.type='button';add.className='erp-customer-option erp-customer-add';add.textContent='+ Neuen Kunden anlegen';add.addEventListener('mousedown',function(e){e.preventDefault();close();quickDialog(select,input,opts,close);});list.appendChild(add);}
   list.hidden=false;
 }
 input.addEventListener('focus',function(){input.select();render(false);}); input.addEventListener('input',function(){render(false);});
 toggle.addEventListener('click',function(){if(list.hidden){input.focus();render(true);}else close();});
 input.addEventListener('keydown',function(e){var items=list.querySelectorAll('.erp-customer-option:not(.erp-customer-add)');if(e.key==='ArrowDown'||e.key==='ArrowUp'){e.preventDefault();if(list.hidden)render(false);items=list.querySelectorAll('.erp-customer-option:not(.erp-customer-add)');if(!items.length)return;active=e.key==='ArrowDown'?Math.min(active+1,items.length-1):Math.max(active-1,0);items.forEach(function(x,i){x.classList.toggle('is-active',i===active);});items[active].scrollIntoView({block:'nearest'});}else if(e.key==='Enter'&&!list.hidden){if(active>=0&&items[active]){e.preventDefault();items[active].dispatchEvent(new MouseEvent('mousedown',{bubbles:true}));}else if(items.length===1){e.preventDefault();items[0].dispatchEvent(new MouseEvent('mousedown',{bubbles:true}));}}else if(e.key==='Escape'){close();input.value=selectedText();}});
 select.addEventListener('change',function(){input.value=selectedText();}); document.addEventListener('mousedown',function(e){if(!wrap.contains(e.target)){close();input.value=selectedText();}});
}
function init(root){(root||document).querySelectorAll('select.erp-customer-select').forEach(enhance);} if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){init(document);});else init(document);
})();
