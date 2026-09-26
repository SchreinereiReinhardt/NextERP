<?php
style('reinhardterp','style'); script('reinhardterp','project'); script('reinhardterp','project_permissions');
$url=\OC::$server->get(\OCP\IURLGenerator::class); $projectPath=trim((string)($project['folder_path']??''),'/'); $filesBase=$url->linkToRoute('files.view.index');
$folderUrl=static fn(string $path):string=>$filesBase.'?dir='.rawurlencode('/'.trim($path,'/'));
$fileUrl=static function(string $path)use($filesBase):string{$path=trim($path,'/');return $filesBase.'?dir='.rawurlencode(dirname('/'.$path)).'&scrollto='.rawurlencode(basename($path));};
$formatSize=static function(int $b):string{if($b<1024)return $b.' B';if($b<1048576)return number_format($b/1024,1,',','.').' KB';return number_format($b/1048576,1,',','.').' MB';};
$totalHours=array_sum(array_map(static fn($x)=>(float)$x['hours'],$times)); $statuses=['Anfrage','Angebot','Auftrag','Fertigung','Montage','Abnahme','Abrechnung','Abgeschlossen'];
$currentStatus=(string)($project['status']??'Anfrage');$idx=array_search(strtolower($currentStatus),array_map('strtolower',$statuses),true);$progress=$idx===false?0:(int)round(($idx/(count($statuses)-1))*100);
$cockpit=$cockpit??[];$projectCosts=$projectCosts??[];$offers=$offers??[];$orders=$orders??[];$invoices=$invoices??[];$projectEvents=$projectEvents??[];$documentRecords=$documentRecords??[];$projectNotes=$projectNotes??[];$projectChecklist=$projectChecklist??[];$documentTags=$documentTags??[];$selectedDocumentTag=$selectedDocumentTag??'';$projectSuppliers=$projectSuppliers??[];$suppliers=$suppliers??[];$supplierDocuments=$supplierDocuments??[];
$money=static fn($v):string=>number_format((float)$v,2,',','.').' €';
$memberMap=[];foreach(($projectMembers??[]) as $m)$memberMap[(string)$m['user_id']]=$m;
$openProjectFile=static fn(string $path):string=>$url->linkToRoute('reinhardterp.page.projectFile',['id'=>$project['id'],'path'=>$path]);
$folderLabels=['00_Eingang'=>'Eingang','01_Aufmass'=>'Aufmaß','02_Planung'=>'Planung','03_Zeichnungen'=>'Zeichnungen','04_Material'=>'Material','05_Bestellungen'=>'Bestellungen','06_Rapporte'=>'Rapporte','07_Fotos'=>'Fotos','08_Abnahme'=>'Abnahme','09_Rechnung'=>'Rechnung','10_Angebote'=>'Angebote','11_Auftraege'=>'Aufträge','12_Sonstiges'=>'Sonstiges'];
?>
<div id="app-content"><div id="app-content-wrapper"><?php print_unescaped($this->inc('_nav')); ?><main class="erp-main erp-project-center">
<header class="erp-project-hero"><div class="erp-project-identity"><span class="erp-record-kicker">Digitale Projektakte · <?=p($project['project_no'])?></span><h1><?=p($project['title'])?></h1><p><a href="<?=p($url->linkToRoute('reinhardterp.page.customerDetail',['id'=>$project['customer_id']]))?>"><?=p($project['customer_no'].' '.$project['customer_name'])?></a> · <strong><?=p($currentStatus)?></strong></p><div class="erp-project-progress"><i style="width:<?=p($progress)?>%"></i></div></div><div class="erp-project-hero-actions"><?php if($projectPath!==''):?><a class="button primary" href="<?=p($url->linkToRoute('reinhardterp.page.projectExplorer',['id'=>$project['id']]))?>"><span class="erp-ui-icon erp-icon-folder"></span>Projektordner</a><?php endif;?><a class="button" href="<?=p($url->linkToRoute('reinhardterp.module.reports',['projectId'=>$project['id']]))?>">+ Rapport</a><a class="button" href="<?=p($url->linkToRoute('reinhardterp.module.workdays'))?>">+ Zeit</a><?php if(!empty($isProjectSupervisor)):?><details class="erp-project-more"><summary class="button">Mehr</summary><div class="erp-project-more-menu"><a href="<?=p($url->linkToRoute('reinhardterp.page.projectForm',['id'=>$project['id']]))?>">Projekt bearbeiten</a><?php if($projectPath!==''):?><a target="_blank" rel="noopener" href="<?=p($folderUrl($projectPath))?>">In Nextcloud öffnen</a><?php endif;?></div></details><?php endif;?></div></header>
<nav class="erp-project-center-nav" aria-label="Projektcenter"><a href="#overview">Übersicht</a><a href="#appointments">Termine</a><?php if(!empty($isProjectSupervisor)):?><a href="#offers">Angebote</a><a href="#orders">Aufträge</a><a href="#invoices">Rechnungen</a><?php endif;?><a href="#reports">Rapporte</a><a href="#time">Zeiten</a><a href="#material">Material</a><?php if(!empty($isProjectSupervisor)):?><a href="#suppliers">Lieferanten</a><?php endif;?><a href="#notes">Notizen</a><a href="#checklist">Checkliste</a><a href="#documents">Dokumente</a><details class="erp-project-nav-more"><summary>Mehr</summary><div><a href="#photos">Fotos</a><a href="#permissions">Freigaben</a><?php if(!empty($isProjectSupervisor)):?><a href="#costs">Kosten</a><?php endif;?><a href="#timeline">Timeline</a></div></details></nav>
<section id="overview" class="erp-project-center-metrics"><article><span>Fortschritt</span><strong><?=p($progress)?> %</strong><small><?=p($currentStatus)?></small></article><article><span>Arbeitszeit</span><strong><?=p(number_format($totalHours,2,',','.'))?> h</strong><small><?=count($times)?> Buchungen</small></article><article><span>Rapporte</span><strong><?=count($reports)?></strong><small><?=count(array_filter($reports,static fn($r)=>empty($r['locked'])))?> offen</small></article><article><span>Projektwert</span><strong><?=p($money($projectCosts['projectValue']??0))?></strong><small><?=($projectCosts['orderValue']??0)>0?'Aufträge':'Angebote'?></small></article><article><span>Dokumente</span><strong><?=count($documents)?></strong><small><?=count($documentRecords)?> klassifiziert</small></article></section>
<section class="erp-card erp-permissions-compact" id="permissions"><div class="erp-permission-summary"><div><h2>Mitarbeiter & Freigaben</h2><?php if(!empty($canManageAssignments)):?><?php $assignedSummaries=[];foreach(($assignmentUsers??[]) as $u){$uid=(string)$u['uid'];if(isset($memberMap[$uid])){$folders=$memberMap[$uid]['folders']??[];$assignedSummaries[]=(string)$u['displayName'].' · '.count($folders).' Ordner';}}?><p class="erp-muted"><?php p(!empty($assignedSummaries)?implode(' · ',$assignedSummaries):'Noch keinem Monteur zugewiesen.'); ?></p><?php else:?><p class="erp-muted">Deine Projektfreigaben</p><?php endif;?></div><?php if(!empty($canManageAssignments)):?><button type="button" class="button erp-permission-toggle" aria-expanded="false" aria-controls="erp-permission-editor">Bearbeiten</button><?php endif;?></div>
<?php if(!empty($canManageAssignments)):?>
<div id="erp-permission-editor" class="erp-permission-editor" hidden><p class="erp-muted">Monteure sehen nur zugewiesene Projekte und die hier freigegebenen Projektordner.</p><form method="post" action="<?=p($url->linkToRoute('reinhardterp.project.saveAssignments',['id'=>$project['id']]))?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><div class="erp-permission-list">
<?php if(empty($assignmentUsers)):?><p class="erp-muted">Keine aktiven Monteure/Mitarbeiter vorhanden.</p><?php else:?><?php foreach($assignmentUsers as $u):$uid=(string)$u['uid'];$member=$memberMap[$uid]??null;$selected=$member!==null;$memberFolders=$member['folders']??\OCA\ReinhardtERP\Service\PermissionService::EMPLOYEE_DEFAULT_FOLDERS;?>
<article class="erp-permission-user" data-permission-user>
<div class="erp-permission-user-head"><label class="erp-project-access"><input type="checkbox" name="projectUsers[]" value="<?=p($uid)?>" <?=$selected?'checked':''?>> <span><strong><?=p($u['displayName'])?></strong><small><?=p($u['role'])?></small></span></label><div class="erp-permission-actions"><button type="button" class="button erp-permission-all" data-action="all">Alle</button><button type="button" class="button erp-permission-none" data-action="none">Keine</button></div></div>
<div class="erp-folder-groups"><div><span class="erp-permission-group-title">Projekt</span><div class="erp-folder-permissions"><?php foreach(array_slice($folderLabels,0,9,true) as $folder=>$label):?><label><input type="checkbox" name="folders[<?=p($uid)?>][]" value="<?=p($folder)?>" <?=in_array($folder,$memberFolders,true)?'checked':''?>><span><?=p($label)?></span></label><?php endforeach;?></div></div><div><span class="erp-permission-group-title">Kaufmännisch & Sonstiges</span><div class="erp-folder-permissions"><?php foreach(array_slice($folderLabels,9,null,true) as $folder=>$label):?><label><input type="checkbox" name="folders[<?=p($uid)?>][]" value="<?=p($folder)?>" <?=in_array($folder,$memberFolders,true)?'checked':''?>><span><?=p($label)?></span></label><?php endforeach;?></div></div></div>
</article>
<?php endforeach;?><?php endif;?></div><button class="button primary">Freigaben speichern</button></form></div>
<?php else:?><div class="erp-folder-summary"><strong>Für dich freigegebene Ordner:</strong> <?php foreach(($allowedProjectFolders??[]) as $folder):?><span class="erp-status-pill"><?=p($folderLabels[$folder]??$folder)?></span><?php endforeach;?></div><?php endif;?></section>
<?php if(!empty($isProjectSupervisor)):?><section class="erp-card erp-workflow-card"><div class="erp-section-head"><div><h2>Projektablauf</h2><p class="erp-muted">Der aktuelle Status steuert den Projektfortschritt.</p></div></div><div class="erp-workflow"><?php foreach($statuses as $status):?><form method="post" action="<?=p($url->linkToRoute('reinhardterp.project.updateStatus',['id'=>$project['id']]))?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input type="hidden" name="status" value="<?=p($status)?>"><button class="erp-workflow-step <?=strcasecmp($currentStatus,$status)===0?'is-active':''?>"><span></span><?=p($status)?></button></form><?php endforeach;?></div></section><?php endif;?>
<?php if(!empty($isProjectSupervisor)):?>
<?php
$mountRows=array_values(array_filter($projectSuppliers,static fn($r)=>(int)($r['mounting_relevant']??1)===1));
$mountComplete=!empty($mountRows)&&count(array_filter($mountRows,static fn($r)=>(string)($r['receipt_status']??'')==='complete'))===count($mountRows);
$mountCompleteCount=count(array_filter($mountRows,static fn($r)=>(string)($r['receipt_status']??'')==='complete'));
$receiptLabels=['open'=>'Offen','partial'=>'Teilweise eingelagert','complete'=>'Vollständig eingelagert'];
$abLabels=['open'=>'Offen','received'=>'Erhalten'];
?>
<section class="erp-card" id="suppliers">
 <div class="erp-section-head"><div><h2>Lieferanten & Wareneingang</h2><p class="erp-muted">Bestellungen, Auftragsbestätigungen und Wareneingänge direkt mit diesem Projekt verknüpfen.</p></div>
 <span class="erp-status-pill <?=$mountComplete?'is-success':'is-warning'?>"><?php p($mountComplete?'Montagefreigabe: Material vollständig':(empty($mountRows)?'Montagefreigabe: noch keine Lieferungen':'Montagefreigabe: '.$mountCompleteCount.'/'.count($mountRows).' vollständig')); ?></span></div>
 <?php if($projectSuppliers):?><div class="erp-table-wrap"><table class="erp-table"><thead><tr><th>Gewerk / Lieferant</th><th>Bestell-Nr.</th><th>Auftragsbestätigung</th><th>Avisierte KW</th><th>Wareneingang</th><th>Belege</th><th></th></tr></thead><tbody>
 <?php foreach($projectSuppliers as $sp):?><tr>
  <td><strong><?=p(trim((string)($sp['trade']??''))!==''?$sp['trade']:$sp['supplier_name'])?></strong><?php if(trim((string)($sp['trade']??''))!==''):?><small><?=p($sp['supplier_name'])?></small><?php endif;?><?php if(!(int)($sp['mounting_relevant']??1)):?><small>Nicht montagekritisch</small><?php endif;?></td>
  <td><?=p($sp['purchase_no']?:'–')?></td>
  <td><?=p($abLabels[$sp['confirmation_status']]??$sp['confirmation_status'])?><?php if(!empty($sp['confirmation_no'])):?> <small><?=p($sp['confirmation_no'])?></small><?php endif;?></td>
  <td><?=p(!empty($sp['expected_week'])?'KW '.(int)$sp['expected_week']:'–')?></td>
  <td><span class="erp-status-pill"><?=p($receiptLabels[$sp['receipt_status']]??$sp['receipt_status'])?></span></td>
  <td><?php if(!empty($sp['confirmation_document_id'])):?><a href="<?=p($url->linkToRoute('reinhardterp.document.detail',['id'=>$sp['confirmation_document_id']]))?>">AB</a><?php endif;?><?php if(!empty($sp['delivery_document_id'])):?> <?=!empty($sp['confirmation_document_id'])?' · ':''?><a href="<?=p($url->linkToRoute('reinhardterp.document.detail',['id'=>$sp['delivery_document_id']]))?>">Lieferschein</a><?php endif;?><?php if(empty($sp['confirmation_document_id'])&&empty($sp['delivery_document_id'])):?>–<?php endif;?></td>
  <td><details><summary class="button">Bearbeiten</summary>
   <form method="post" action="<?=p($url->linkToRoute('reinhardterp.project.saveSupplierCockpit',['id'=>$project['id']]))?>" class="erp-form-grid erp-supplier-cockpit-form"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input type="hidden" name="rowId" value="<?=p($sp['id'])?>">
    <label>Lieferant<select name="supplierId" required><?php foreach($suppliers as $su):?><option value="<?=p($su['id'])?>" <?=(int)$su['id']===(int)$sp['supplier_id']?'selected':''?>><?=p($su['name'])?></option><?php endforeach;?></select></label>
    <label>Gewerk<input name="trade" value="<?=p($sp['trade']??'')?>" placeholder="z. B. Arbeitsplatte"></label>
    <label>Bestell-Nr.<input name="purchaseNo" value="<?=p($sp['purchase_no']??'')?>"></label>
    <label>AB-Status<select name="confirmationStatus"><option value="open" <?=$sp['confirmation_status']==='open'?'selected':''?>>Offen</option><option value="received" <?=$sp['confirmation_status']==='received'?'selected':''?>>Erhalten</option></select></label>
    <label>AB-Nr.<input name="confirmationNo" value="<?=p($sp['confirmation_no']??'')?>"></label>
    <label>Avisierte KW<input type="number" min="1" max="53" name="expectedWeek" value="<?=p($sp['expected_week']??'')?>"></label>
    <label>Wareneingang<select name="receiptStatus"><option value="open" <?=$sp['receipt_status']==='open'?'selected':''?>>Offen</option><option value="partial" <?=$sp['receipt_status']==='partial'?'selected':''?>>Teilweise eingelagert</option><option value="complete" <?=$sp['receipt_status']==='complete'?'selected':''?>>Vollständig eingelagert</option></select></label>
    <label>AB aus Dokumenten<select name="confirmationDocumentId"><option value="">–</option><?php foreach($supplierDocuments as $d):?><option value="<?=p($d['id'])?>" <?=(int)($sp['confirmation_document_id']??0)===(int)$d['id']?'selected':''?>><?=p(($d['document_no']?:$d['file_name']).' · '.$d['document_type'])?></option><?php endforeach;?></select></label>
    <label>Lieferschein aus Dokumenten<select name="deliveryDocumentId"><option value="">–</option><?php foreach($supplierDocuments as $d):?><option value="<?=p($d['id'])?>" <?=(int)($sp['delivery_document_id']??0)===(int)$d['id']?'selected':''?>><?=p(($d['document_no']?:$d['file_name']).' · '.$d['document_type'])?></option><?php endforeach;?></select></label>
    <label><input type="checkbox" name="mountingRelevant" value="1" <?=(int)($sp['mounting_relevant']??1)?'checked':''?>> Für Montagefreigabe relevant</label>
    <label>Notiz<input name="notes" value="<?=p($sp['notes']??'')?>"></label><div><button class="button primary">Speichern</button></div>
   </form>
   <form method="post" action="<?=p($url->linkToRoute('reinhardterp.project.deleteSupplierCockpit',['id'=>$project['id'],'rowId'=>$sp['id']]))?>" onsubmit="return confirm('Lieferantenzeile wirklich aus dem Projekt entfernen?');"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><button class="button">Zeile entfernen</button></form>
  </details></td>
 </tr><?php endforeach;?></tbody></table></div><?php else:?><p class="erp-muted">Noch keine projektbezogenen Lieferungen erfasst.</p><?php endif;?>
 <details class="erp-add-supplier"><summary class="button primary">+ Lieferant / Bestellung</summary>
 <form method="post" action="<?=p($url->linkToRoute('reinhardterp.project.saveSupplierCockpit',['id'=>$project['id']]))?>" class="erp-form-grid erp-supplier-cockpit-form"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>">
  <label>Lieferant<select name="supplierId" required><option value="">Lieferant wählen</option><?php foreach($suppliers as $su):?><option value="<?=p($su['id'])?>"><?=p($su['name'])?></option><?php endforeach;?></select></label>
  <label>Gewerk<input name="trade" placeholder="z. B. Möbelhersteller"></label><label>Bestell-Nr.<input name="purchaseNo"></label>
  <label>AB-Status<select name="confirmationStatus"><option value="open">Offen</option><option value="received">Erhalten</option></select></label><label>AB-Nr.<input name="confirmationNo"></label><label>Avisierte KW<input type="number" min="1" max="53" name="expectedWeek"></label>
  <label>Wareneingang<select name="receiptStatus"><option value="open">Offen</option><option value="partial">Teilweise eingelagert</option><option value="complete">Vollständig eingelagert</option></select></label>
  <label>AB aus Dokumenten<select name="confirmationDocumentId"><option value="">–</option><?php foreach($supplierDocuments as $d):?><option value="<?=p($d['id'])?>"><?=p(($d['document_no']?:$d['file_name']).' · '.$d['document_type'])?></option><?php endforeach;?></select></label>
  <label>Lieferschein aus Dokumenten<select name="deliveryDocumentId"><option value="">–</option><?php foreach($supplierDocuments as $d):?><option value="<?=p($d['id'])?>"><?=p(($d['document_no']?:$d['file_name']).' · '.$d['document_type'])?></option><?php endforeach;?></select></label>
  <label><input type="checkbox" name="mountingRelevant" value="1" checked> Für Montagefreigabe relevant</label><label>Notiz<input name="notes"></label><div><button class="button primary">Hinzufügen</button></div>
 </form></details>
