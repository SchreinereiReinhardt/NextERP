<?php
style('reinhardterp','style');
$url=\OC::$server->getURLGenerator();
?>
<div id="app-content"><div id="app-content-wrapper"><?php print_unescaped($this->inc('_nav')); ?><main class="erp-main erp-dashboard-page">
<div class="erp-head erp-dashboard-head"><div><span class="erp-record-kicker">NextERP</span><h1>Guten Tag, <?=p($displayName)?></h1><p class="erp-sub"><?=p(date('d.m.Y'))?> · Ihr aktueller Betriebsüberblick</p></div><div class="erp-actions"><a class="button primary" href="<?=p($url->linkToRoute('reinhardterp.page.customerForm'))?>">+ Kunde</a><a class="button" href="<?=p($url->linkToRoute('reinhardterp.page.projectForm'))?>">+ Projekt</a><a class="button" href="<?=p($url->linkToRoute('reinhardterp.module.reports'))?>">+ Rapport</a></div></div>
<div class="erp-kpis erp-dashboard-kpis">
<a class="erp-kpi-card" href="<?=p($url->linkToRoute('reinhardterp.page.customers'))?>"><span class="erp-kpi-icon"><i class="erp-ui-icon erp-icon-customer"></i></span><span>Kunden</span><strong><?=p($customerCount)?></strong><small>Kundenakten öffnen</small></a>
<a class="erp-kpi-card" href="<?=p($url->linkToRoute('reinhardterp.page.projects'))?>"><span class="erp-kpi-icon"><i class="erp-ui-icon erp-icon-project"></i></span><span>Projekte</span><strong><?=p($projectCount)?></strong><small>Projektübersicht öffnen</small></a>
<a class="erp-kpi-card" href="<?=p($url->linkToRoute('reinhardterp.module.reports'))?>"><span class="erp-kpi-icon"><i class="erp-ui-icon erp-icon-report"></i></span><span>Offene Rapporte</span><strong><?=p($openReportCount)?></strong><small>Rapporte prüfen</small></a>
<a class="erp-kpi-card" href="<?=p($url->linkToRoute('reinhardterp.module.workdays'))?>"><span class="erp-kpi-icon"><i class="erp-ui-icon erp-icon-time"></i></span><span>Stunden heute</span><strong><?=p(number_format((float)$todayHours,2,',','.'))?></strong><small>Zeiterfassung öffnen</small></a>
</div>
<section class="erp-card erp-dashboard-events">
<div class="erp-section-head"><div><h2><span class="erp-ui-icon erp-icon-calendar erp-section-icon"></span>Fällige Termine</h2><p class="erp-muted">Heute und kommende Termine</p></div><a class="button" href="<?=p($url->linkToRoute('reinhardterp.module.teamEvents'))?>">Teamkalender öffnen</a></div>
<?php if (!empty($upcomingEvents)): ?>
<div class="erp-upcoming-events">
<?php foreach ($upcomingEvents as $event):
 $start=new DateTimeImmutable((string)$event['start_at']);
 $today=new DateTimeImmutable('today');
 $tomorrow=$today->modify('+1 day');
 if ($start->format('Y-m-d')===$today->format('Y-m-d')) {$dayLabel='Heute';}
 elseif ($start->format('Y-m-d')===$tomorrow->format('Y-m-d')) {$dayLabel='Morgen';}
 else {$dayLabel=$start->format('D, d.m.');}
?>
<a class="erp-upcoming-event" href="<?=p($url->linkToRoute('reinhardterp.module.teamEvents'))?>">
<span class="erp-event-date"><strong><?=p($dayLabel)?></strong><small><?=p($start->format('H:i'))?> Uhr</small></span>
<span class="erp-event-main"><strong><?=p($event['title'])?></strong><small><?=p($event['location'] ?: 'Kein Ort hinterlegt')?></small></span>
<span class="erp-event-source"><?=p(($event['sync_source'] ?? '')==='nextcloud' ? 'Nextcloud' : 'NextERP')?></span>
</a>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="erp-empty erp-dashboard-empty">Keine Termine für heute oder die Zukunft vorhanden.</div>
<?php endif; ?>
</section>
<div class="erp-dashboard-layout"><section class="erp-card erp-quick-card"><div class="erp-section-head"><div><h2><span class="erp-ui-icon erp-icon-plus erp-section-icon"></span>Schnellzugriff</h2><p class="erp-muted">Häufig benötigte Bereiche</p></div></div><div class="erp-quick-grid"><a href="<?=p($url->linkToRoute('reinhardterp.page.customers'))?>"><span class="erp-ui-icon erp-icon-customer"></span><strong>Kundenakten</strong></a><a href="<?=p($url->linkToRoute('reinhardterp.page.projects'))?>"><span class="erp-ui-icon erp-icon-project"></span><strong>Projektakten</strong></a><a href="<?=p($url->linkToRoute('reinhardterp.module.workdays'))?>"><span class="erp-ui-icon erp-icon-time"></span><strong>Zeiterfassung</strong></a><a href="<?=p($url->linkToRoute('reinhardterp.module.timeEvaluation'))?>"><span class="erp-ui-icon erp-icon-statistics"></span><strong>Zeitauswertung</strong></a><a href="<?=p($url->linkToRoute('reinhardterp.module.reports'))?>"><span class="erp-ui-icon erp-icon-report"></span><strong>Rapporte</strong></a><a href="<?=p($url->linkToRoute('reinhardterp.module.materials'))?>"><span class="erp-ui-icon erp-icon-material"></span><strong>Material</strong></a></div></section><section class="erp-card erp-activity-card"><div class="erp-section-head"><div><h2><span class="erp-ui-icon erp-icon-activity erp-section-icon"></span>Letzte Aktivitäten</h2><p class="erp-muted">Neueste Änderungen in NextERP</p></div></div><?php print_unescaped($this->inc('_activity_timeline',['activities'=>$activities])); ?></section></div>
</main></div></div>
