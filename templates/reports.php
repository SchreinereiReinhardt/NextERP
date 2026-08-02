<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
$url=\OC::$server->get(IURLGenerator::class);
$selectedProjectId=(int)($_['selectedProjectId']??0);
$availableEntries=$_['availableEntries']??[];
?>
<div id="app-content"><div class="erp-page">
<div class="erp-head"><div><h1>Rapporte</h1><p class="erp-sub">Projekt wählen, offene Zeiten markieren und direkt in den neuen Rapport übernehmen.</p></div></div>

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
 <input type="hidden" name="requesttoken" value="<?php p($requestToken); ?>">
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
 <button class="button primary">Rapport anlegen und ausgewählte Zeiten übernehmen</button>
</form>
<?php endif; ?>

<div class="erp-table"><table><thead><tr><th>Nr.</th><th>Datum</th><th>Titel</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach($_['rows'] as $report): ?><tr><td><?php p($report['report_no']); ?></td><td><?php p($report['report_date']); ?></td><td><?php p($report['title']); ?></td><td><span class="erp-badge"><?php p($report['status']); ?></span></td><td><a class="button" href="<?php p($url->linkToRoute('reinhardterp.module.reportDetail',['id'=>$report['id']])); ?>">Öffnen</a></td></tr><?php endforeach; ?>
</tbody></table></div>
</div></div>
<script>
(() => {
 const boxes=[...document.querySelectorAll('.time-entry-checkbox')];
 const total=document.getElementById('selected-time-total');
 const all=document.getElementById('select-all-times');
 const update=()=>{const hours=boxes.filter(b=>b.checked).reduce((sum,b)=>sum+(parseFloat(b.dataset.hours)||0),0);if(total)total.textContent=hours.toLocaleString('de-DE',{minimumFractionDigits:2,maximumFractionDigits:2})+' Std.';if(all)all.checked=boxes.length>0&&boxes.every(b=>b.checked);};
 boxes.forEach(b=>b.addEventListener('change',update));
 if(all)all.addEventListener('change',()=>{boxes.forEach(b=>b.checked=all.checked);update();});
 update();
})();
</script>