</section>
<?php endif;?>
<div class="erp-project-center-grid">
<?php if(!empty($isProjectSupervisor)):?>
<section class="erp-card" id="offers"><div class="erp-section-head"><div><h2>Angebote</h2><p class="erp-muted">Alle dem Projekt zugeordneten Angebote.</p></div><a class="button" href="<?=p($url->linkToRoute('reinhardterp.business.offerForm'))?>">+ Angebot</a></div><?php if(!$offers):?><p class="erp-muted">Noch kein Angebot zugeordnet.</p><?php else:?><div class="erp-material-stream"><?php foreach($offers as $x):?><a class="erp-business-row" href="<?=p($url->linkToRoute('reinhardterp.business.offerDetail',['id'=>$x['id']]))?>"><span><strong><?=p($x['offer_no'])?></strong><small><?=p(date('d.m.Y',strtotime((string)$x['offer_date'])))?> · <?=p($x['status'])?> · <?=p($x['title'])?></small></span><em><?=p($money($x['gross_amount']))?></em></a><?php endforeach;?></div><?php endif;?></section>
<section class="erp-card" id="orders"><div class="erp-section-head"><div><h2>Aufträge</h2><p class="erp-muted">Alle Aufträge dieses Projekts.</p></div></div><?php if(!$orders):?><p class="erp-muted">Noch kein Auftrag zugeordnet.</p><?php else:?><div class="erp-material-stream"><?php foreach($orders as $x):?><a class="erp-business-row" href="<?=p($url->linkToRoute('reinhardterp.business.orderDetail',['id'=>$x['id']]))?>"><span><strong><?=p($x['order_no'])?></strong><small><?=p(date('d.m.Y',strtotime((string)$x['order_date'])))?> · <?=p($x['status'])?> · <?=p($x['title'])?></small></span><em><?=p($money($x['gross_amount']))?></em></a><?php endforeach;?></div><?php endif;?></section>
<section class="erp-card" id="billing"><div class="erp-section-head"><div><h2>Abrechnung aus der Projektakte</h2><p class="erp-muted">Zeiten, Material und unterschriebene Rapporte direkt als Rechnungsgrundlage übernehmen.</p></div></div><form method="get" action="<?=p($url->linkToRoute('reinhardterp.business.invoiceForm'))?>" class="erp-form-grid"><input type="hidden" name="projectId" value="<?=p($project['id'])?>"><label><input type="checkbox" name="includeTimes" value="1" checked> Zeiten übernehmen</label><label><input type="checkbox" name="includeMaterials" value="1" checked> Material übernehmen</label><label><input type="checkbox" name="includeReports" value="1" checked> Rapporte als Nachweis übernehmen</label><div><button class="button primary" type="submit">Rechnung aus Projekt vorbereiten</button></div></form><p class="erp-muted">Alle übernommenen Positionen bleiben vor dem Speichern der Rechnung bearbeitbar. Rapport-Nachweise werden als Alternativ-/Infozeilen mit 0,00 € übernommen.</p></section>
<section class="erp-card" id="invoices"><div class="erp-section-head"><div><h2>Rechnungen</h2><p class="erp-muted">Rechnungen, Abschläge und Schlussrechnungen des Projekts.</p></div></div><?php if(!$invoices):?><p class="erp-muted">Noch keine Rechnung zugeordnet.</p><?php else:?><div class="erp-material-stream"><?php $invoiceTypes=['invoice'=>'Rechnung','advance'=>'Abschlagsrechnung','final'=>'Schlussrechnung','credit'=>'Gutschrift'];foreach($invoices as $x):?><a class="erp-business-row" href="<?=p($url->linkToRoute('reinhardterp.business.invoiceDetail',['id'=>$x['id']]))?>"><span><strong><?=p(($x['status']??'draft')==='draft'?'Entwurf #'.$x['id']:$x['invoice_no'])?></strong><small><?=p($invoiceTypes[$x['invoice_type']??'invoice']??'Rechnung')?> · <?=p(date('d.m.Y',strtotime((string)$x['invoice_date'])))?> · <?=p($x['status'])?></small></span><em><?=p($money($x['gross_amount']))?></em></a><?php endforeach;?></div><?php endif;?></section>
<section class="erp-card" id="payments"><div class="erp-section-head"><div><h2>Zahlungen</h2><p class="erp-muted">Zahlungseingänge aller Projekt-Rechnungen.</p></div></div><?php if(empty($_['projectPayments'])):?><p class="erp-muted">Noch keine Zahlungen verbucht.</p><?php else:?><div class="erp-material-stream"><?php foreach($_['projectPayments'] as $pmt):?><article><span><strong><?=p($pmt['invoice_no'])?></strong><small><?=p(date('d.m.Y',strtotime((string)$pmt['payment_date'])))?><?php if(!empty($pmt['note'])):?> · <?=p($pmt['note'])?><?php endif;?></small></span><em><?=p($money($pmt['amount']))?></em></article><?php endforeach;?></div><?php endif;?></section>
<?php endif;?>
<section class="erp-card" id="appointments"><div class="erp-section-head"><div><h2>Termine</h2><p class="erp-muted">Projektbezogene Termine aus dem Teamkalender.</p></div><a class="button primary" href="<?=p($url->linkToRoute('reinhardterp.module.teamEvents',['customerId'=>(int)($project['customer_id']??0),'projectId'=>(int)$project['id']]))?>">+ Termin anlegen</a></div><?php if(!$projectEvents):?><p class="erp-muted">Noch keine Termine diesem Projekt zugeordnet.</p><?php else:?><div class="erp-event-list"><?php foreach($projectEvents as $event):?><article><time><?=p(date('d.m.Y H:i',strtotime((string)$event['start_at'])))?></time><span><strong><?=p($event['title'])?></strong><small><?=p($event['location']??'')?></small></span></article><?php endforeach;?></div><?php endif;?></section>
</div>
<?php if(!empty($isProjectSupervisor)):?>
<?php
$mountRows=array_values(array_filter($projectSuppliers,static fn($r)=>(int)($r['mounting_relevant']??1)===1));
$mountComplete=!empty($mountRows)&&count(array_filter($mountRows,static fn($r)=>(string)($r['receipt_status']??'')==='complete'))===count($mountRows);
$mountCompleteCount=count(array_filter($mountRows,static fn($r)=>(string)($r['receipt_status']??'')==='complete'));
$receiptLabels=['open'=>'Offen','partial'=>'Teilweise eingelagert','complete'=>'Vollständig eingelagert'];
$abLabels=['open'=>'Offen','received'=>'Erhalten'];
?>
<section class="erp-card" id="suppliers">
 <div class="erp-section-head"><div><h2>Lieferanten & Wareneingang</h2><p class="erp-muted">Bestellungen, Auftragsbestätigungen und Wareneingänge direkt mit diesem Projekt verknüpfen.</p></div>
 <span class="erp-status-pill <?=$mountComplete?'is-success':'is-warning'?>"><?php p($mountComplete?'Montagefreigabe: Material vollständig':(empty($mountRows)?'Montagefreigabe: noch keine Lieferungen':'Montagefreigabe: '.$mountCompleteCount.'/'.count($mountRows).' vollständig')); ?></span></div>
 <?php if($projectSuppliers):?><div class="erp-table-wrap"><table class="erp-table"><thead><tr><th>Gewerk / Lieferant</th><th>Bestell-Nr.</th><th>Auftragsbestätigung</th><th>Avisierte KW</th><th>Wareneingang</th><th>Belege</th><th></th></tr></thead><tbody>
 <?php foreach($projectSuppliers as $sp):?><tr>
  <td><strong><?=p(trim((string)($sp['trade']??''))!==''?$sp['trade']:$sp['supplier_name'])?></strong><?php if(trim((string)($sp['trade']??''))!==''):?><small><?=p($sp['supplier_name'])?></small><?php endif;?><?php if(!(int)($sp['mounting_relevant']??1)):?><small>Nicht montagekritisch</small><?php endif;?></td>
  <td><?=p($sp['purchase_no']?:'–')?></td>
  <td><?=p($abLabels[$sp['confirmation_status']]??$sp['confirmation_status'])?><?php if(!empty($sp['confirmation_no'])):?> <small><?=p($sp['confirmation_no'])?></small><?php endif;?></td>
  <td><?=p(!empty($sp['expected_week'])?'KW '.(int)$sp['expected_week']:'–')?></td>
  <td><span class="erp-status-pill"><?=p($receiptLabels[$sp['receipt_status']]??$sp['receipt_status'])?></span></td>
  <td><?php if(!empty($sp['confirmation_document_id'])):?><a href="<?=p($url->linkToRoute('reinhardterp.document.detail',['id'=>$sp['confirmation_document_id']]))?>">AB</a><?php endif;?><?php if(!empty($sp['delivery_document_id'])):?> <?=!empty($sp['confirmation_document_id'])?' · ':''?><a href="<?=p($url->linkToRoute('reinhardterp.document.detail',['id'=>$sp['delivery_document_id']]))?>">Lieferschein</a><?php endif;?><?php if(empty($sp['confirmation_document_id'])&&empty($sp['delivery_document_id'])):?>–<?php endif;?></td>
  <td><details><summary class="button">Bearbeiten</summary>
   <form method="post" action="<?=p($url->linkToRoute('reinhardterp.project.saveSupplierCockpit',['id'=>$project['id']]))?>" class="erp-form-grid erp-supplier-cockpit-form"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input type="hidden" name="rowId" value="<?=p($sp['id'])?>">
    <label>Lieferant<select name="supplierId" required><?php foreach($suppliers as $su):?><option value="<?=p($su['id'])?>" <?=(int)$su['id']===(int)$sp['supplier_id']?'selected':''?>><?=p($su['name'])?></option><?php endforeach;?></select></label>
    <label>Gewerk<input name="trade" value="<?=p($sp['trade']??'')?>" placeholder="z. B. Arbeitsplatte"></label>
    <label>Bestell-Nr.<input name="purchaseNo" value="<?=p($sp['purchase_no']??'')?>"></label>
    <label>AB-Status<select name="confirmationStatus"><option value="open" <?=$sp['confirmation_status']==='open'?'selected':''?>>Offen</option><option value="received" <?=$sp['confirmation_status']==='received'?'selected':''?>>Erhalten</option></select></label>
    <label>AB-Nr.<input name="confirmationNo" value="<?=p($sp['confirmation_no']??'')?>"></label>
    <label>Avisierte KW<input type="number" min="1" max="53" name="expectedWeek" value="<?=p($sp['expected_week']??'')?>"></label>
    <label>Wareneingang<select name="receiptStatus"><option value="open" <?=$sp['receipt_status']==='open'?'selected':''?>>Offen</option><option value="partial" <?=$sp['receipt_status']==='partial'?'selected':''?>>Teilweise eingelagert</option><option value="complete" <?=$sp['receipt_status']==='complete'?'selected':''?>>Vollständig eingelagert</option></select></label>
    <label>AB aus Dokumenten<select name="confirmationDocumentId"><option value="">–</option><?php foreach($supplierDocuments as $d):?><option value="<?=p($d['id'])?>" <?=(int)($sp['confirmation_document_id']??0)===(int)$d['id']?'selected':''?>><?=p(($d['document_no']?:$d['file_name']).' · '.$d['document_type'])?></option><?php endforeach;?></select></label>
    <label>Lieferschein aus Dokumenten<select name="deliveryDocumentId"><option value="">–</option><?php foreach($supplierDocuments as $d):?><option value="<?=p($d['id'])?>" <?=(int)($sp['delivery_document_id']??0)===(int)$d['id']?'selected':''?>><?=p(($d['document_no']?:$d['file_name']).' · '.$d['document_type'])?></option><?php endforeach;?></select></label>
    <label><input type="checkbox" name="mountingRelevant" value="1" <?=(int)($sp['mounting_relevant']??1)?'checked':''?>> Für Montagefreigabe relevant</label>
    <label>Notiz<input name="notes" value="<?=p($sp['notes']??'')?>"></label><div><button class="button primary">Speichern</button></div>
   </form>
   <form method="post" action="<?=p($url->linkToRoute('reinhardterp.project.deleteSupplierCockpit',['id'=>$project['id'],'rowId'=>$sp['id']]))?>" onsubmit="return confirm('Lieferantenzeile wirklich aus dem Projekt entfernen?');"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><button class="button">Zeile entfernen</button></form>
  </details></td>
 </tr><?php endforeach;?></tbody></table></div><?php else:?><p class="erp-muted">Noch keine projektbezogenen Lieferungen erfasst.</p><?php endif;?>
 <details class="erp-add-supplier"><summary class="button primary">+ Lieferant / Bestellung</summary>
 <form method="post" action="<?=p($url->linkToRoute('reinhardterp.project.saveSupplierCockpit',['id'=>$project['id']]))?>" class="erp-form-grid erp-supplier-cockpit-form"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>">
  <label>Lieferant<select name="supplierId" required><option value="">Lieferant wählen</option><?php foreach($suppliers as $su):?><option value="<?=p($su['id'])?>"><?=p($su['name'])?></option><?php endforeach;?></select></label>
  <label>Gewerk<input name="trade" placeholder="z. B. Möbelhersteller"></label><label>Bestell-Nr.<input name="purchaseNo"></label>
  <label>AB-Status<select name="confirmationStatus"><option value="open">Offen</option><option value="received">Erhalten</option></select></label><label>AB-Nr.<input name="confirmationNo"></label><label>Avisierte KW<input type="number" min="1" max="53" name="expectedWeek"></label>
  <label>Wareneingang<select name="receiptStatus"><option value="open">Offen</option><option value="partial">Teilweise eingelagert</option><option value="complete">Vollständig eingelagert</option></select></label>
  <label>AB aus Dokumenten<select name="confirmationDocumentId"><option value="">–</option><?php foreach($supplierDocuments as $d):?><option value="<?=p($d['id'])?>"><?=p(($d['document_no']?:$d['file_name']).' · '.$d['document_type'])?></option><?php endforeach;?></select></label>
  <label>Lieferschein aus Dokumenten<select name="deliveryDocumentId"><option value="">–</option><?php foreach($supplierDocuments as $d):?><option value="<?=p($d['id'])?>"><?=p(($d['document_no']?:$d['file_name']).' · '.$d['document_type'])?></option><?php endforeach;?></select></label>
  <label><input type="checkbox" name="mountingRelevant" value="1" checked> Für Montagefreigabe relevant</label><label>Notiz<input name="notes"></label><div><button class="button primary">Hinzufügen</button></div>
 </form></details>
