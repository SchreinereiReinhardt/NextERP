(() => {
  'use strict';
  const setup = (host, rows) => {
    if (!host || host.dataset.betrioPagerReady === '1' || rows.length <= 25) return;
    host.dataset.betrioPagerReady = '1';
    let page = 1;
    const wrap = document.createElement('div');
    wrap.className = 'erp-inventory-pagination erp-auto-pagination';
    wrap.innerHTML = '<label>Pro Seite <select><option value="25" selected>25</option><option value="50">50</option><option value="100">100</option></select></label><div class="erp-muted" data-page-info></div><div class="erp-page-buttons" data-pages aria-label="Seitennavigation"></div>';
    host.insertAdjacentElement('afterend', wrap);
    const size=wrap.querySelector('select'),info=wrap.querySelector('[data-page-info]'),pages=wrap.querySelector('[data-pages]');
    const externallyVisible=r=>!r.hidden&&!r.classList.contains('is-hidden');
    const render=(reset=false)=>{
      if(reset)page=1;
      rows.forEach(r=>r.classList.remove('betrio-page-hidden'));
      const visible=rows.filter(externallyVisible),per=parseInt(size.value||'25',10),total=Math.max(1,Math.ceil(visible.length/per));
      page=Math.min(Math.max(1,page),total);
      visible.forEach((r,i)=>r.classList.toggle('betrio-page-hidden',i<(page-1)*per||i>=page*per));
      const from=visible.length?(page-1)*per+1:0,to=Math.min(page*per,visible.length);info.textContent=`${from}–${to} von ${visible.length}`;pages.replaceChildren();
      if(total<=1)return;
      const button=(label,target,disabled=false,active=false)=>{const b=document.createElement('button');b.type='button';b.className='button'+(active?' primary':'');b.textContent=label;b.disabled=disabled;b.addEventListener('click',()=>{page=target;render();host.scrollIntoView({block:'start',behavior:'smooth'});});pages.appendChild(b);};
      button('‹',page-1,page===1);const start=Math.max(1,Math.min(page-2,total-4)),end=Math.min(total,start+4);for(let i=start;i<=end;i++)button(String(i),i,false,i===page);button('›',page+1,page===total);
    };
    size.addEventListener('change',()=>render(true));
    const scope=host.closest('section')||host.parentElement||document;
    scope.addEventListener('input',()=>setTimeout(()=>render(true),0));scope.addEventListener('change',e=>{if(e.target!==size)setTimeout(()=>render(true),0);});render();
  };
  const style=document.createElement('style');style.textContent='.betrio-page-hidden{display:none!important}.erp-auto-pagination{margin-top:12px}';document.head.appendChild(style);
  document.querySelectorAll('tbody[data-betrio-auto-page]').forEach(body=>setup(body.closest('.erp-table')||body.closest('table')||body,[...body.querySelectorAll(':scope > tr')]));
  document.querySelectorAll('[data-betrio-page-container]').forEach(host=>{const sel=host.dataset.betrioPageContainer;setup(host,[...host.querySelectorAll(sel)]);});
})();
