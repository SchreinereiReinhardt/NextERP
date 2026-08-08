<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
$url=\OC::$server->get(IURLGenerator::class);
$selectedProjectId=(int)($_['selectedProjectId']??0);
$availableEntries=$_['availableEntries']??[];
$availableMaterials=$_['availableMaterials']??[];
$isArchive=!empty($_['archiveMode']);
?>
<div id="app-content"><div class="erp-page">
<div class="erp-head"><div><h1><?php p($isArchive?'Rapportarchiv':'Rapporte'); ?></h1><p class="erp-sub"><?php p($isArchive?'Archivierte Rapporte bleiben vollständig erhalten und können wiederhergestellt werden.':'Projekt wählen, offene Zeiten markieren und direkt in den neuen Rapport übernehmen.'); ?></p></div></div>
<nav class="erp-project-tabs" aria-label="Rapportansicht"><a class="<?php p(!$isArchive?'is-active':''); ?>" href="<?php p($url->linkToRoute('reinhardterp.module.reports')); ?>">Aktive Rapporte <strong><?php p((string)($_['activeCount']??0)); ?></strong></a><a class="<?php p($isArchive?'is-active':''); ?>" href="<?php p($url->linkToRoute('reinhardterp.module.reports').'?archive=1'); ?>">Archiv <strong><?php p((string)($_['archiveCount']??0)); ?></strong></a></nav>

<?php if(!$isArchive): ?>
<form class="erp-form-card erp-project-loader" method="get" action="<?php p($url->linkToRoute('reinhardterp.module.reports')); ?>">
 <label>Projekt für neuen Rapport</label>
 <div class="erp-project-select-row">
  <select name="projectId" required>
   <option value="">Projekt wählen</option>
   <?php foreach($_['projects'] as $project): ?>
    <option value="<?php p($project['id']); ?>" <?php if((int)$project['id']===$selectedProjectId): ?>selected<?php endif; ?>><?php p($project['project_no'].' · '.$project['title']); ?></option>
   <?php endforeach; ?>
  </select>
  <button class="button primary" type="submit">Zeiten anzeigen / neuen Rapport</button>
 </div>
</form>

<?php if($selectedProjectId>0): ?>
<form class="erp-form-card" method="post" action="<?php p($url->linkToRoute('reinhardterp.module.saveReport')); ?>" id="new-report-form">
 <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
 <input type="hidden" name="projectId" value="<?php p($selectedProjectId); ?>">
 <div class="erp-form-grid">
  <div><label>Datum</label><input type="date" name="reportDate" value="<?php p(date('Y-m-d')); ?>" required></div>
  <div><label>Titel</label><input name="title" placeholder="z. B. Montage Küche" required></div>
  <div class="erp-span-2"><label>Zusätzliche Tätigkeitsbeschreibung</label><textarea name="description" rows="6" placeholder="Nur Ergänzungen eintragen – die ausgewählten Zeiterfassungen werden als einzelne Tätigkeiten übernommen."></textarea></div>
 </div>

 <section class="erp-time-import-box">
  <div class="erp-time-import-head">
   <div><h2>Offene Zeiten dieses Projekts</h2><p class="erp-muted">Bereits in einen Rapport übernommene Zeiten werden hier nicht erneut angeboten.</p></div>
   <?php if(count($availableEntries)>0): ?><label class="erp-select-all"><input type="checkbox" id="select-all-times"> Alle auswählen</label><?php endif; ?>
  </div>
  <?php if(count($availableEntries)===0): ?>
   <div class="erp-empty">Für dieses Projekt sind aktuell keine offenen Zeiteinträge vorhanden. Der Rapport kann trotzdem angelegt werden.</div>
  <?php else: ?>
   <div class="erp-time-entry-list">
    <?php foreach($availableEntries as $entry): ?>
     <label class="erp-check-row erp-time-entry">
      <input class="time-entry-checkbox" type="checkbox" name="entryIds[]" value="<?php p($entry['id']); ?>" data-hours="<?php p((string)$entry['hours']); ?>">
      <span class="erp-time-entry-date"><?php p(date('d.m.Y',strtotime((string)$entry['work_date']))); ?></span>
      <span class="erp-time-entry-person"><?php p($entry['display_name']); ?></span>
      <strong class="erp-time-entry-hours"><?php p(number_format((float)$entry['hours'],2,',','.')); ?> Std.</strong>
      <span class="erp-time-entry-activity"><?php p($entry['activity']); ?></span>
     </label>
    <?php endforeach; ?>
   </div>
   <div class="erp-time-total">Ausgewählt: <strong id="selected-time-total">0,00 Std.</strong></div>
  <?php endif; ?>
 </section>

 <section class="erp-time-import-box">
  <div class="erp-time-import-head">
   <div><h2>Offenes Material dieses Projekts</h2><p class="erp-muted">Material aus der Zeiterfassung kann unabhängig von der Arbeitszeit in den Rapport übernommen werden.</p></div>
   <?php if(count($availableMaterials)>0): ?><label class="erp-select-all"><input type="checkbox" id="select-all-materials"> Alle auswählen</label><?php endif; ?>
  </div>
  <?php if(count($availableMaterials)===0): ?>
   <div class="erp-empty">Für dieses Projekt ist aktuell kein noch nicht übernommenes Material vorhanden.</div>
  <?php else: ?>
   <div class="erp-time-entry-list">
    <?php foreach($availableMaterials as $material): ?>
     <label class="erp-check-row erp-time-entry">
      <input class="material-entry-checkbox" type="checkbox" name="materialEntryIds[]" value="<?php p($material['id']); ?>">
      <span class="erp-time-entry-date"><?php p(date('d.m.Y',strtotime((string)$material['work_date']))); ?></span>
      <span class="erp-time-entry-person"><?php p($material['display_name']); ?></span>
      <strong class="erp-time-entry-hours"><?php p(number_format((float)$material['quantity'],3,',','.').' '.($material['unit']??'')); ?></strong>
      <span class="erp-time-entry-activity"><strong><?php p($material['description']); ?></strong><?php if(!empty($material['activity'])): ?><br><span class="erp-muted"><?php p($material['activity']); ?></span><?php endif; ?></span>
     </label>
    <?php endforeach; ?>
   </div>
  <?php endif; ?>
 </section>
 <button class="button primary">Rapport anlegen und Auswahl übernehmen</button>