</section>
<?php endif;?>
<div class="erp-project-center-grid">
<section class="erp-card" id="reports"><div class="erp-section-head"><div><h2>Rapporte</h2><p class="erp-muted">Leistungsnachweise, Unterschriften und PDF.</p></div><a class="button primary" href="<?=p($url->linkToRoute('reinhardterp.module.reports',['projectId'=>$project['id']]))?>">+ Neuer Rapport</a></div><?php if(!$reports):?><p class="erp-muted">Noch keine Rapporte.</p><?php else:?><div class="erp-compact-list"><?php foreach(array_slice($reports,0,8) as $x):?><a href="<?=p($url->linkToRoute('reinhardterp.module.reportDetail',['id'=>$x['id']]))?>"><span><strong><?=p($x['report_no'])?></strong><small><?=p($x['title'])?> · <?=p($x['report_date'])?></small></span><em><?=p($x['status'])?></em></a><?php endforeach;?></div><?php endif;?></section>
<section class="erp-card" id="time"><div class="erp-section-head"><div><h2>Zeiterfassung</h2><p class="erp-muted">Letzte Arbeiten im Projekt.</p></div><a class="button" href="<?=p($url->linkToRoute('reinhardterp.module.workdays'))?>">Zeit buchen</a></div><?php if(!$times):?><p class="erp-muted">Noch keine Zeiten.</p><?php else:?><div class="erp-compact-table"><div class="erp-compact-table-head"><span>Datum</span><span>Mitarbeiter</span><span>Std.</span><span>Tätigkeit</span></div><?php foreach(array_slice($times,0,10) as $x):?><div><span><?=p($x['work_date'])?></span><span><?=p($x['user_id'])?></span><strong><?=p(number_format((float)$x['hours'],2,',','.'))?></strong><span><?=p($x['activity'])?></span></div><?php endforeach;?></div><?php endif;?></section>
</div>
<?php if(!empty($isProjectSupervisor)):?>
<?php
$mountRows=array_values(array_filter($projectSuppliers,static fn($r)=>(int)($r['mounting_relevant']??1)===1));
$mountComplete=!empty($mountRows)&&count(array_filter($mountRows,static fn($r)=>(string)($r['receipt_status']??'')==='complete'))===count($mountRows);
$mountCompleteCount=count(array_filter($mountRows,static fn($r)=>(string)($r['receipt_status']??'')==='complete'));
$receiptLabels=['open'=>'Offen','partial'=>'Teilweise eingelagert','complete'=>'Vollständig eingelagert'];
$abLabels=['open'=>'Offen','received'=>'Erhalten'];
?>
<section class="erp-card" id="suppliers">
 <div class="erp-section-head"><div><h2>Lieferanten & Wareneingang</h2><p class="erp-muted">Bestellungen, Auftragsbestätigungen und Wareneingänge direkt mit diesem Projekt verknüpfen.</p></div>
 <span class="erp-status-pill <?=$mountComplete?'is-success':'is-warning'?>"><?php p($mountComplete?'Montagefreigabe: Material vollständig':(empty($mountRows)?'Montagefreigabe: noch keine Lieferungen':'Montagefreigabe: '.$mountCompleteCount.'/'.count($mountRows).' vollständig')); ?></span></div>
 <?php if($projectSuppliers):?><div class="erp-table-wrap"><table class="erp-table"><thead><tr><th>Gewerk / Lieferant</th><th>Bestell-Nr.</th><th>Auftragsbestätigung</th><th>Avisierte KW</th><th>Wareneingang</th><th>Belege</th><th></th></tr></thead><tbody>
 <?php foreach($projectSuppliers as $sp):?><tr>
  <td><strong><?=p(trim((string)($sp['trade']??''))!==''?$sp['trade']:$sp['supplier_name'])?></strong><?php if(trim((string)($sp['trade']??''))!==''):?><small><?=p($sp['supplier_name'])?></small><?php endif;?><?php if(!(int)($sp['mounting_relevant']??1)):?><small>Nicht montagekritisch</small><?php endif;?></td>
  <td><?=p($sp['purchase_no']?:'–')?></td>
  <td><?=p($abLabels[$sp['confirmation_status']]??$sp['confirmation_status'])?><?php if(!empty($sp['confirmation_no'])):?> <small><?=p($sp['confirmation_no'])?></small><?php endif;?></td>
  <td><?=p(!empty($sp['expected_week'])?'KW '.(int)$sp['expected_week']:'–')?></td>
  <td><span class="erp-status-pill"><?=p($receiptLabels[$sp['receipt_status']]??$sp['receipt_status'])?></span></td>
  <td><?php if(!empty($sp['confirmation_document_id'])):?><a href="<?=p($url->linkToRoute('reinhardterp.document.detail',['id'=>$sp['confirmation_document_id']]))?>">AB</a><?php endif;?><?php if(!empty($sp['delivery_document_id'])):?> <?=!empty($sp['confirmation_document_id'])?' · ':''?><a href="<?=p($url->linkToRoute('reinhardterp.document.detail',['id'=>$sp['delivery_document_id']]))?>">Lieferschein</a><?php endif;?><?php if(empty($sp['confirmation_document_id'])&&empty($sp['delivery_document_id'])):?>–<?php endif;?></td>
  <td><details><summary class="button">Bearbeiten</summary>
   <form method="post" action="<?=p($url->linkToRoute('reinhardterp.project.saveSupplierCockpit',['id'=>$project['id']]))?>" class="erp-form-grid erp-supplier-cockpit-form"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input type="hidden" name="rowId" value="<?=p($sp['id'])?>">
    <label>Lieferant<select name="supplierId" required><?php foreach($suppliers as $su):?><option value="<?=p($su['id'])?>" <?=(int)$su['id']===(int)$sp['supplier_id']?'selected':''?>><?=p($su['name'])?></option><?php endforeach;?></select></label>
    <label>Gewerk<input name="trade" value="<?=p($sp['trade']??'')?>" placeholder="z. B. Arbeitsplatte"></label>
    <label>Bestell-Nr.<input name="purchaseNo" value="<?=p($sp['purchase_no']??'')?>"></label>
    <label>AB-Status<select name="confirmationStatus"><option value="open" <?=$sp['confirmation_status']==='open'?'selected':''?>>Offen</option><option value="received" <?=$sp['confirmation_status']==='received'?'selected':''?>>Erhalten</option></select></label>
    <label>AB-Nr.<input name="confirmationNo" value="<?=p($sp['confirmation_no']??'')?>"></label>
    <label>Avisierte KW<input type="number" min="1" max="53" name="expectedWeek" value="<?=p($sp['expected_week']??'')?>"></label>
    <label>Wareneingang<select name="receiptStatus"><option value="open" <?=$sp['receipt_status']==='open'?'selected':''?>>Offen</option><option value="partial" <?=$sp['receipt_status']==='partial'?'selected':''?>>Teilweise eingelagert</option><option value="complete" <?=$sp['receipt_status']==='complete'?'selected':''?>>Vollständig eingelagert</option></select></label>
    <label>AB aus Dokumenten<select name="confirmationDocumentId"><option value="">–</option><?php foreach($supplierDocuments as $d):?><option value="<?=p($d['id'])?>" <?=(int)($sp['confirmation_document_id']??0)===(int)$d['id']?'selected':''?>><?=p(($d['document_no']?:$d['file_name']).' · '.$d['document_type'])?></option><?php endforeach;?></select></label>
    <label>Lieferschein aus Dokumenten<select name="deliveryDocumentId"><option value="">–</option><?php foreach($supplierDocuments as $d):?><option value="<?=p($d['id'])?>" <?=(int)($sp['delivery_document_id']??0)===(int)$d['id']?'selected':''?>><?=p(($d['document_no']?:$d['file_name']).' · '.$d['document_type'])?></option><?php endforeach;?></select></label>
    <label><input type="checkbox" name="mountingRelevant" value="1" <?=(int)($sp['mounting_relevant']??1)?'checked':''?>> Für Montagefreigabe relevant</label>
    <label>Notiz<input name="notes" value="<?=p($sp['notes']??'')?>"></label><div><button class="button primary">Speichern</button></div>
   </form>
   <form method="post" action="<?=p($url->linkToRoute('reinhardterp.project.deleteSupplierCockpit',['id'=>$project['id'],'rowId'=>$sp['id']]))?>" onsubmit="return confirm('Lieferantenzeile wirklich aus dem Projekt entfernen?');"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><button class="button">Zeile entfernen</button></form>
  </details></td>
 </tr><?php endforeach;?></tbody></table></div><?php else:?><p class="erp-muted">Noch keine projektbezogenen Lieferungen erfasst.</p><?php endif;?>
 <details class="erp-add-supplier"><summary class="button primary">+ Lieferant / Bestellung</summary>
 <form method="post" action="<?=p($url->linkToRoute('reinhardterp.project.saveSupplierCockpit',['id'=>$project['id']]))?>" class="erp-form-grid erp-supplier-cockpit-form"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>">
  <label>Lieferant<select name="supplierId" required><option value="">Lieferant wählen</option><?php foreach($suppliers as $su):?><option value="<?=p($su['id'])?>"><?=p($su['name'])?></option><?php endforeach;?></select></label>
  <label>Gewerk<input name="trade" placeholder="z. B. Möbelhersteller"></label><label>Bestell-Nr.<input name="purchaseNo"></label>
  <label>AB-Status<select name="confirmationStatus"><option value="open">Offen</option><option value="received">Erhalten</option></select></label><label>AB-Nr.<input name="confirmationNo"></label><label>Avisierte KW<input type="number" min="1" max="53" name="expectedWeek"></label>
  <label>Wareneingang<select name="receiptStatus"><option value="open">Offen</option><option value="partial">Teilweise eingelagert</option><option value="complete">Vollständig eingelagert</option></select></label>
  <label>AB aus Dokumenten<select name="confirmationDocumentId"><option value="">–</option><?php foreach($supplierDocuments as $d):?><option value="<?=p($d['id'])?>"><?=p(($d['document_no']?:$d['file_name']).' · '.$d['document_type'])?></option><?php endforeach;?></select></label>
  <label>Lieferschein aus Dokumenten<select name="deliveryDocumentId"><option value="">–</option><?php foreach($supplierDocuments as $d):?><option value="<?=p($d['id'])?>"><?=p(($d['document_no']?:$d['file_name']).' · '.$d['document_type'])?></option><?php endforeach;?></select></label>
  <label><input type="checkbox" name="mountingRelevant" value="1" checked> Für Montagefreigabe relevant</label><label>Notiz<input name="notes"></label><div><button class="button primary">Hinzufügen</button></div>
 </form></details>
