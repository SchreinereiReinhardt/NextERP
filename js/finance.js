document.addEventListener('DOMContentLoaded', () => {
 const token=document.querySelector('input[name="requesttoken"]')?.value||OC?.requestToken||'';
 const uploadUrl=OC.generateUrl('/apps/reinhardterp/api/documents/upload');
 async function uploadFiles(files){
  for(const file of Array.from(files)){
   const data=new FormData();data.append('requesttoken',token);data.append('document',file);
   const response=await fetch(uploadUrl,{method:'POST',body:data,credentials:'same-origin'});
   if(!response.ok)throw new Error('Upload fehlgeschlagen: '+file.name);
  }
  window.location.reload();
 }
 function bind(zone,input){
  if(!zone)return;
  ['dragenter','dragover'].forEach(type=>zone.addEventListener(type,e=>{e.preventDefault();e.stopPropagation();zone.classList.add('is-dragging')}));
  ['dragleave','drop'].forEach(type=>zone.addEventListener(type,e=>{e.preventDefault();e.stopPropagation();zone.classList.remove('is-dragging')}));
  zone.addEventListener('drop',e=>{if(e.dataTransfer?.files?.length)uploadFiles(e.dataTransfer.files).catch(err=>alert(err.message))});
  if(input){input.multiple=true;input.addEventListener('change',()=>{if(input.files?.length)uploadFiles(input.files).catch(err=>alert(err.message))});}
 }
 bind(document.getElementById('financeDropForm'),document.getElementById('financeFileInput'));
 bind(document.getElementById('globalDocumentDrop'),null);
});
