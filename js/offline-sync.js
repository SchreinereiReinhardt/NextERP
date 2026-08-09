(function(){
'use strict';
const KEY='nexterp-offline-queue-v1';
function load(){try{return JSON.parse(localStorage.getItem(KEY)||'[]')}catch(e){return[]}}
function save(q){localStorage.setItem(KEY,JSON.stringify(q));render()}
function id(){return 'op-'+Date.now()+'-'+Math.random().toString(36).slice(2,12)}
function render(){
 let q=load(),e=document.getElementById('nexterp-syncstate');
 if(!e){e=document.createElement('button');e.id='nexterp-syncstate';e.type='button';e.addEventListener('click',sync);document.body.appendChild(e)}
 e.textContent=q.length?(q.length+' wartet · Sync'):'Synchronisiert';
 e.className=q.length?'pending':'';
}
async function sync(){
 if(!navigator.onLine)return render();
 let q=load();if(!q.length)return render();
 let left=[];
 for(const item of q){
  try{
   const fd=new FormData();
   Object.entries(item.data).forEach(([k,v])=>fd.append(k,v));
   fd.set('clientOperationId',item.id);
   const r=await fetch(item.action,{method:'POST',body:fd,credentials:'same-origin',redirect:'follow'});
   if(!r.ok)throw new Error('HTTP '+r.status);
  }catch(e){left.push(item)}
 }
 save(left);
 if(!left.length)window.dispatchEvent(new CustomEvent('nexterp-sync-complete'));
}
function hook(){
 document.querySelectorAll('form[data-nexterp-offline]').forEach(form=>{
  if(form.dataset.offlineHooked)return;form.dataset.offlineHooked='1';
  form.addEventListener('submit',ev=>{
   if(navigator.onLine)return;
   ev.preventDefault();
   const fd=new FormData(form),data={};fd.forEach((v,k)=>{if(typeof v==='string')data[k]=v});
   data.clientOperationId=id();
   const q=load();q.push({id:data.clientOperationId,type:form.dataset.nexterpOffline,action:form.action,data,createdAt:new Date().toISOString()});save(q);
   alert('Offline gespeichert. Die Buchung wird synchronisiert, sobald wieder Internet verfügbar ist.');
   if(form.dataset.afterQueue)location.href=form.dataset.afterQueue;
  });
 });
 render();
}
window.addEventListener('online',sync);
window.addEventListener('load',()=>{hook();sync()});
document.addEventListener('DOMContentLoaded',hook);
})();