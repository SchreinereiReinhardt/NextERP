(() => {
  const q=document.getElementById('material-search'), g=document.getElementById('material-group-filter'), supplier=document.getElementById('material-supplier-filter'), stock=document.getElementById('material-stock-filter'), body=document.getElementById('material-table-body'), count=document.getElementById('material-result-count'), empty=document.getElementById('material-empty'), sort=document.getElementById('material-supplier-sort');
  if(!q || !body) return;
  const rows=[...body.querySelectorAll('tr')];
  let supplierSort=0;
  const run=()=>{
    const term=q.value.trim().toLowerCase(), group=g?.value||'', supplierValue=supplier?.value||'', stockValue=stock?.value||''; let n=0;
    rows.forEach(r=>{const show=(!term||r.dataset.search.includes(term))&&(!group||r.dataset.group===group)&&(!supplierValue||r.dataset.supplier===supplierValue)&&(!stockValue||r.dataset.stock===stockValue); r.hidden=!show; if(show)n++;});
    count.textContent=`${n} Treffer`; empty.hidden=n!==0;
  };
  [q,g,supplier,stock].filter(Boolean).forEach(el=>el.addEventListener(el.tagName==='INPUT'?'input':'change',run));
  sort?.addEventListener('click',()=>{
    supplierSort=supplierSort===1?-1:1;
    rows.sort((a,b)=>supplierSort*(a.dataset.supplier||'').localeCompare(b.dataset.supplier||'', 'de', {sensitivity:'base'}));
    rows.forEach(r=>body.appendChild(r));
    sort.textContent=supplierSort===1?'Lieferant ↑':'Lieferant ↓';
    run();
  });
})();
