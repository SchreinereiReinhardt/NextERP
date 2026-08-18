<?php require __DIR__.'/_mobile_pwa.php'; ?>
<?php
$url = $_['urlGenerator'];
$projects = $_['projects'] ?? [];
$recent = $_['recent'] ?? [];
$view = $_['view'] ?? 'today';
$base = $url->linkToRoute('reinhardterp.business.mobile');
$mobileUrl = static fn(string $v): string => $base.'?view='.rawurlencode($v);
$hour = (int)date('G');
$greeting = $hour >= 5 && $hour <= 10 ? 'Guten Morgen' : ($hour <= 16 ? 'Guten Tag' : ($hour <= 21 ? 'Guten Abend' : 'Willkommen zurück'));
?>
<div id="app-content"><div class="erp-page erp-mobile-app">
<header class="erp-mobile-appbar">
  <div><small>NEXTERP MOBILE</small><h1><?php p($view==='today' ? $greeting.', '.($_['displayName'] ?? '') : match($view){'projects'=>'Projekte','more'=>'Mehr',default=>'NextERP Mobile'}); ?></h1><p><?php p(date('d.m.Y')); ?> · <?php p($_['role'] ?? ''); ?></p></div>
  <a class="erp-mobile-refresh" href="<?php p($base.'?view='.rawurlencode($view)); ?>" aria-label="Aktualisieren">↻</a>
</header>

<?php if ($view === 'more'): ?>
<section class="erp-mobile-install" id="nexterp-install-card">
  <h2>NextERP wie eine App nutzen</h2>
  <p>Auf dem Startbildschirm installieren, im Vollbild öffnen und schneller in den Arbeitsalltag starten.</p>
  <button type="button" id="nexterp-install-button">Zum Startbildschirm</button>
  <small id="nexterp-install-hint">Falls kein Dialog erscheint: Browsermenü → „Zum Startbildschirm hinzufügen“.</small>
</section>
<section class="erp-mobile-section"><div class="erp-mobile-sectionhead"><h2>Werkzeuge</h2></div><div class="erp-mobile-moregrid">
 <a href="<?php p($url->linkToRoute('reinhardterp.business.mobileMaterial')); ?>"><b>Material & Scanner</b><small>Artikel suchen, scannen und entnehmen</small></a>
 <a href="<?php p($url->linkToRoute('reinhardterp.module.reports')); ?>"><b>Rapporte</b><small>Offene Rapporte verwalten</small></a>
 <a href="<?php p($url->linkToRoute('reinhardterp.document.index')); ?>"><b>Dokumente</b><small>Belege und Unterlagen</small></a>
 <a href="<?php p($url->linkToRoute('reinhardterp.page.customers')); ?>"><b>Kunden</b><small>Kontaktdaten und Kundenakten</small></a>
</div></section>
<section class="erp-mobile-section erp-mobile-info-card"><h2>Angemeldet als</h2><b><?php p($_['displayName'] ?? ''); ?></b><p><?php p($_['role'] ?? ''); ?></p></section>
<section class="erp-mobile-section erp-mobile-info-card"><h2>NextERP</h2><p>ERP für das Handwerk · Mobile Web/PWA</p><p>Entwickler: André Reinhardt · Schreinerei Reinhardt</p></section>
<p class="erp-mobile-copyright">© 2026 André Reinhardt · NextERP</p>
<script>
(function(){let deferred=null,btn=document.getElementById('nexterp-install-button'),hint=document.getElementById('nexterp-install-hint');if(!btn)return;
window.addEventListener('beforeinstallprompt',function(e){e.preventDefault();deferred=e;btn.textContent='NextERP installieren';hint.textContent='Installation ist auf diesem Gerät verfügbar.';});
btn.addEventListener('click',async function(){if(deferred){deferred.prompt();try{await deferred.userChoice;}catch(e){}deferred=null;return;} hint.textContent='Browsermenü öffnen und „Zum Startbildschirm hinzufügen“ bzw. „App installieren“ wählen.';});})();
</script>