</section>
<?php endif;?>
<div class="erp-project-center-grid">
<section class="erp-card" id="material"><div class="erp-section-head"><div><h2>Material</h2><p class="erp-muted">In Rapporten erfasster Projektverbrauch.</p></div><a class="button" href="<?=p($url->linkToRoute('reinhardterp.module.materials'))?>">Materialstamm</a></div><?php if(!$projectMaterials):?><p class="erp-muted">Noch kein Material erfasst.</p><?php else:?><div class="erp-material-stream"><?php foreach($projectMaterials as $item):?><article><span><strong><?=p($item['description'])?></strong><small><?=p($item['report_no'])?> · <?=p(date('d.m.Y',strtotime((string)$item['report_date'])))?></small></span><em><?=p(number_format((float)$item['quantity'],3,',','.'))?> <?=p($item['unit']??'')?></em></article><?php endforeach;?></div><?php endif;?></section>
<section class="erp-card" id="notes">
 <div class="erp-section-head">
  <div><h2>Projektnotizen</h2><p class="erp-muted">Notizen aus Betrio Mobile und der Projektakte.</p></div>
 </div>
 <form method="post" action="<?=p($url->linkToRoute('reinhardterp.page.saveProjectNote',['id'=>$project['id']]))?>" class="erp-form-grid">
  <input type="hidden" name="requesttoken" value="<?=p(\OCP\Util::callRegister())?>">
  <label>Notizart
   <select name="noteType">
    <option value="note">Notiz</option>
    <option value="measurement">Aufmaß</option>
    <option value="meeting">Besprechung</option>
    <option value="phone">Telefonnotiz</option>
   </select>
  </label>
  <label>Titel<input type="text" name="title" maxlength="255" placeholder="Optionaler Titel"></label>
  <label class="erp-form-wide">Notiz<textarea name="content" rows="4" required placeholder="Notiz eingeben"></textarea></label>
  <div class="erp-form-wide"><button class="button primary" type="submit">Notiz speichern</button></div>
 </form>
 <?php if(empty($projectNotes)):?>
  <p class="erp-muted">Noch keine Projektnotizen vorhanden.</p>
 <?php else:?>
  <div class="erp-stack">
   <?php foreach($projectNotes as $note):
    $raw=(string)($note['content']??'');$parts=preg_split("/\R\R/",$raw,2);$noteTitle=count($parts)>1?trim((string)$parts[0]):'';$noteBody=count($parts)>1?(string)$parts[1]:$raw;
    $type=(string)($note['note_type']??'note');$typeLabel=['measurement'=>'Aufmaß','meeting'=>'Besprechung','phone'=>'Telefonnotiz','note'=>'Notiz'][$type]??'Notiz';
   ?>
   <article class="erp-card erp-note-card">
    <div class="erp-section-head"><div><strong><?=p($noteTitle!==''?$noteTitle:$typeLabel)?></strong><small class="erp-muted"><?=p($typeLabel)?> · <?=p((string)($note['created_at']??''))?><?php if(!empty($note['created_by'])):?> · <?=p((string)$note['created_by'])?><?php endif;?></small></div></div>
    <form method="post" action="<?=p($url->linkToRoute('reinhardterp.page.saveProjectNote',['id'=>$project['id']]))?>" class="erp-form-grid">
     <input type="hidden" name="requesttoken" value="<?=p(\OCP\Util::callRegister())?>">
     <input type="hidden" name="noteId" value="<?=p((int)$note['id'])?>">
     <label>Notizart<select name="noteType">
      <?php foreach(['note'=>'Notiz','measurement'=>'Aufmaß','meeting'=>'Besprechung','phone'=>'Telefonnotiz'] as $value=>$label):?>
       <option value="<?=p($value)?>"<?=$type===$value?' selected':''?>><?=p($label)?></option>
      <?php endforeach;?>
     </select></label>
     <label>Titel<input type="text" name="title" maxlength="255" value="<?=p($noteTitle)?>"></label>
     <label class="erp-form-wide">Notiz<textarea name="content" rows="4" required><?=p($noteBody)?></textarea></label>
     <div class="erp-form-wide"><button class="button" type="submit">Änderungen speichern</button></div>
    </form>
    <form method="post" action="<?=p($url->linkToRoute('reinhardterp.page.deleteProjectNote',['id'=>$project['id'],'noteId'=>(int)$note['id']]))?>" onsubmit="return confirm('Diese Projektnotiz wirklich löschen?');">
     <input type="hidden" name="requesttoken" value="<?=p(\OCP\Util::callRegister())?>">
     <button class="button" type="submit">Löschen</button>
    </form>
   </article>
   <?php endforeach;?>
  </div>
 <?php endif;?>
