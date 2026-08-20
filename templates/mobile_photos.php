<?php require __DIR__.'/_mobile_pwa.php'; script('reinhardterp','mobile-camera'); ?>
<?php $p=$_['project'];$url=$_['urlGenerator']; ?>
<style>
.erp-cam{max-width:720px;margin:0 auto;padding:16px 14px 110px;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#14213d}.erp-cam *{box-sizing:border-box}.erp-cam a{text-decoration:none}.erp-cam-head{display:flex;gap:12px;align-items:center;margin-bottom:14px}.erp-cam-back{width:44px;height:44px;display:grid;place-items:center;border-radius:14px;background:#e8f3fa;color:#1265d8;font-size:25px}.erp-cam h1{margin:0;font-size:23px;color:#0b1f55}.erp-cam .sub{margin:2px 0;color:#6b7280;font-size:13px}.camera-card{background:#08111f;border-radius:22px;overflow:hidden;position:relative;box-shadow:0 10px 30px rgba(12,32,70,.18)}#camera-video,#camera-preview{display:none;width:100%;aspect-ratio:3/4;object-fit:cover;background:#08111f}.camera-idle{min-height:300px;display:grid;place-items:center;text-align:center;color:#fff;padding:32px}.camera-idle b{display:block;font-size:20px;margin-bottom:7px}.camera-idle span{color:#b9c7da;font-size:13px}.camera-actions{display:flex;gap:10px;padding:14px;background:#08111f}.camera-actions button,.camera-actions label{flex:1;min-height:50px;border:0;border-radius:14px;display:grid;place-items:center;font:800 14px system-ui;background:#1265d8;color:#fff;cursor:pointer}.camera-actions .secondary{background:#26364d}.camera-shutter{width:68px!important;min-width:68px;flex:0 0 68px!important;border-radius:50%!important;border:5px solid #fff!important;background:#1265d8!important}.camera-note{margin:14px 0}.camera-note input{width:100%;min-height:48px;border:1px solid #cfd7e3;border-radius:14px;padding:10px 12px;font:inherit}.camera-status{display:none;margin:10px 0;padding:11px 13px;border-radius:13px;background:#eef5ff;color:#174c90;font-size:13px}.camera-save{display:none;width:100%;min-height:52px;border:0;border-radius:14px;background:#1265d8;color:#fff;font-weight:800;font-size:15px}.photo-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:9px;margin:18px 0}.photo{border:1px solid #dfe5ec;border-radius:16px;overflow:hidden;background:#fff;color:#14213d!important}.photo-preview{height:135px;background:#e8f3fa;display:grid;place-items:center;font-size:35px;color:#1265d8}.photo b{display:block;padding:9px 10px 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.photo small{display:block;padding:0 10px 10px;color:#6b7280}
.camera-native-input{position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;left:-9999px!important}.camera-input-wrap{position:relative;flex:1;min-height:50px}.camera-input-wrap>span{position:absolute;inset:0;display:grid;place-items:center;border-radius:14px;background:#1265d8;color:#fff;font:800 14px system-ui;pointer-events:none}.camera-input-wrap.secondary>span{background:#26364d}.camera-input-wrap>input{position:absolute!important;inset:0!important;width:100%!important;height:100%!important;opacity:0!important;left:0!important;cursor:pointer!important;z-index:2}.camera-input-wrap.retry{display:none}
</style>
<div id="app-content"><main class="erp-cam"><header class="erp-cam-head"><a class="erp-cam-back" href="<?php p($url->linkToRoute('reinhardterp.business.mobileProject',['id'=>(int)$p['id']])); ?>">‹</a><div><h1>Projektkamera</h1><p class="sub"><?php p(($p['project_no']??'').' · '.($p['title']??''));?></p></div></header>
<?php if($_['canUpload']):?>
<div class="camera-card" id="camera-root"
 data-upload-url="<?php p($url->linkToRoute('reinhardterp.page.uploadMobileProjectPhoto',['id'=>(int)$p['id']])); ?>"
 data-requesttoken="<?php p($_['requesttoken']); ?>">
  <div id="camera-idle" class="camera-idle"><div><b>Live-Kamera</b><span>Die Rückkamera wird direkt in Betrio geöffnet.</span></div></div>
  <video id="camera-video" autoplay playsinline muted></video>
  <img id="camera-preview" alt="Foto Vorschau">
  <div class="camera-actions">
    <button id="camera-open" type="button">Kamera starten</button>
    <button id="camera-shutter" class="camera-shutter" type="button" style="display:none" aria-label="Foto aufnehmen"></button>
    <button id="camera-cancel" class="secondary" type="button" style="display:none">Abbrechen</button>
    <button id="camera-retry" class="secondary" type="button" style="display:none">Neu</button>
    <label class="secondary" for="camera-file" id="camera-file-label">Datei</label>
  </div>
</div>
<input id="camera-file" class="camera-native-input" type="file" accept="image/*">
<canvas id="camera-canvas" style="display:none"></canvas>
<div id="camera-status" class="camera-status"></div>
<div class="camera-note"><input id="camera-description" type="text" placeholder="Beschreibung optional, z. B. Fenster Küche"></div>
<button id="camera-save" class="camera-save" type="button">Foto zum Projekt speichern</button>
<?php endif;?>
<div class="photo-grid"><?php foreach($_['photos'] as $d):$path=(string)($d['path']??'');$name=(string)($d['name']??basename($path));?><a class="photo" href="<?php p($url->linkToRoute('reinhardterp.page.projectFile',['id'=>(int)$p['id'],'path'=>$path])); ?>"><div class="photo-preview">▧</div><b><?php p($name);?></b><small>Foto öffnen</small></a><?php endforeach;?></div></main></div>

<?php $mobileActive='projects';$mobileProjectId=(int)$p['id'];require __DIR__.'/_mobile_nav.php';?>
