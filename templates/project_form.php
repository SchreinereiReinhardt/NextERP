<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
$url=\OC::$server->get(IURLGenerator::class);
$p=$_['project'];
$statuses=['Anfrage','Angebot','Auftrag','Fertigung','Montage','Abnahme','Abrechnung','Abgeschlossen'];
$currentStatus=(string)($p?->getStatus()??'Anfrage');
?>
<div id="app-content"><div class="erp-page erp-form-page"><h1><?php p($p?'Projekt bearbeiten':'Neues Projekt'); ?></h1>
<form method="post" action="<?php p($url->linkToRoute('reinhardterp.project.save')); ?>">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
<?php if($p): ?><input type="hidden" name="id" value="<?php p($p->getId()); ?>"><?php endif; ?>
<label>Kunde *</label><select name="customerId" required><option value="">Bitte auswählen</option><?php foreach($_['customers'] as $c): ?><option value="<?php p($c->getId()); ?>" <?php if((int)($_['selectedCustomerId']??0)===$c->getId()) print_unescaped('selected'); ?>><?php p($c->getName()); ?></option><?php endforeach; ?></select>
<div class="erp-form-grid"><div><label>Projektnummer</label><input name="projectNo" readonly placeholder="wird automatisch vergeben" value="<?php p($p?->getProjectNo()??''); ?>"></div><div><label>Status</label><select name="status"><?php foreach($statuses as $status): ?><option value="<?php p($status); ?>" <?php if(strcasecmp($currentStatus,$status)===0) print_unescaped('selected'); ?>><?php p($status); ?></option><?php endforeach; ?></select></div></div>
<label>Bezeichnung *</label><input name="title" required value="<?php p($p?->getTitle()??''); ?>">
<div class="erp-form-grid"><div><label>Startdatum</label><input type="date" name="startDate" value="<?php p($p?->getStartDate()?->format('Y-m-d')??''); ?>"></div><div><label>Fällig am</label><input type="date" name="dueDate" value="<?php p($p?->getDueDate()?->format('Y-m-d')??''); ?>"></div></div>
<label>Beschreibung</label><textarea name="description" rows="7"><?php p($p?->getDescription()??''); ?></textarea>
<div class="erp-actions"><button class="button primary">Speichern</button><?php if($p): ?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$p->getId()])); ?>">Zur Projektakte</a><?php else: ?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.projects')); ?>">Abbrechen</a><?php endif; ?></div>
</form></div></div>
