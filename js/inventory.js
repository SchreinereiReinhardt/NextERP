(() => {
  const q = document.getElementById('inventory-search');
  const rows = [...document.querySelectorAll('#inventory-table-body tr')];
  const count = document.getElementById('inventory-result-count');
  const empty = document.getElementById('inventory-empty');
  let filter = '';
  const run = () => {
    const term = (q?.value || '').trim().toLowerCase();
    let n = 0;
    rows.forEach(r => {
      const show = (!term || r.dataset.search.includes(term)) && (!filter || r.dataset.stock === filter);
      r.hidden = !show;
      if (show) n++;
    });
    if (count) count.textContent = `${n} Treffer`;
    if (empty) empty.hidden = n !== 0;
  };
  q?.addEventListener('input', run);
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
