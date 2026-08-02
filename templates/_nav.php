<?php
use OCP\Util;use OCP\IURLGenerator;use OCA\ReinhardtERP\Service\PermissionService;
$url=\OC::$server->get(IURLGenerator::class);$permissions=\OC::$server->get(PermissionService::class);Util::addStyle('reinhardterp','style');
$requestToken=(string)($_['requesttoken']??'');
$items=[
 ['Dashboard','reinhardterp.page.index','dashboard'],['Kunden','reinhardterp.page.customers','customers'],['Projekte','reinhardterp.page.projects','projects'],['Rapporte','reinhardterp.module.reports','reports'],['Zeiterfassung','reinhardterp.module.workdays','time'],['Zeitauswertung','reinhardterp.module.timeEvaluation','time_billing'],['Abrechnung vorbereiten','reinhardterp.module.invoicePreparation','invoices'],['Material','reinhardterp.module.materials','materials'],['Teamkalender','reinhardterp.module.teamEvents','calendar'],['Benutzer & Rechte','reinhardterp.module.users','users_view'],['Einstellungen','reinhardterp.module.settings','settings']];
?>
<div id="app-navigation"><ul><?php foreach($items as [$label,$route,$permission]):if(!$permissions->can($permission))continue;?><li><a href="<?php p($url->linkToRoute($route)); ?>"><?php p($label); ?></a></li><?php endforeach;?></ul><div class="erp-role-note">Rolle: <?php p($permissions->role());?></div></div>
