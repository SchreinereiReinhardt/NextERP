<?php require __DIR__.'/_mobile_pwa.php'; ?>
<?php $url=$_['urlGenerator'];$materials=$_['materials'];$projects=$_['projects'];$pid=(int)($_['projectId']??0); ?>
<style>
.erp-mm{max-width:720px;margin:0 auto;padding:16px 14px 110px;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#14213d}.erp-mm *{box-sizing:border-box}.erp-mm h1{margin:0;font-size:25px;color:#0b1f55}.erp-mm .sub{color:#6b7280;margin:3px 0 16px}.erp-mm-search{display:flex;gap:8px;margin-bottom:12px}.erp-mm input,.erp-mm select{width:100%;min-height:46px;border:1px solid #cfd7e3;border-radius:12px;padding:9px 11px;font:inherit;background:#fff}.erp-mm button{min-height:46px;border:0;border-radius:12px;padding:0 16px;background:#1265d8;color:#fff;font-weight:800}.erp-scan{margin-bottom:15px}.erp-scan button{width:100%}#scan-video{display:none;width:100%;border-radius:16px;margin:8px 0 15px}.erp-mm-card{border:1px solid #dfe5ec;border-radius:16px;background:#fff;padding:13px;margin:9px 0}.erp-mm-top{display:flex;justify-content:space-between;gap:12px}.erp-mm-top b{font-size:16px}.erp-mm-top small{display:block;color:#6b7280;margin-top:2px}.erp-stock{white-space:nowrap;font-weight:800;color:#1265d8}.erp-use{display:grid;grid-template-columns:1fr 110px;gap:8px;margin-top:11px}.erp-use .wide{grid-column:1/-1}.erp-empty{padding:22px;text-align:center;border:1px dashed #cfd7e3;border-radius:16px;color:#6b7280}.erp-back{text-decoration:none;color:#1265d8;font-weight:800;display:inline-block;margin-bottom:12px}
</style>
<div id="app-content"><main class="erp-mm"><a class="erp-back" href="<?php p($url->linkToRoute('reinhardterp.business.mobile')); ?>">‹ Heute</a><h1>Material & Scanner</h1><p class="sub">Material suchen, Barcode scannen und direkt einem Projekt entnehmen.</p>
<form class="erp-mm-search" method="get"><input id="material-q" type="search" name="q" value="<?php p((string)($_['query']??''));?>" placeholder="Artikel, Material oder Barcode"><input type="hidden" name="projectId" value="<?php p($pid?:'');?>"><button>Suchen</button></form>
<div class="erp-scan"><button type="button" id="scan-btn">Barcode scannen</button><button type="button" id="stop-scan-btn" style="display:none;background:#6b7280">Scanner schließen</button></div>
<div id="scan-status" style="display:none;margin:-5px 0 10px;padding:10px 12px;border-radius:12px;background:#eef5ff;color:#174c90;font-size:13px"></div>
<video id="scan-video" playsinline muted></video>
<input id="camera-fallback" type="file" accept="image/*" capture="environment" style="display:none">
<?php if(!$materials):?><div class="erp-empty">Kein Material gefunden.</div><?php endif;?>
<?php foreach($materials as $m):$stock=(float)($m['stock_quantity']??0);?><article class="erp-mm-card"><div class="erp-mm-top"><div><b><?php p((string)$m['name']);?></b><small><?php p((string)($m['article_no']??''));?><?php if(!empty($m['barcode'])):?> · EAN <?php p((string)$m['barcode']);?><?php endif;?><?php if(!empty($m['storage_location'])):?> · <?php p((string)$m['storage_location']);?><?php endif;?></small></div><span class="erp-stock"><?php p(number_format($stock,3,',','.').' '.($m['unit']??''));?></span></div>
<form class="erp-use" data-nexterp-offline="material" method="post" action="<?php p($url->linkToRoute('reinhardterp.business.saveMobileMaterial'));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input type="hidden" name="materialId" value="<?php p((int)$m['id']);?>"><select class="wide" name="projectId" required><option value="">Projekt wählen …</option><?php foreach($projects as $p):?><option value="<?php p((int)$p['id']);?>" <?php if($pid===(int)$p['id'])echo 'selected';?>><?php p(($p['project_no']??'').' · '.($p['title']??''));?></option><?php endforeach;?></select><input type="number" name="quantity" min="0.001" max="<?php p($stock);?>" step="0.001" value="1" required><button <?php if($stock<=0)echo 'disabled';?>>Entnehmen</button><input class="wide" name="note" placeholder="Notiz optional"></form></article><?php endforeach;?></main></div>
<script>
(function(){
 const startBtn=document.getElementById('scan-btn');
 const stopBtn=document.getElementById('stop-scan-btn');
 const video=document.getElementById('scan-video');
 const q=document.getElementById('material-q');
 const status=document.getElementById('scan-status');
 const fallback=document.getElementById('camera-fallback');
 let stream=null,active=false,raf=0,detector=null;

 function message(text,error=false){
   status.textContent=text;
   status.style.display='block';
   status.style.background=error?'#fff1f1':'#eef5ff';
   status.style.color=error?'#9b1c1c':'#174c90';
 }
 function stop(){
   active=false;
   if(raf)cancelAnimationFrame(raf);
   if(stream){stream.getTracks().forEach(t=>t.stop());stream=null;}
   video.pause();video.srcObject=null;video.style.display='none';
   stopBtn.style.display='none';startBtn.style.display='';
 }
 async function detectLoop(){
   if(!active||!detector)return;
   try{
     const codes=await detector.detect(video);
     if(codes&&codes.length){
       const value=(codes[0].rawValue||'').trim();
       if(value){q.value=value;message('Barcode erkannt: '+value);stop();q.form.submit();return;}
     }
   }catch(e){}
   raf=requestAnimationFrame(detectLoop);
 }
 async function openCamera(){
   if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){
     message('Direkter Kamerazugriff wird von diesem Browser nicht unterstützt. Ich öffne die Handykamera.',true);
     fallback.click();return;
   }
   try{
     stream=await navigator.mediaDevices.getUserMedia({
       video:{facingMode:{ideal:'environment'},width:{ideal:1280},height:{ideal:720}},
       audio:false
     });
     video.srcObject=stream;video.style.display='block';await video.play();
     active=true;startBtn.style.display='none';stopBtn.style.display='';
     if('BarcodeDetector' in window){
       try{
         const supported=BarcodeDetector.getSupportedFormats?await BarcodeDetector.getSupportedFormats():[];
         const wanted=['ean_13','ean_8','code_128','code_39','upc_a','upc_e','qr_code'];
         const formats=supported.length?wanted.filter(x=>supported.includes(x)):wanted;
         detector=new BarcodeDetector(formats.length?{formats}:undefined);
         message('Kamera aktiv – Barcode vor die Kamera halten.');
         detectLoop();
       }catch(e){
         detector=null;
         message('Kamera ist geöffnet. Automatische Barcode-Erkennung wird von diesem Browser nicht unterstützt – Barcode bitte manuell eingeben.',true);
       }
     }else{
       detector=null;
       message('Kamera ist geöffnet. Dieser Browser unterstützt die automatische Barcode-Erkennung nicht – Barcode bitte manuell eingeben.',true);
     }
   }catch(e){
     const name=e&&e.name?e.name:'';
     if(name==='NotAllowedError'||name==='PermissionDeniedError'){
       message('Kamerazugriff wurde blockiert. Bitte in den Website-Einstellungen Kamera erlauben.',true);
     }else if(name==='NotFoundError'||name==='DevicesNotFoundError'){
       message('Keine Kamera gefunden.',true);
     }else{
       message('Direkter Kamerazugriff fehlgeschlagen. Ich öffne die Handykamera als Fallback.',true);
       fallback.click();
     }
   }
 }
 startBtn.addEventListener('click',openCamera);
 stopBtn.addEventListener('click',stop);
 fallback.addEventListener('change',function(){
   if(this.files&&this.files.length){
     message('Foto aufgenommen. Falls der Barcode nicht automatisch erkannt werden kann, bitte die Nummer ins Suchfeld eingeben.');
     q.focus();
   }
 });
 window.addEventListener('pagehide',stop);
 document.addEventListener('visibilitychange',()=>{if(document.hidden&&active)stop();});
})();
</script>