document.addEventListener('DOMContentLoaded',()=>{
 const box=document.getElementById('erpWhatsNew'); if(!box)return;
 let busy=false;
 const dismiss=async()=>{if(busy)return;busy=true;try{const body=new URLSearchParams({version:box.dataset.version});const r=await fetch(box.dataset.dismissUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','requesttoken':window.OC?.requestToken||''},body:body.toString(),credentials:'same-origin'});if(!r.ok)throw new Error('dismiss failed');box.remove();}catch(e){busy=false;box.classList.add('erp-whatsnew-error');}};
 document.getElementById('erpWhatsNewClose')?.addEventListener('click',dismiss);
 document.getElementById('erpWhatsNewDone')?.addEventListener('click',dismiss);
});
