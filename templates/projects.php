<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
$url=\OC::$server->get(IURLGenerator::class);
$view=$_['view']??'active';
$isArchive=$view==='archive';
$search=(string)($_['search']??'');
?>
<div id="app-content"><div class="erp-page erp-list-page erp-projects-page">
<div class="erp-head"><div><span class="erp-record-kicker">Projektverwaltung</span><h1><?php p($isArchive?'Projektarchiv':'Projekte'); ?></h1><p class="erp-sub"><?php p($isArchive?'Archivierte Projektakten bleiben vollständig erhalten und können jederzeit wiederhergestellt werden.':'Kompakter Überblick über alle laufenden Projektakten.'); ?></p></div><?php if(!$isArchive): ?><a class="button primary" href="<?php p($url->linkToRoute('reinhardterp.page.projectForm')); ?>">+ Neues Projekt</a><?php endif; ?></div>

<nav class="erp-project-tabs" aria-label="Projektansicht">
 <a class="<?php p(!$isArchive?'is-active':''); ?>" href="<?php p($url->linkToRoute('reinhardterp.page.projects')); ?>">Aktive Projekte <strong><?php p((string)($_['activeCount']??0)); ?></strong></a>
 <a class="<?php p($isArchive?'is-active':''); ?>" href="<?php p($url->linkToRoute('reinhardterp.page.projects').'?view=archive'); ?>">Archiv <strong><?php p((string)($_['archiveCount']??0)); ?></strong></a>
</nav>

<form class="erp-project-search" method="get" action="<?php p($url->linkToRoute('reinhardterp.page.projects')); ?>">
 <?php if($isArchive): ?><input type="hidden" name="view" value="archive"><?php endif; ?>
 <input type="search" name="q" value="<?php p($search); ?>" placeholder="Projektnummer, Titel oder Status suchen …" aria-label="Projekte durchsuchen">
 <button class="button" type="submit">Suchen</button>
 <?php if($search!==''): ?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.projects').($isArchive?'?view=archive':'')); ?>">Zurücksetzen</a><?php endif; ?>
</form>

<?php if(!$_['projects']): ?>
<div class="erp-card erp-empty"><?php p($search!==''?'Keine passenden Projekte gefunden.':($isArchive?'Das Projektarchiv ist leer.':'Noch keine Projekte vorhanden.')); ?></div>
<?php else: ?>
<div class="erp-project-list">
<?php foreach($_['projects'] as $p):
$detail=$url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$p->getId()]);
$status=trim((string)$p->getStatus()) ?: 'offen';
?>
<article class="erp-project-row<?php p($isArchive?' is-archived':''); ?>">
<a class="erp-project-row-main" href="<?php p($detail); ?>">
<div class="erp-project-row-icon"><?php p($isArchive?'🗄️':'📁'); ?></div>
<div class="erp-project-row-title"><span><?php p($p->getProjectNo()); ?></span><strong><?php p($p->getTitle()); ?></strong></div>
<div class="erp-project-row-meta"><span>Kunde</span><strong><?php p($_['customerNames'][$p->getCustomerId()]??'–'); ?></strong></div>
<div class="erp-project-row-meta"><span><?php p($isArchive?'Archiviert/geändert':'Termin'); ?></span><strong><?php p($isArchive?($p->getUpdatedAt()?->format('d.m.Y')??'–'):($p->getDueDate()?->format('d.m.Y')??'Noch offen')); ?></strong></div>
<div><span class="erp-status-pill" data-status="<?php p(strtolower($status)); ?>"><?php p($status); ?></span></div>
</a>
<div class="erp-project-row-actions"><a class="button primary" href="<?php p($detail); ?>">Öffnen</a><?php if($isArchive): ?><form method="post" action="<?php p($url->linkToRoute('reinhardterp.project.restore',['id'=>$p->getId()])); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button" type="submit">Wiederherstellen</button></form><?php else: ?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.projectForm',['id'=>$p->getId()])); ?>">Bearbeiten</a><form method="post" action="<?php p($url->linkToRoute('reinhardterp.project.archive',['id'=>$p->getId()])); ?>" onsubmit="return confirm('Projekt wirklich archivieren? Dokumente, Rapporte und Zeiten bleiben erhalten.');"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button" type="submit">Archivieren</button></form><?php endif; ?></div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div></div>