</form>
<?php endif; ?>

<?php endif; ?>

<div class="erp-table"><table><thead><tr><th>Nr.</th><th>Datum</th><th>Titel</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach($_['rows'] as $report): ?><tr><td><?php p($report['report_no']); ?></td><td><?php p($report['report_date']); ?></td><td><strong><?php p($report['title']); ?></strong><?php if(!empty($report['customer_name'])): ?><br><small><?php p($report['customer_name'].' · '.($report['project_no']??'')); ?></small><?php endif; ?></td><td><span class="erp-badge"><?php p($report['status']); ?></span></td><td><div class="erp-row-actions"><a class="button" href="<?php p($url->linkToRoute('reinhardterp.module.reportDetail',['id'=>$report['id']])); ?>">Öffnen</a><?php if($isArchive): ?><form method="post" action="<?php p($url->linkToRoute('reinhardterp.module.restoreReport',['id'=>$report['id']])); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button" type="submit">Wiederherstellen</button></form><?php if(!empty($_['canDeleteReports'])): ?><form method="post" action="<?php p($url->linkToRoute('reinhardterp.module.deleteReport',['id'=>$report['id']])); ?>" onsubmit="return confirm('Rapport wirklich ENDGÜLTIG löschen? Zeiten und Material werden wieder zur Übernahme freigegeben.');"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button" type="submit">Endgültig löschen</button></form><?php endif; ?><?php else: ?><form method="post" action="<?php p($url->linkToRoute('reinhardterp.module.archiveReport',['id'=>$report['id']])); ?>" onsubmit="return confirm('Rapport wirklich archivieren?');"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button" type="submit">Archivieren</button></form><?php endif; ?></div></td></tr><?php endforeach; ?>
</tbody></table></div>
</div></div>
<script>
(() => {
 const boxes=[...document.querySelectorAll('.time-entry-checkbox')];
 const total=document.getElementById('selected-time-total');
 const all=document.getElementById('select-all-times');
 const materialBoxes=[...document.querySelectorAll('.material-entry-checkbox')];
 const allMaterials=document.getElementById('select-all-materials');
 const update=()=>{const hours=boxes.filter(b=>b.checked).reduce((sum,b)=>sum+(parseFloat(b.dataset.hours)||0),0);if(total)total.textContent=hours.toLocaleString('de-DE',{minimumFractionDigits:2,maximumFractionDigits:2})+' Std.';if(all)all.checked=boxes.length>0&&boxes.every(b=>b.checked);if(allMaterials)allMaterials.checked=materialBoxes.length>0&&materialBoxes.every(b=>b.checked);};
 boxes.forEach(b=>b.addEventListener('change',update));
 materialBoxes.forEach(b=>b.addEventListener('change',update));
 if(all)all.addEventListener('change',()=>{boxes.forEach(b=>b.checked=all.checked);update();});
 if(allMaterials)allMaterials.addEventListener('change',()=>{materialBoxes.forEach(b=>b.checked=allMaterials.checked);update();});
 update();
})();
</script>
