document.addEventListener('DOMContentLoaded', () => {
 const zone=document.getElementById('projectExplorerDrop');
 const input=document.getElementById('projectExplorerFiles');
 const choose=document.getElementById('projectExplorerChoose');
 const preview=document.getElementById('erp-file-preview');
 const body=document.getElementById('erp-preview-body');
 const title=document.getElementById('erp-preview-title');
 const status=document.getElementById('erp-preview-status');
 let renderGeneration=0;
 let pdfJsPromise=null;

 const loadPdfJs=()=>{
  if(!pdfJsPromise){
   pdfJsPromise=import('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/5.4.54/pdf.min.mjs').then(pdfjs=>{
    pdfjs.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/5.4.54/pdf.worker.min.mjs';
    return pdfjs;
   });
  }
  return pdfJsPromise;
 };

 const clearPreview=()=>{
  renderGeneration++;
  body?.replaceChildren();
 };

 const renderPdf=async(data,generation)=>{
  const pdfjs=await loadPdfJs();
  if(generation!==renderGeneration)return;
  const pdf=await pdfjs.getDocument({data:new Uint8Array(data)}).promise;
  if(generation!==renderGeneration)return;
  status.textContent='PDF wird gerendert – '+pdf.numPages+' Seite'+(pdf.numPages===1?'':'n')+' …';
  for(let pageNo=1;pageNo<=pdf.numPages;pageNo++){
   if(generation!==renderGeneration)return;
   const page=await pdf.getPage(pageNo);
   const base=page.getViewport({scale:1});
   const available=Math.max(320,(body.clientWidth||1000)-32);
   const scale=Math.min(2.2,Math.max(0.75,available/base.width));
   const viewport=page.getViewport({scale});
   const wrap=document.createElement('div');wrap.className='erp-pdf-page';
   const label=document.createElement('div');label.className='erp-pdf-page-label';label.textContent='Seite '+pageNo+' / '+pdf.numPages;
   const canvas=document.createElement('canvas');canvas.width=Math.ceil(viewport.width);canvas.height=Math.ceil(viewport.height);
   canvas.style.width='100%';canvas.style.height='auto';canvas.setAttribute('aria-label','PDF Seite '+pageNo);
   wrap.append(label,canvas);body.appendChild(wrap);
   const context=canvas.getContext('2d',{alpha:false});
   await page.render({canvasContext:context,viewport}).promise;
  }
  if(generation===renderGeneration)status.hidden=true;
 };

 document.querySelectorAll('.erp-preview-open').forEach(button=>button.addEventListener('click',async()=>{
  if(!preview||!body||!title||!status)return;
  clearPreview();const generation=renderGeneration;
  title.textContent=button.dataset.name||'Datei';status.textContent='Datei wird geladen …';status.hidden=false;
  preview.hidden=false;preview.scrollIntoView({behavior:'smooth',block:'start'});
  try{
   const response=await fetch(button.dataset.src||'',{credentials:'same-origin'});
   if(!response.ok)throw new Error('Datei konnte nicht geladen werden ('+response.status+').');
   const mime=(button.dataset.mime||response.headers.get('content-type')||'').toLowerCase();
   if(mime.startsWith('image/')){
    const blob=await response.blob();if(generation!==renderGeneration)return;
    const url=URL.createObjectURL(blob);const image=document.createElement('img');image.src=url;image.alt=button.dataset.name||'Bild';
    image.addEventListener('load',()=>URL.revokeObjectURL(url),{once:true});body.appendChild(image);status.hidden=true;
   }else{
    const data=await response.arrayBuffer();if(generation!==renderGeneration)return;await renderPdf(data,generation);
   }
  }catch(error){
   if(generation!==renderGeneration)return;
   status.textContent='Vorschau fehlgeschlagen: '+(error?.message||'Unbekannter Fehler');
   status.hidden=false;
  }
 }));
 document.getElementById('erp-preview-close')?.addEventListener('click',()=>{
  clearPreview();if(preview)preview.hidden=true;
  document.querySelector('.erp-explorer-list')?.scrollIntoView({behavior:'smooth',block:'start'});
 });

 async function uploadFiles(fileList){
  if(!zone||!fileList?.length)return;
  zone.classList.add('is-uploading');
  try{
   for(const file of Array.from(fileList)){
    const data=new FormData();data.append('requesttoken',zone.dataset.token||OC.requestToken||'');data.append('path',zone.dataset.path||'');data.append('document',file,file.name);
    const response=await fetch(zone.dataset.uploadUrl,{method:'POST',body:data,credentials:'same-origin',redirect:'follow'});
    if(!response.ok)throw new Error('Upload fehlgeschlagen: '+file.name+' ('+response.status+')');
   }
   window.location.reload();
  }catch(error){zone.classList.remove('is-uploading');alert(error?.message||'Upload fehlgeschlagen.');}
 }

 // Critical: stop the browser from navigating to/opening dropped PDFs anywhere on this page.
 const stopFileDrop=e=>{if(Array.from(e.dataTransfer?.types||[]).includes('Files')){e.preventDefault();}};
 document.addEventListener('dragenter',stopFileDrop,true);
 document.addEventListener('dragover',stopFileDrop,true);
 document.addEventListener('drop',stopFileDrop,true);
 window.addEventListener('dragover',stopFileDrop,true);
 window.addEventListener('drop',stopFileDrop,true);

 if(zone){
  ['dragenter','dragover'].forEach(type=>zone.addEventListener(type,e=>{e.preventDefault();e.stopPropagation();zone.classList.add('is-dragging');}));
  zone.addEventListener('dragleave',e=>{e.preventDefault();e.stopPropagation();zone.classList.remove('is-dragging');});
  zone.addEventListener('drop',e=>{e.preventDefault();e.stopPropagation();zone.classList.remove('is-dragging');if(e.dataTransfer?.files?.length)uploadFiles(e.dataTransfer.files);});
 }
 choose?.addEventListener('click',()=>input?.click());
 input?.addEventListener('change',()=>{if(input.files?.length)uploadFiles(input.files);});
});
