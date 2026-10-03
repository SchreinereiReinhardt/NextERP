(() => {
  const q = document.getElementById('inventory-search');
  const rows = [...document.querySelectorAll('#inventory-table-body tr')];
  const count = document.getElementById('inventory-result-count');
  const supplierFilter = document.getElementById('inventory-supplier-filter');
  const sortSelect = document.getElementById('inventory-sort');
  const tbody = document.getElementById('inventory-table-body');
  const empty = document.getElementById('inventory-empty');
  let filter = '';
  const run = () => {
    const term = (q?.value || '').trim().toLowerCase();
    let n = 0;
    rows.forEach(r => {
      const supplier = supplierFilter?.value || '';
      const show = (!term || r.dataset.search.includes(term)) && (!filter || r.dataset.stock === filter) && (!supplier || r.dataset.supplierId === supplier);
      r.hidden = !show;
      if (show) n++;
    });
    if (count) count.textContent = `${n} Treffer`;
    if (empty) empty.hidden = n !== 0;
    const mode = sortSelect?.value || 'name';
    const sorted = [...rows].sort((a,b) => {
      if (mode === 'stock-asc' || mode === 'stock-desc') { const d=(parseFloat(a.dataset.stockValue||'0')-parseFloat(b.dataset.stockValue||'0')); return mode==='stock-desc'?-d:d; }
      const key = mode === 'supplier' ? 'supplier' : (mode === 'article' ? 'article' : 'name');
      return (a.dataset[key]||'').localeCompare(b.dataset[key]||'', 'de', {numeric:true, sensitivity:'base'});
    });
    sorted.forEach(r => tbody?.appendChild(r));
  };
  q?.addEventListener('input', run);
  supplierFilter?.addEventListener('change', run);
  sortSelect?.addEventListener('change', run);
  document.querySelectorAll('.inventory-filter').forEach(b => b.addEventListener('click', () => {
    filter = b.dataset.filter || '';
    document.querySelectorAll('.inventory-filter').forEach(x => x.classList.toggle('active', x === b));
    run();
  }));

  const search = document.getElementById('movement-material-search');
  const select = document.getElementById('movement-material-select');
  const type = document.getElementById('movement-type');
  const ek = document.getElementById('movement-purchase-price');
  const markup = document.getElementById('movement-sale-markup');
  const preview = document.getElementById('movement-sale-preview');

  const syncPrice = (loadEk = false) => {
    const opt = select?.selectedOptions?.[0];
    if (loadEk && ek && opt) ek.value = opt.dataset.ek || '';
    const base = parseFloat(ek?.value || '0');
    const pct = parseFloat(markup?.value || '0');
    if (preview) preview.textContent = `Berechneter VK: ${(base * (1 + pct / 100)).toFixed(2).replace('.', ',')} € netto`;
  };
  const syncMovement = () => {
    const isIn = type?.value === 'in';
    document.querySelectorAll('.erp-stock-price-field').forEach(el => el.hidden = !isIn);
    if (ek) ek.disabled = !isIn;
    if (markup) markup.disabled = !isIn;
    if (isIn) syncPrice(true);
  };
  search?.addEventListener('input', () => {
    const t = search.value.trim().toLowerCase();
    [...select.options].forEach(o => o.hidden = !!t && !o.dataset.search.includes(t));
    const first = [...select.options].find(o => !o.hidden);
    if (first) {
      select.value = first.value;
      syncPrice(true);
    }
  });
  select?.addEventListener('change', () => syncPrice(true));
  ek?.addEventListener('input', () => syncPrice(false));
  markup?.addEventListener('input', () => syncPrice(false));
  type?.addEventListener('change', syncMovement);
  syncMovement();
})();

(() => {
  const modal=document.getElementById('datanorm-modal');
  const open=document.getElementById('datanorm-open');
  const close=document.getElementById('datanorm-close');
  const form=document.getElementById('datanorm-form');
  const file=document.getElementById('datanorm-file');
  const supplier=document.getElementById('datanorm-supplier');
  const preview=document.getElementById('datanorm-preview');
  const submit=document.getElementById('datanorm-import');
  const result=document.getElementById('datanorm-result');
  if(!modal||!form)return;
  open?.addEventListener('click',()=>{modal.hidden=false;});
  close?.addEventListener('click',()=>{modal.hidden=true;});
  supplier?.addEventListener('change',()=>{if(submit)submit.disabled=true;if(result){result.hidden=true;result.innerHTML='';}});
  file?.addEventListener('change',()=>{if(submit)submit.disabled=true;if(result){result.hidden=true;result.innerHTML='';}});
  preview?.addEventListener('click',async()=>{
    if(!supplier?.value){if(result){result.hidden=false;result.textContent='Bitte zuerst einen Lieferanten auswählen.';}return;}
    if(!file?.files?.length)return;
    preview.disabled=true;if(submit)submit.disabled=true;
    if(result){result.hidden=false;result.textContent='DATANORM-Datei wird geprüft …';}
    try{
      const fd=new FormData();fd.append('datanorm',file.files[0]);fd.append('supplierId',supplier.value);fd.append('requesttoken',window.OC?.requestToken||'');
      const r=await fetch(form.dataset.previewUrl,{method:'POST',body:fd,credentials:'same-origin',headers:{'requesttoken':window.OC?.requestToken||''}});const data=await r.json();if(!r.ok||!data.ok)throw new Error(data.message||'Datei konnte nicht geprüft werden.');
      const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
      let html=`<div class="erp-datanorm-summary"><strong>${data.count} Artikel erkannt</strong><span>DATANORM ${data.version||''}${data.supplierName?' · '+esc(data.supplierName):''}</span></div>`;
      if(data.warnings?.length)html+=`<div class="erp-warning">${data.warnings.join('<br>')}</div>`;
      html+='<div class="erp-table"><table><thead><tr><th>Artikel</th><th>Bezeichnung</th><th>Einheit</th><th>EK netto</th><th>Preisbasis</th><th>EAN</th><th>Rabattgruppe</th></tr></thead><tbody>';
      (data.items||[]).forEach(i=>{html+=`<tr><td>${esc(i.article_no)}</td><td>${esc(i.name)}</td><td>${esc(i.unit)}</td><td>${Number(i.price||0).toFixed(2).replace('.',',')} €</td><td>${esc(i.price_basis)}</td><td>${esc(i.ean)}</td><td>${esc(i.discount_group)}</td></tr>`;});
      html+='</tbody></table></div>';if(data.count>20)html+=`<p class="erp-muted">Vorschau zeigt die ersten 20 von ${data.count} Artikeln.</p>`;
      if(result)result.innerHTML=html;if(submit)submit.disabled=false;
    }catch(e){if(result)result.textContent=e.message||'DATANORM-Prüfung fehlgeschlagen.';}finally{preview.disabled=false;}
  });
})();
