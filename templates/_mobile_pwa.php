<?php
$pwaUrl=\OC::$server->getURLGenerator();
$sw=$pwaUrl->linkToRoute('reinhardterp.page.pwaServiceWorker');
?>
<script>
(function(){
 if('serviceWorker' in navigator && window.isSecureContext){
  window.addEventListener('load',function(){
   navigator.serviceWorker.register(<?php echo json_encode($sw); ?>,{scope:'/'})
    .catch(function(e){console.warn('NextERP PWA:',e);});
  });
 }
})();
</script>

<style>
#nexterp-netstate{position:fixed;top:8px;right:8px;z-index:99999;padding:5px 9px;border-radius:999px;font-size:11px;font-weight:800;background:#eef5ff;color:#174c90;box-shadow:0 2px 8px rgba(0,0,0,.12)}
#nexterp-netstate.offline{background:#fff1d6;color:#7a4b00}
</style>
<script>
(function(){
 function render(){
  var e=document.getElementById('nexterp-netstate');
  if(!e){e=document.createElement('div');e.id='nexterp-netstate';document.body.appendChild(e);}
  var on=navigator.onLine;e.textContent=on?'Online':'Offline';e.className=on?'':'offline';
 }
 window.addEventListener('online',render);window.addEventListener('offline',render);
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',render);else render();
})();
</script>

<style>
#nexterp-syncstate{position:fixed;top:38px;right:8px;z-index:99998;border:0;border-radius:999px;padding:5px 9px;font-size:11px;font-weight:800;background:#e8f5e9;color:#236b2c;box-shadow:0 2px 8px rgba(0,0,0,.12)}
#nexterp-syncstate.pending{background:#fff1d6;color:#7a4b00}
</style>
<script>
(function(){var s=document.createElement('script');s.src=(window.OC&&OC.filePath)?OC.filePath('reinhardterp','js','offline-sync.js'):('/apps/reinhardterp/js/offline-sync.js');document.head.appendChild(s)})();
</script>
