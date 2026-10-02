<?php
$url=\OC::$server->get(\OCP\IURLGenerator::class);$activities=$_['activities']??[];
$labels=['file_created'=>'Neu','file_changed'=>'Geändert','file_moved'=>'Verschoben','file_deleted'=>'Gelöscht'];
?>
<div id="app-content"><div id="app-content-wrapper"><?php print_unescaped($this->inc('_nav'));?><main class="erp-main erp-file-activity-page">
<header class="erp-project-hero"><div><span class="erp-record-kicker">Nextcloud Dateien</span><h1>Dateiaktivitäten</h1><p class="erp-muted">Neue und geänderte Dateien in allen Betrio-Projekten – auch bei Upload über Nextcloud, Desktop-Sync oder WebDAV.</p></div></header>
<section class="erp-card erp-wide"><div class="erp-section-head"><div><h2>Letzte Aktivitäten</h2><p class="erp-muted">Die neuesten Dateiänderungen aus deinen freigegebenen Projekten.</p></div></div>
<?php if(!$activities):?><p class="erp-muted">Noch keine Dateiaktivitäten erfasst. Neue Änderungen werden ab diesem Update protokolliert.</p><?php else:?><div class="erp-file-activity-list">
<?php foreach($activities as $a):?><a class="erp-file-activity-row" href="<?php p($url->linkToRoute('reinhardterp.page.projectExplorer',['id'=>(int)$a['project_id']]));?>"><span class="erp-file-activity-badge"><?php p($labels[$a['action']]??'Datei');?></span><span><strong><?php p(($a['project_no']??'').' · '.($a['project_title']??''));?></strong><small><?php p((string)($a['details']??''));?></small></span><span class="erp-file-activity-meta"><?php p((string)($a['display_name']??$a['created_by']??''));?><br><?php p(date('d.m.Y H:i',strtotime((string)$a['created_at'])));?></span></a><?php endforeach;?></div><?php endif;?></section></main></div></div>