<?php elseif ($view === 'projects'): ?>
<section class="erp-mobile-section"><div class="erp-mobile-sectionhead"><h2>Meine Projekte</h2><span><?php p((string)count($projects)); ?></span></div>
<div class="erp-mobile-search"><span>⌕</span><input id="nexterp-project-search" type="search" placeholder="Projekt, Nummer oder Status suchen" autocomplete="off"></div>
<?php if (!$projects): ?><div class="erp-mobile-empty">Dir sind aktuell keine Projekte freigegeben.</div><?php else: ?><div class="erp-mobile-projects" id="nexterp-project-list">
<?php foreach($projects as $project): ?><a class="erp-mobile-project" data-search="<?php p(strtolower(trim(($project['project_no']??'').' '.($project['title']??'').' '.($project['status']??'')))); ?>" href="<?php p($url->linkToRoute('reinhardterp.business.mobileProject',['id'=>(int)$project['id']])); ?>"><span class="erp-mobile-projecticon"><span class="erp-ui-icon erp-icon-project"></span></span><span><small><?php p($project['project_no'] ?? 'Projekt'); ?></small><b><?php p($project['title'] ?? ''); ?></b><em><?php p($project['status'] ?? ''); ?></em></span><i>›</i></a><?php endforeach; ?>
</div><?php endif; ?></section>
<script>(function(){const q=document.getElementById('nexterp-project-search'),rows=[...document.querySelectorAll('#nexterp-project-list [data-search]')];if(!q)return;q.addEventListener('input',()=>{const v=q.value.trim().toLowerCase();rows.forEach(r=>r.style.display=!v||r.dataset.search.includes(v)?'grid':'none')})})();</script>

<?php else: ?>
<section class="erp-mobile-hero"><div><span>Heute erfasst</span><strong><?php p(number_format((float)$_['todayHours'],2,',','.')); ?> h</strong></div><div><span>Meine Projekte</span><strong><?php p((string)count($projects)); ?></strong></div></section>
<section class="erp-mobile-section"><div class="erp-mobile-sectionhead"><h2>Schnellzugriff</h2></div><div class="erp-mobile-actiongrid">
<a href="<?php p($url->linkToRoute('reinhardterp.business.mobileTime')); ?>"><span class="erp-ui-icon erp-icon-time"></span><b>Zeit erfassen</b><small>Arbeitszeit in wenigen Sekunden buchen</small></a>
<a href="<?php p($mobileUrl('projects')); ?>"><span class="erp-ui-icon erp-icon-project"></span><b>Projekte</b><small>Baustelle öffnen und alles erreichen</small></a>
<a href="<?php p($url->linkToRoute('reinhardterp.business.mobileMaterial')); ?>"><span class="erp-ui-icon erp-icon-search"></span><b>Scanner</b><small>Material suchen, scannen und entnehmen</small></a>
<a href="<?php p($mobileUrl('more')); ?>"><span class="erp-ui-icon erp-icon-document"></span><b>Mehr</b><small>Rapporte, Dokumente, Kunden & weitere Werkzeuge</small></a>
</div></section>
<section class="erp-mobile-section"><div class="erp-mobile-sectionhead"><h2>Meine Projekte</h2><a href="<?php p($mobileUrl('projects')); ?>">Alle</a></div><?php if(!$projects):?><div class="erp-mobile-empty">Dir sind aktuell keine Projekte freigegeben.</div><?php else:?><div class="erp-mobile-projects"><?php foreach(array_slice($projects,0,5) as $project):?><a class="erp-mobile-project" href="<?php p($url->linkToRoute('reinhardterp.business.mobileProject',['id'=>(int)$project['id']])); ?>"><span class="erp-mobile-projecticon"><span class="erp-ui-icon erp-icon-project"></span></span><span><small><?php p($project['project_no']??'Projekt');?></small><b><?php p($project['title']??'');?></b><em><?php p($project['status']??'');?></em></span><i>›</i></a><?php endforeach;?></div><?php endif;?></section>
<section class="erp-mobile-section"><div class="erp-mobile-sectionhead"><h2>Letzte Buchungen</h2></div><?php if(!$recent):?><div class="erp-mobile-empty">Noch keine Buchungen vorhanden.</div><?php endif;?><?php foreach(array_slice($recent,0,3) as $r):?><div class="erp-mobile-booking"><span><b><?php p(trim(($r['project_no']??'').' '.($r['title']??'')));?></b><small><?php p(date('d.m.Y',strtotime($r['work_date'])).' · '.number_format((float)$r['hours'],2,',','.').' h');?></small></span><em><?php p($r['activity']??'');?></em></div><?php endforeach;?></section>
<?php endif; ?>
</div></div>
<?php $mobileActive=$view==='projects'?'projects':($view==='more'?'more':'today'); require __DIR__.'/_mobile_nav.php'; ?>
