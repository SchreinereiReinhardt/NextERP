(() => {
  const q = document.getElementById('inventory-search');
  const rows = [...document.querySelectorAll('#inventory-table-body tr')];
  const count = document.getElementById('inventory-result-count');
  const supplierFilter = document.getElementById('inventory-supplier-filter');
  const sortSelect = document.getElementById('inventory-sort');
  const tbody = document.getElementById('inventory-table-body');
  const empty = document.getElementById('inventory-empty');
  let filter = '';
  let currentPage = 1;
  const pageSizeSelect = document.getElementById('inventory-page-size');
  const pageInfo = document.getElementById('inventory-page-info');
  const pages = document.getElementById('inventory-pages');
  const renderPages = (visibleRows) => {
    const pageSize = parseInt(pageSizeSelect?.value || '25', 10);
    const totalPages = Math.max(1, Math.ceil(visibleRows.length / pageSize));
    currentPage = Math.min(currentPage, totalPages);
    visibleRows.forEach((r, i) => { const hide = i < (currentPage - 1) * pageSize || i >= currentPage * pageSize; r.hidden = hide; r.classList.toggle('is-hidden', hide); });
    if (pageInfo) {
      const from = visibleRows.length ? (currentPage - 1) * pageSize + 1 : 0;
      const to = Math.min(currentPage * pageSize, visibleRows.length);
      pageInfo.textContent = `${from}–${to} von ${visibleRows.length}`;
    }
    if (pages) {
      pages.innerHTML = '';
      const add = (label, page, active = false, disabled = false) => {
        const b = document.createElement('button'); b.type='button'; b.className='button' + (active ? ' primary' : ''); b.textContent=label; b.disabled=disabled;
        b.addEventListener('click',()=>{ currentPage=page; run(false); }); pages.appendChild(b);
      };
      add('‹', Math.max(1,currentPage-1), false, currentPage===1);
      const candidates=[]; for(let i=1;i<=totalPages;i++){ if(i===1||i===totalPages||Math.abs(i-currentPage)<=2)candidates.push(i); }
      let prev=0; candidates.forEach(i=>{ if(prev && i-prev>1){const span=document.createElement('span');span.textContent='…';pages.appendChild(span);} add(String(i),i,i===currentPage);prev=i; });
      add('›', Math.min(totalPages,currentPage+1), false, currentPage===totalPages);
    }
  };
  const run = (resetPage = true) => {
    const term = (q?.value || '').trim().toLowerCase();
    if (resetPage) currentPage = 1;
    const visibleRows = [];
    rows.forEach(r => {
      const supplier = supplierFilter?.value || '';
      const show = (!term || r.dataset.search.includes(term)) && (!filter || r.dataset.stock === filter) && (!supplier || r.dataset.supplierId === supplier);
      r.hidden = !show;
      r.classList.toggle('is-hidden', !show);
      if (show) visibleRows.push(r);
    });
    if (count) count.textContent = `${visibleRows.length} Treffer`;
    if (empty) empty.hidden = visibleRows.length !== 0;
    const mode = sortSelect?.value || 'name';
    const sorted = [...rows].sort((a,b) => {
      if (mode === 'stock-asc' || mode === 'stock-desc') { const d=(parseFloat(a.dataset.stockValue||'0')-parseFloat(b.dataset.stockValue||'0')); return mode==='stock-desc'?-d:d; }
      const key = mode === 'supplier' ? 'supplier' : (mode === 'article' ? 'article' : 'name');
      return (a.dataset[key]||'').localeCompare(b.dataset[key]||'', 'de', {numeric:true, sensitivity:'base'});
    });
    sorted.forEach(r => tbody?.appendChild(r));
    const orderedVisible = sorted.filter(r => visibleRows.includes(r));
    renderPages(orderedVisible);
  };
  q?.addEventListener('input', run);
  supplierFilter?.addEventListener('change', run);
  sortSelect?.addEventListener('change', run);
  pageSizeSelect?.addEventListener('change', () => run(true));
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
  const destination = document.getElementById('movement-destination');
  const customer = document.getElementById('movement-customer');
  const project = document.getElementById('movement-project');
  const destinationHint = document.getElementById('movement-destination-hint');

  const syncPrice = (loadEk = false) => {
    const opt = select?.selectedOptions?.[0];
    if (loadEk && ek && opt) ek.value = opt.dataset.ek || '';
    const base = parseFloat(ek?.value || '0');
    const pct = parseFloat(markup?.value || '0');
    if (preview) preview.textContent = `Berechneter VK: ${(base * (1 + pct / 100)).toFixed(2).replace('.', ',')} € netto`;
  };
  const syncDestination = () => {
    const isIn = type?.value === 'in';
    const direct = isIn && destination?.value === 'project';
    document.querySelectorAll('.erp-stock-destination-field').forEach(el => el.hidden = !isIn);
    const projectMovement = ['out','return'].includes(type?.value || '');
    document.querySelectorAll('.erp-stock-customer-field,.erp-stock-project-field').forEach(el => el.hidden = !direct && !projectMovement);
    if (destinationHint) destinationHint.hidden = !direct;
    if (project) project.required = direct || projectMovement;
  };
  const syncMovement = () => {
    const isIn = type?.value === 'in';
    document.querySelectorAll('.erp-stock-price-field').forEach(el => el.hidden = !isIn);
    if (ek) ek.disabled = !isIn;
    if (markup) markup.disabled = !isIn;
    if (isIn) syncPrice(true);
    syncDestination();
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
  destination?.addEventListener('change', syncDestination);
  customer?.addEventListener('change',()=>{if(!project)return;const cid=customer.value;[...project.options].forEach(o=>{if(!o.value)return;o.hidden=!!cid&&o.dataset.customerId!==cid;});if(project.selectedOptions[0]?.hidden)project.value='';});
  project?.addEventListener('change',()=>{const cid=project.selectedOptions[0]?.dataset.customerId||'';if(cid&&customer)customer.value=cid;});
  syncMovement();
  run(true);
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