</section>
<section class="erp-card erp-checklist-card" id="checklist">
 <div class="erp-section-head">
  <div><h2>Checkliste</h2><p class="erp-muted">Gemeinsame Projektcheckliste für Betrio und Betrio Mobile.</p></div>
  <?php if($projectChecklist):?><span class="erp-checklist-progress"><?=count(array_filter($projectChecklist,static fn($x)=>(int)($x['done']??0)===1))?> / <?=count($projectChecklist)?> erledigt</span><?php endif;?>
 </div>
 <form method="post" action="<?=p($url->linkToRoute('reinhardterp.page.saveProjectChecklistItem',['id'=>$project['id']]))?>" class="erp-checklist-add">
  <input type="hidden" name="requesttoken" value="<?=p(\OCP\Util::callRegister())?>">
  <input type="text" name="text" maxlength="1000" required placeholder="Neuen Checklistenpunkt hinzufügen">
  <button class="button primary" type="submit">Hinzufügen</button>
 </form>
 <?php if(empty($projectChecklist)):?>
  <p class="erp-muted erp-checklist-empty">Noch keine Checklistenpunkte vorhanden.</p>
 <?php else:?>
  <div class="erp-checklist-list">
   <?php foreach($projectChecklist as $item):$done=(int)($item['done']??0)===1;?>
    <div class="erp-checklist-item<?=$done?' is-done':''?>">
     <form method="post" action="<?=p($url->linkToRoute('reinhardterp.page.toggleProjectChecklistItem',['id'=>$project['id'],'itemId'=>(int)$item['id']]))?>">
      <input type="hidden" name="requesttoken" value="<?=p(\OCP\Util::callRegister())?>">
      <button type="submit" class="erp-checklist-toggle" title="<?=$done?'Wieder öffnen':'Erledigen'?>" aria-label="<?=$done?'Wieder öffnen':'Erledigen'?>"><?=$done?'✓':''?></button>
     </form>
     <div class="erp-checklist-text"><span><?=p((string)$item['text'])?></span><?php if(!empty($item['created_by'])):?><small><?=p((string)$item['created_by'])?></small><?php endif;?></div>
     <form method="post" action="<?=p($url->linkToRoute('reinhardterp.page.deleteProjectChecklistItem',['id'=>$project['id'],'itemId'=>(int)$item['id']]))?>" onsubmit="return confirm('Diesen Checklistenpunkt wirklich löschen?');">
      <input type="hidden" name="requesttoken" value="<?=p(\OCP\Util::callRegister())?>">
      <button class="erp-checklist-delete" type="submit" title="Löschen" aria-label="Löschen">×</button>
     </form>
    </div>
   <?php endforeach;?>
  </div>
 <?php endif;?>
