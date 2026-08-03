<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
$url=\OC::$server->get(IURLGenerator::class);
?>
<div id="app-content"><div class="erp-page erp-list-page erp-projects-page">
<div class="erp-head"><div><span class="erp-record-kicker">Projektverwaltung</span><h1>Projekte</h1><p class="erp-sub">Kompakter Überblick über alle laufenden Projektakten.</p></div><a class="button primary" href="<?php p($url->linkToRoute('reinhardterp.page.projectForm')); ?>">+ Neues Projekt</a></div>
<?php if(!$_['projects']): ?>
<div class="erp-card erp-empty">Noch keine Projekte vorhanden.</div>
<?php else: ?>
<div class="erp-project-list">
<?php foreach($_['projects'] as $p):
$detail=$url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$p->getId()]);
$status=trim((string)$p->getStatus()) ?: 'offen';
?>
<article class="erp-project-row">
<a class="erp-project-row-main" href="<?php p($detail); ?>">
<div class="erp-project-row-icon">📁</div>
<div class="erp-project-row-title"><span><?php p($p->getProjectNo()); ?></span><strong><?php p($p->getTitle()); ?></strong></div>
<div class="erp-project-row-meta"><span>Kunde</span><strong><?php p($_['customerNames'][$p->getCustomerId()]??'–'); ?></strong></div>
<div class="erp-project-row-meta"><span>Termin</span><strong><?php p($p->getDueDate()?->format('d.m.Y')??'Noch offen'); ?></strong></div>
<div><span class="erp-status-pill" data-status="<?php p(strtolower($status)); ?>"><?php p($status); ?></span></div>
</a>
<div class="erp-project-row-actions"><a class="button primary" href="<?php p($detail); ?>">Öffnen</a><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.projectForm',['id'=>$p->getId()])); ?>">Bearbeiten</a><form method="post" action="<?php p($url->linkToRoute('reinhardterp.project.archive',['id'=>$p->getId()])); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button">Archivieren</button></form></div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div></div>
