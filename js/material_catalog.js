(() => {
  const q=document.getElementById('material-search'), g=document.getElementById('material-group-filter'), supplier=document.getElementById('material-supplier-filter'), stock=document.getElementById('material-stock-filter'), body=document.getElementById('material-table-body'), count=document.getElementById('material-result-count'), empty=document.getElementById('material-empty'), sort=document.getElementById('material-supplier-sort');
  const pageSizeSelect=document.getElementById('material-page-size'), pageInfo=document.getElementById('material-page-info'), pages=document.getElementById('material-pages');
  if(!q || !body) return;
  const rows=[...body.querySelectorAll('tr')];
  let supplierSort=0, currentPage=1;
  const renderPages=(visibleRows)=>{
    const pageSize=parseInt(pageSizeSelect?.value||'25',10);
    const totalPages=Math.max(1,Math.ceil(visibleRows.length/pageSize));
    currentPage=Math.min(currentPage,totalPages);
    visibleRows.forEach((r,i)=>{const hide=i<(currentPage-1)*pageSize||i>=currentPage*pageSize;r.hidden=hide;r.classList.toggle('is-hidden',hide);});
    if(pageInfo){const from=visibleRows.length?(currentPage-1)*pageSize+1:0;const to=Math.min(currentPage*pageSize,visibleRows.length);pageInfo.textContent=`${from}–${to} von ${visibleRows.length}`;}
    if(pages){
      pages.innerHTML='';
      const add=(label,page,active=false,disabled=false)=>{const b=document.createElement('button');b.type='button';b.className='button'+(active?' primary':'');b.textContent=label;b.disabled=disabled;b.addEventListener('click',()=>{currentPage=page;run(false);});pages.appendChild(b);};
      add('‹',Math.max(1,currentPage-1),false,currentPage===1);
      const candidates=[];for(let i=1;i<=totalPages;i++){if(i===1||i===totalPages||Math.abs(i-currentPage)<=2)candidates.push(i);}
      let prev=0;candidates.forEach(i=>{if(prev&&i-prev>1){const span=document.createElement('span');span.textContent='…';pages.appendChild(span);}add(String(i),i,i===currentPage);prev=i;});
      add('›',Math.min(totalPages,currentPage+1),false,currentPage===totalPages);
    }
  };
  const run=(resetPage=true)=>{
    if(resetPage)currentPage=1;
    const term=q.value.trim().toLowerCase(), group=g?.value||'', supplierValue=supplier?.value||'', stockValue=stock?.value||'';
    const visibleRows=[];
    rows.forEach(r=>{const show=(!term||r.dataset.search.includes(term))&&(!group||r.dataset.group===group)&&(!supplierValue||r.dataset.supplier===supplierValue)&&(!stockValue||r.dataset.stock===stockValue);r.hidden=!show;r.classList.toggle('is-hidden',!show);if(show)visibleRows.push(r);});
    if(count)count.textContent=`${visibleRows.length} Treffer`;
    if(empty)empty.hidden=visibleRows.length!==0;
    renderPages(visibleRows);
  };
  [q,g,supplier,stock].filter(Boolean).forEach(el=>el.addEventListener(el.tagName==='INPUT'?'input':'change',()=>run(true)));
  pageSizeSelect?.addEventListener('change',()=>run(true));
  sort?.addEventListener('click',()=>{
    supplierSort=supplierSort===1?-1:1;
    rows.sort((a,b)=>supplierSort*(a.dataset.supplier||'').localeCompare(b.dataset.supplier||'','de',{sensitivity:'base'}));
    rows.forEach(r=>body.appendChild(r));
    sort.textContent=supplierSort===1?'Lieferant ↑':'Lieferant ↓';
    run(true);
  });
  run(true);
})();
