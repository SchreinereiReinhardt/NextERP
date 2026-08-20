<?php
style('reinhardterp','style');
$url=\OC::$server->get(\OCP\IURLGenerator::class);
$can=$can??[];
$roleNames=['admin'=>'Administration','office'=>'Büro','manager'=>'Projektleitung','employee'=>'Monteur','time'=>'Zeiterfassung'];
$roleLabel=$roleNames[$role]??'Betrio';
?>
<div id="app-content"><div id="app-content-wrapper"><?php print_unescaped($this->inc('_nav')); ?><main class="erp-main erp-dashboard-page erp-dashboard-v2">
<div class="erp-head erp-dashboard-head"><div><span class="erp-record-kicker">Betrio · <?=p($roleLabel)?></span><h1>Guten Tag, <?=p($displayName)?></h1><p class="erp-sub"><?=p(date('d.m.Y'))?> · Was heute im Betrieb wichtig ist</p></div><div class="erp-actions">
<?php if($can['customers']??false): ?><a class="button primary" href="<?=p($url->linkToRoute('reinhardterp.page.customerForm'))?>">+ Kunde</a><?php endif; ?>
<?php if(($can['projects']??false) && in_array($role,['admin','office','manager'],true)): ?><a class="button" href="<?=p($url->linkToRoute('reinhardterp.page.projectForm'))?>">+ Projekt</a><?php endif; ?>
<?php if($can['reports']??false): ?><a class="button" href="<?=p($url->linkToRoute('reinhardterp.module.reports'))?>">+ Rapport</a><?php endif; ?>
</div></div>
<div class="erp-kpis erp-dashboard-kpis">
<?php if($can['customers']??false): ?><a class="erp-kpi-card" href="<?=p($url->linkToRoute('reinhardterp.page.customers'))?>"><span class="erp-kpi-icon"><i class="erp-ui-icon erp-icon-customer"></i></span><span>Kunden</span><strong><?=p($customerCount)?></strong><small>Kundenakten</small></a><?php endif; ?>
<a class="erp-kpi-card" href="<?=p($url->linkToRoute('reinhardterp.page.projects'))?>"><span class="erp-kpi-icon"><i class="erp-ui-icon erp-icon-project"></i></span><span><?=p(in_array($role,['employee','time'],true)?'Meine Projekte':'Aktive Projekte')?></span><strong><?=p($projectCount)?></strong><small>Projektübersicht</small></a>
<?php if($can['reports']??false): ?><a class="erp-kpi-card" href="<?=p($url->linkToRoute('reinhardterp.module.reports'))?>"><span class="erp-kpi-icon"><i class="erp-ui-icon erp-icon-report"></i></span><span>Offene Rapporte</span><strong><?=p($openReportCount)?></strong><small>Rapporte prüfen</small></a><?php endif; ?>
<?php if($can['time']??false): ?><a class="erp-kpi-card" href="<?=p($url->linkToRoute('reinhardterp.module.workdays'))?>"><span class="erp-kpi-icon"><i class="erp-ui-icon erp-icon-time"></i></span><span><?=p(in_array($role,['employee','time'],true)?'Meine Stunden heute':'Stunden heute')?></span><strong><?=p(number_format((float)$todayHours,2,',','.'))?></strong><small>Zeiterfassung</small></a><?php endif; ?>
</div>
<section class="erp-card erp-attention-card"><div class="erp-section-head"><div><h2><span class="erp-ui-icon erp-icon-activity erp-section-icon"></span>Jetzt wichtig</h2><p class="erp-muted">Automatisch aus den vorhandenen ERP-Daten ermittelt</p></div></div><div class="erp-attention-grid">
<?php foreach($attention as $item): $href=!empty($item['route'])?$url->linkToRoute($item['route']):''; ?>
<?php if($href!==''): ?><a class="erp-attention-item erp-attention-<?=p($item['kind'])?>" href="<?=p($href)?>"><?php else: ?><div class="erp-attention-item erp-attention-<?=p($item['kind'])?>"><?php endif; ?>
<span class="erp-attention-count"><?=p($item['count'])?></span><span><strong><?=p($item['title'])?></strong><small><?=p($item['text'])?></small></span>
<?php if($href!==''): ?></a><?php else: ?></div><?php endif; ?>
<?php endforeach; ?>
</div></section>
<?php if($can['calendar']??false): ?><section class="erp-card erp-dashboard-events"><div class="erp-section-head"><div><h2><span class="erp-ui-icon erp-icon-calendar erp-section-icon"></span>Nächste Termine</h2><p class="erp-muted">Heute und kommende Termine</p></div><a class="button" href="<?=p($url->linkToRoute('reinhardterp.module.teamEvents'))?>">Teamkalender</a></div>
<?php if(!empty($upcomingEvents)): ?><div class="erp-upcoming-events"><?php foreach($upcomingEvents as $event): $start=new DateTimeImmutable((string)$event['start_at']);$today=new DateTimeImmutable('today');$tomorrow=$today->modify('+1 day');$dayLabel=$start->format('Y-m-d')===$today->format('Y-m-d')?'Heute':($start->format('Y-m-d')===$tomorrow->format('Y-m-d')?'Morgen':$start->format('D, d.m.')); ?><a class="erp-upcoming-event" href="<?=p($url->linkToRoute('reinhardterp.module.teamEvents'))?>"><span class="erp-event-date"><strong><?=p($dayLabel)?></strong><small><?=p($start->format('H:i'))?> Uhr</small></span><span class="erp-event-main"><strong><?=p($event['title'])?></strong><small><?=p($event['location']?:'Kein Ort hinterlegt')?></small></span><span class="erp-event-source"><?=p(($event['sync_source']??'')==='nextcloud'?'Nextcloud':'Betrio')?></span></a><?php endforeach; ?></div><?php else: ?><div class="erp-empty erp-dashboard-empty">Keine kommenden Termine vorhanden.</div><?php endif; ?></section><?php endif; ?>
<div class="erp-dashboard-layout erp-dashboard-v2-layout"><section class="erp-card"><div class="erp-section-head"><div><h2><span class="erp-ui-icon erp-icon-project erp-section-icon"></span>Aktuelle Projekte</h2><p class="erp-muted">Schneller Einstieg in die laufende Arbeit</p></div><a class="button" href="<?=p($url->linkToRoute('reinhardterp.page.projects'))?>">Alle Projekte</a></div>
<?php if($recentProjects): ?><div class="erp-dashboard-projects"><?php foreach($recentProjects as $project): ?><a href="<?=p($url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$project['id']]))?>"><span><strong><?=p($project['project_no'])?> · <?=p($project['title'])?></strong><small>Status: <?=p($project['status'])?><?php if(!empty($project['due_date'])): ?> · Fällig <?=p(date('d.m.Y',strtotime($project['due_date'])))?><?php endif; ?></small></span><b>›</b></a><?php endforeach; ?></div><?php else: ?><div class="erp-empty">Keine aktiven Projekte vorhanden.</div><?php endif; ?></section>
<section class="erp-card erp-quick-card"><div class="erp-section-head"><div><h2><span class="erp-ui-icon erp-icon-plus erp-section-icon"></span>Schnellzugriff</h2><p class="erp-muted">Direkt in die tägliche Arbeit</p></div></div><div class="erp-quick-grid">
<a href="<?=p($url->linkToRoute('reinhardterp.page.projects'))?>"><span class="erp-ui-icon erp-icon-project"></span><strong>Projektakten</strong></a>
<?php if($can['time']??false): ?><a href="<?=p($url->linkToRoute('reinhardterp.module.workdays'))?>"><span class="erp-ui-icon erp-icon-time"></span><strong>Zeiterfassung</strong></a><?php endif; ?>
<?php if($can['reports']??false): ?><a href="<?=p($url->linkToRoute('reinhardterp.module.reports'))?>"><span class="erp-ui-icon erp-icon-report"></span><strong>Rapporte</strong></a><?php endif; ?>
<?php if($can['documents']??false): ?><a href="<?=p($url->linkToRoute('reinhardterp.document.index'))?>"><span class="erp-ui-icon erp-icon-document"></span><strong>Dokumenteingang</strong></a><?php endif; ?>
<?php if($can['invoices']??false): ?><a href="<?=p($url->linkToRoute('reinhardterp.document.finance'))?>"><span class="erp-ui-icon erp-icon-statistics"></span><strong>Finanzen</strong></a><?php endif; ?>
<?php if($can['inventory']??false): ?><a href="<?=p($url->linkToRoute('reinhardterp.business.inventory'))?>"><span class="erp-ui-icon erp-icon-material"></span><strong>Lager</strong></a><?php endif; ?>
</div></section></div>
<section class="erp-card erp-activity-card erp-dashboard-activity"><div class="erp-section-head"><div><h2><span class="erp-ui-icon erp-icon-activity erp-section-icon"></span>Letzte Aktivitäten</h2><p class="erp-muted">Neueste Änderungen in Betrio</p></div></div><?php print_unescaped($this->inc('_activity_timeline',['activities'=>$activities])); ?></section>
</main></div></div>