</section>
<section class="erp-card" id="photos"><div class="erp-section-head"><div><h2>Fotos</h2><p class="erp-muted">Bilder aus der digitalen Projektakte.</p></div><?php if($projectPath!==''&&!empty($isProjectSupervisor)):?><a class="button" target="_blank" rel="noopener" href="<?=p($folderUrl($projectPath.'/07_Fotos'))?>">Fotoordner</a><?php endif;?></div><?php if(empty($cockpit['photos'])):?><p class="erp-muted">Noch keine Bilder.</p><?php else:?><div class="erp-photo-strip"><?php foreach($cockpit['photos'] as $photo):$photoUrl=$openProjectFile($photo['path']);?><a target="_blank" rel="noopener" href="<?=p($photoUrl)?>"><span class="erp-photo-thumb"><span class="erp-photo-fallback">🖼️</span><img src="<?=p($photoUrl)?>" alt="" loading="lazy" decoding="async"></span><strong><?=p($photo['name'])?></strong><small><?=p(date('d.m.Y',(int)$photo['mtime']))?></small></a><?php endforeach;?></div><?php endif;?></section>
</div>
<section class="erp-card erp-document-center" id="documents"><div class="erp-section-head"><div><h2>Dokumenteingang</h2><p class="erp-muted">PDF, Angebot, Auftrag, Rechnung, Zeichnung oder Foto direkt dem Projekt zuordnen.</p></div><?php if($projectPath!==''&&!empty($isProjectSupervisor)):?><a class="button" target="_blank" rel="noopener" href="<?=p($folderUrl($projectPath))?>">Alle Dateien</a><?php endif;?></div><?php if($projectPath!==''):?><form class="erp-document-intake" method="post" enctype="multipart/form-data" action="<?=p($url->linkToRoute('reinhardterp.page.uploadProjectDocument',['id'=>$project['id']]))?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.dwg,.dxf" required><select name="documentType"><?php $uploadTypes=['inbox'=>['00_Eingang','Ungeprüfter Eingang'],'offer'=>['10_Angebote','Angebot'],'order'=>['11_Auftraege','Auftrag'],'invoice'=>['09_Rechnung','Rechnung'],'report'=>['06_Rapporte','Rapport'],'drawing'=>['03_Zeichnungen','Zeichnung'],'photo'=>['07_Fotos','Foto'],'material'=>['04_Material','Material'],'other'=>['12_Sonstiges','Sonstiges']];foreach($uploadTypes as $key=>$meta):if(!empty($isProjectSupervisor)||in_array($meta[0],$allowedProjectFolders??[],true)):?><option value="<?=p($key)?>"><?=p($meta[1])?></option><?php endif;endforeach;?></select><button class="button primary">Dem Projekt zuordnen</button></form><?php endif;?><?php if(!empty($documentTags)):?><div class="erp-tag-filter"><a class="erp-tag-chip<?=($selectedDocumentTag===''?' is-active':'')?>" href="<?=p($url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$project['id']]).'#documents')?>">Alle</a><?php foreach($documentTags as $tag):?><a class="erp-tag-chip<?=($selectedDocumentTag===$tag?' is-active':'')?>" href="<?=p($url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$project['id'],'documentTag'=>$tag]).'#documents')?>">#<?=p($tag)?></a><?php endforeach;?></div><?php endif;?><?php if(!$documents):?><p class="erp-muted"><?php if($selectedDocumentTag!==''):?>Keine Projektdatei mit Tag #<?=p($selectedDocumentTag)?><?php else:?>Noch keine Projektdateien.<?php endif;?></p><?php else:?><div class="erp-document-list"><?php foreach(array_slice($documents,0,16) as $doc):?><a class="erp-document-row" target="_blank" rel="noopener" href="<?=p($openProjectFile($doc['path']))?>"><?php $isImage=str_starts_with((string)$doc['mime'],'image/');$docUrl=$openProjectFile($doc['path']);?><span class="erp-file-icon<?=$isImage?' erp-file-image-thumb':''?>"><?php if($isImage):?><span>🖼️</span><img src="<?=p($docUrl)?>" alt="" loading="lazy" decoding="async"><?php else:?><?=((string)$doc['mime']==='application/pdf'?'📄':'📎')?><?php endif;?></span><span class="erp-file-main"><strong><?=p($doc['name'])?></strong><small><?=p(str_replace($projectPath.'/','',$doc['path']))?></small><?php if(!empty($doc['collaborativeTags'])):?><span class="erp-file-tags"><?php foreach($doc['collaborativeTags'] as $tag):?><em>#<?=p($tag)?></em><?php endforeach;?></span><?php endif;?></span><span class="erp-file-meta"><?=p($formatSize((int)$doc['size']))?><br><?=p(date('d.m.Y H:i',(int)$doc['mtime']))?></span><span class="button">Öffnen</span></a><?php endforeach;?></div><?php endif;?></section>
<?php if(!empty($isProjectSupervisor)):?><section class="erp-card erp-cost-center" id="costs"><div class="erp-section-head"><div><h2>Projektkosten & Leistung</h2><p class="erp-muted">Aktuelle Werte aus Auftrag/Angebot, Zeit und Rapportmaterial.</p></div></div><div class="erp-cost-grid"><article><span>Projektwert</span><strong><?=p($money($projectCosts['projectValue']??0))?></strong></article><article><span>Arbeitswert</span><strong><?=p($money($projectCosts['laborValue']??0))?></strong><small><?=p(number_format((float)($projectCosts['hours']??0),2,',','.'))?> h</small></article><article><span>Materialwert</span><strong><?=p($money($projectCosts['materialValue']??0))?></strong></article><article class="<?=($projectCosts['remaining']??0)<0?'is-negative':'is-positive'?>"><span>Rest / Deckung</span><strong><?=p($money($projectCosts['remaining']??0))?></strong><small>vor Fremdleistungen und Gemeinkosten</small></article></div></section><?php endif;?>
<section class="erp-card" id="timeline"><div class="erp-section-head"><div><h2>Projekt-Timeline</h2><p class="erp-muted">Die vollständige Geschichte des Projekts.</p></div></div><form class="erp-journal-form" method="post" action="<?=p($url->linkToRoute('reinhardterp.activity.addProjectNote',['projectId'=>$project['id']]))?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input type="hidden" name="customerId" value="<?=p($project['customer_id'])?>"><textarea name="note" rows="2" placeholder="Interne Notiz …" required></textarea><button class="button primary">Hinzufügen</button></form><?php print_unescaped($this->inc('_activity_timeline',['activities'=>array_slice($activities,0,30)]));?></section>
</main></div></div>
