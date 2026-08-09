<?php require __DIR__.'/_mobile_pwa.php'; ?>
<?php
use OCP\Util;
Util::addStyle('reinhardterp', 'style');
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
<section class="erp-mobile-about-hero">
  <div class="erp-mobile-about-mark">N</div>
  <h2>NextERP</h2><p>ERP für das Handwerk</p><strong>Handwerk. Einfach. Digital.</strong>
  <span>Web Mobile · 1.2.1</span>
</section>
<section class="erp-mobile-section erp-mobile-info-card">
  <h2>Entwickler</h2>
  <b>André Reinhardt</b>
  <p>Schreinerei Reinhardt</p>
  <a class="erp-mobile-widebutton" href="https://www.schreinerei-reinhardt.de" target="_blank" rel="noopener">Website öffnen</a>
</section>
<section class="erp-mobile-section erp-mobile-coffee-card">
  <h2>☕ Buy me a Coffee</h2>
  <p>Wenn dir NextERP gefällt und du die Entwicklung unterstützen möchtest:</p>
  <code id="nexterp-paypal">andrereinhardt@kassel-net.de</code>
  <button type="button" class="erp-mobile-widebutton" onclick="navigator.clipboard.writeText('andrereinhardt@kassel-net.de');this.textContent='PayPal-Adresse kopiert ✓'">PayPal-Adresse kopieren</button>
</section>
<section class="erp-mobile-section erp-mobile-info-card"><h2>Angemeldet als</h2><b><?php p($_['displayName'] ?? ''); ?></b><p><?php p($_['role'] ?? ''); ?></p></section>
<p class="erp-mobile-copyright">© 2026 André Reinhardt · NextERP</p>

<?php elseif ($view === 'projects'): ?>
<section class="erp-mobile-section"><div class="erp-mobile-sectionhead"><h2>Meine Projekte</h2><span><?php p((string)count($projects)); ?></span></div>
<?php if (!$projects): ?><div class="erp-mobile-empty">Dir sind aktuell keine Projekte freigegeben.</div><?php else: ?><div class="erp-mobile-projects">
<?php foreach($projects as $project): ?><a class="erp-mobile-project" href="<?php p($url->linkToRoute('reinhardterp.page.projectDetail',['id'=>(int)$project['id']])); ?>"><span class="erp-mobile-projecticon"><span class="erp-ui-icon erp-icon-project"></span></span><span><small><?php p($project['project_no'] ?? 'Projekt'); ?></small><b><?php p($project['title'] ?? ''); ?></b><em><?php p($project['status'] ?? ''); ?></em></span><i>›</i></a><?php endforeach; ?>
</div><?php endif; ?></section>

