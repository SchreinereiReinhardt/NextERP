(function(){
'use strict';
function init(){
 const views=[...document.querySelectorAll('.erp-folder-view')];
 if(!views.length)return;
 function open(path){
  let found=false;
  views.forEach(v=>{const yes=(v.dataset.folder||'')===path;v.hidden=!yes;if(yes)found=true});
  if(!found){views.forEach(v=>v.hidden=(v.dataset.folder||'')!=='')}
  window.scrollTo({top:0,behavior:'instant'});
 }
 document.addEventListener('click',e=>{
  const b=e.target.closest('[data-open-folder]');
  if(!b)return;
  e.preventDefault();open(b.dataset.openFolder||'');
 });
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
})();