<?php else: ?>
<section class="erp-mobile-hero"><div><span>Heute erfasst</span><strong><?php p(number_format((float)$_['todayHours'],2,',','.')); ?> h</strong></div><div><span>Meine Projekte</span><strong><?php p((string)count($projects)); ?></strong></div></section>
<section class="erp-mobile-section"><div class="erp-mobile-sectionhead"><h2>Schnellzugriff</h2></div><div class="erp-mobile-actiongrid">
<a href="<?php p($url->linkToRoute('reinhardterp.business.mobileTime')); ?>"><span class="erp-ui-icon erp-icon-time"></span><b>Zeit erfassen</b><small>Arbeitszeit buchen</small></a>
<a href="<?php p($url->linkToRoute('reinhardterp.module.reports')); ?>"><span class="erp-ui-icon erp-icon-report"></span><b>Rapporte</b><small>Öffnen & unterschreiben</small></a>
<a href="<?php p($url->linkToRoute('reinhardterp.business.mobileMaterial')); ?>"><span class="erp-ui-icon erp-icon-material"></span><b>Material</b><small>Bestand & Entnahme</small></a>
<a href="<?php p($url->linkToRoute('reinhardterp.document.index')); ?>"><span class="erp-ui-icon erp-icon-document"></span><b>Dokumente</b><small>Projektunterlagen</small></a>
<a href="<?php p($mobileUrl('projects')); ?>"><span class="erp-ui-icon erp-icon-project"></span><b>Projekte</b><small>Meine Baustellen</small></a>
<a href="<?php p($url->linkToRoute('reinhardterp.page.projects')); ?>"><span class="erp-ui-icon erp-icon-image"></span><b>Fotos</b><small>Über Projekt öffnen</small></a>
</div></section>
<section class="erp-mobile-section"><div class="erp-mobile-sectionhead"><h2>Meine Projekte</h2><a href="<?php p($mobileUrl('projects')); ?>">Alle</a></div><?php if(!$projects):?><div class="erp-mobile-empty">Dir sind aktuell keine Projekte freigegeben.</div><?php else:?><div class="erp-mobile-projects"><?php foreach(array_slice($projects,0,5) as $project):?><a class="erp-mobile-project" href="<?php p($url->linkToRoute('reinhardterp.page.projectDetail',['id'=>(int)$project['id']])); ?>"><span class="erp-mobile-projecticon"><span class="erp-ui-icon erp-icon-project"></span></span><span><small><?php p($project['project_no']??'Projekt');?></small><b><?php p($project['title']??'');?></b><em><?php p($project['status']??'');?></em></span><i>›</i></a><?php endforeach;?></div><?php endif;?></section>
<section class="erp-mobile-section"><div class="erp-mobile-sectionhead"><h2>Letzte Buchungen</h2></div><?php if(!$recent):?><div class="erp-mobile-empty">Noch keine Buchungen vorhanden.</div><?php endif;?><?php foreach(array_slice($recent,0,3) as $r):?><div class="erp-mobile-booking"><span><b><?php p(trim(($r['project_no']??'').' '.($r['title']??'')));?></b><small><?php p(date('d.m.Y',strtotime($r['work_date'])).' · '.number_format((float)$r['hours'],2,',','.').' h');?></small></span><em><?php p($r['activity']??'');?></em></div><?php endforeach;?></section>
<?php endif; ?>


</div></div>



<!-- Viewport navigation: intentionally outside Nextcloud #app-content -->
<nav id="nexterp-mobile-bottom-nav" class="erp-mobile-bottom" aria-label="Mobile Navigation">
<a class="<?php p($view==='today'?'active':''); ?>" href="<?php p($mobileUrl('today')); ?>"><span class="erp-ui-icon erp-icon-dashboard"></span><b>Heute</b></a>
<a class="<?php p($view==='projects'?'active':''); ?>" href="<?php p($mobileUrl('projects')); ?>"><span class="erp-ui-icon erp-icon-project"></span><b>Projekte</b></a>
<a href="<?php p($url->linkToRoute('reinhardterp.page.customers')); ?>"><span class="erp-ui-icon erp-icon-customer"></span><b>Kunden</b></a>
<a href="<?php p($url->linkToRoute('reinhardterp.business.mobileMaterial')); ?>"><span class="erp-ui-icon erp-icon-search"></span><b>Scanner</b></a>
<a href="<?php p($url->linkToRoute('reinhardterp.business.mobileMaterial')); ?>"><span class="erp-ui-icon erp-icon-material"></span><b>Material</b></a>
<a href="<?php p($url->linkToRoute('reinhardterp.document.index')); ?>"><span class="erp-ui-icon erp-icon-document"></span><b>Dokumente</b></a>
<a class="<?php p($view==='more'?'active':''); ?>" href="<?php p($mobileUrl('more')); ?>"><span class="erp-mobile-moredots">•••</span><b>Mehr</b></a>
</nav>

<style id="nexterp-mobile-viewport-nav">
html body #nexterp-mobile-bottom-nav{
 position:fixed!important;
 inset:auto 0 0 0!important;
 width:100vw!important;
 height:auto!important;
 margin:0!important;
 z-index:2147483640!important;
 transform:none!important;
 background:#fff!important;
 border-top:1px solid #dde3ea!important;
 box-shadow:0 -5px 18px rgba(15,23,42,.10)!important;
 padding-bottom:env(safe-area-inset-bottom)!important;
}
.erp-mobile-app{padding-bottom:calc(100px + env(safe-area-inset-bottom))!important}
</style>

