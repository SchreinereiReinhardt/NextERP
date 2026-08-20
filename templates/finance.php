<?php
require __DIR__.'/_nav.php';
$url=\OC::$server->get(\OCP\IURLGenerator::class);
\OCP\Util::addScript('reinhardterp','finance');
$filesBase=$url->linkToRoute('files.view.index');
$types=[
 'incoming_invoice'=>'Eingangsrechnungen',
 'outgoing_invoice'=>'Ausgangsrechnungen',
 'bank_statement'=>'Kontoauszüge',
 'cash'=>'Kasse',
 'credit_note'=>'Gutschriften',
 'tax'=>'Steuern',
 'accounting_other'=>'Sonstige Belege',
];
$folders=[
 'incoming_invoice'=>'/ERP/30_Finanzen/Eingangsrechnungen',
 'outgoing_invoice'=>'/ERP/30_Finanzen/Ausgangsrechnungen',
 'bank_statement'=>'/ERP/30_Finanzen/Kontoauszuege',
 'cash'=>'/ERP/30_Finanzen/Kasse',
 'credit_note'=>'/ERP/30_Finanzen/Gutschriften',
 'tax'=>'/ERP/30_Finanzen/Steuern',
 'accounting_other'=>'/ERP/30_Finanzen/Sonstige_Belege',
];
?>
<div id="app-content"><div class="erp-page erp-finance-page">
<div class="erp-head"><div><h1>Finanzen</h1><p class="erp-sub">Belege zentral ablegen, automatisch erkennen und der Buchhaltung zuordnen.</p></div><div class="erp-actions"><a class="button" href="<?php p($url->linkToRoute('reinhardterp.document.index')); ?>">Dokumenteneingang</a></div></div>
<?php if(!empty($_['message'])):?><div class="erp-notice"><?php p($_['message']);?></div><?php endif;?>
<?php if(!empty($_['error'])):?><div class="erp-notice erp-notice-warning"><?php p($_['error']);?></div><?php endif;?>

<section class="erp-card erp-wide erp-finance-drop-card">
 <div class="erp-section-head"><div><h2>Belege einfach reinziehen</h2><p class="erp-muted">PDF oder Bild hier ablegen. Betrio übernimmt den Beleg in den Dokumenteneingang, analysiert ihn und öffnet direkt die Zuordnung.</p></div></div>
 <form id="financeDropForm" class="erp-finance-dropzone" method="post" enctype="multipart/form-data" action="<?php p($url->linkToRoute('reinhardterp.document.upload')); ?>">
  <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
  <input id="financeFileInput" type="file" name="document" accept="application/pdf,image/jpeg,image/png,image/webp" required>
  <div class="erp-finance-drop-content">
   <span class="erp-ui-icon erp-icon-document" aria-hidden="true"></span>
   <strong>Datei hier ablegen</strong>
   <span>oder anklicken und auswählen</span>
  </div>
 </form>
</section>

<section class="erp-finance-folder-grid">
<?php foreach($types as $key=>$label):?>
 <a class="erp-card erp-finance-folder-card <?php if(($_['type']??'all')===$key)p('is-active');?>" href="<?php p($url->linkToRoute('reinhardterp.document.finance',['type'=>$key]));?>">
  <span><?php p($label);?></span><strong><?php p((int)($_['counts'][$key]??0));?></strong>
  <small><?php p($folders[$key]);?></small>
 </a>
<?php endforeach;?>
</section>

<section class="erp-card erp-wide">
<h2>Filtern & Steuerbüro-Export</h2>
<form method="get" class="erp-finance-filter-grid">
<label>Belegart<select name="type"><option value="all">Alle</option><?php foreach($types as $key=>$label):?><option value="<?php p($key);?>" <?php if(($_['type']??'all')===$key)p('selected');?>><?php p($label);?></option><?php endforeach;?></select></label>
<label>Jahr<select name="year"><option value="">Alle</option><?php for($y=(int)date('Y');$y>=2020;$y--):?><option value="<?php p((string)$y);?>" <?php if(($_['year']??'')===(string)$y)p('selected');?>><?php p((string)$y);?></option><?php endfor;?></select></label>
<label>Monat<select name="month"><option value="">Alle</option><?php foreach([1=>'Januar',2=>'Februar',3=>'März',4=>'April',5=>'Mai',6=>'Juni',7=>'Juli',8=>'August',9=>'September',10=>'Oktober',11=>'November',12=>'Dezember'] as $m=>$mn):?><option value="<?php p((string)$m);?>" <?php if((int)($_['month']??0)===$m)p('selected');?>><?php p($mn);?></option><?php endforeach;?></select></label>
<label>Lieferant<select name="supplierId"><option value="">Alle</option><?php foreach($_['suppliers'] as $x):?><option value="<?php p((string)$x['id']);?>" <?php if((int)($_['supplierId']??0)===(int)$x['id'])p('selected');?>><?php p($x['name']);?></option><?php endforeach;?></select></label>
<label>Kunde<select name="customerId"><option value="">Alle</option><?php foreach($_['customers'] as $x):?><option value="<?php p((string)$x['id']);?>" <?php if((int)($_['customerId']??0)===(int)$x['id'])p('selected');?>><?php p($x['name']);?></option><?php endforeach;?></select></label>
<label>Projekt<select name="projectId"><option value="">Alle</option><?php foreach($_['projects'] as $x):?><option value="<?php p((string)$x['id']);?>" <?php if((int)($_['projectId']??0)===(int)$x['id'])p('selected');?>><?php p(trim(($x['project_no']??'').' '.($x['title']??'')));?></option><?php endforeach;?></select></label>
<label class="erp-finance-search">Suche<input type="search" name="q" value="<?php p($_['q']??'');?>" placeholder="Nummer, Lieferant, Kunde, Projekt …"></label>
<div class="erp-actions"><button class="button primary">Filter anwenden</button><a class="button" href="<?php p($url->linkToRoute('reinhardterp.document.finance'));?>">Zurücksetzen</a>
<a class="button" href="<?php p($url->linkToRoute('reinhardterp.document.financeExport',['type'=>$_['type']??'all','q'=>$_['q']??'','year'=>$_['year']??'','month'=>$_['month']??'','supplierId'=>$_['supplierId']??0,'customerId'=>$_['customerId']??0,'projectId'=>$_['projectId']??0]));?>">ZIP fürs Steuerbüro</a></div>
</form>
</section>

<section class="erp-card erp-wide">
 <div class="erp-section-head"><div><h2><?php p(($_['type']??'all')==='all'?'Alle Buchhaltungsbelege':($types[$_['type']]??'Buchhaltungsbelege'));?></h2><p class="erp-muted">Erkannte und bereits zugeordnete Belege.</p></div>
 <form method="get" class="erp-inline-filter"><input type="hidden" name="type" value="<?php p($_['type']??'all');?>"><input type="search" name="q" value="<?php p($_['q']??'');?>" placeholder="Beleg, Nummer, Kunde, Projekt …"><button class="button">Suchen</button><?php if(!empty($_['q'])):?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.document.finance',['type'=>$_['type']??'all']));?>">Zurücksetzen</a><?php endif;?></form>
 </div>
 <?php if(empty($_['documents'])):?><p class="erp-muted">Keine passenden Buchhaltungsbelege vorhanden.</p><?php else:?><div class="erp-dms-list">
 <?php foreach($_['documents'] as $document):
  $actual=(string)($document['document_type']??'unassigned');$suggested=(string)($document['suggested_type']??'unassigned');$effective=$actual!=='unassigned'?$actual:$suggested;?>
  <a class="erp-dms-row" href="<?php p($url->linkToRoute('reinhardterp.document.review',['id'=>$document['id']]));?>">
   <span class="erp-dms-row-icon"><span class="erp-ui-icon erp-icon-document" aria-hidden="true"></span></span>
   <span class="erp-dms-row-main"><strong><?php p($document['original_name']);?></strong><small><?php p($document['document_date']??$document['created_at']??'');?></small></span>
   <span class="erp-dms-row-type"><?php p($types[$effective]??$effective);?><?php if($actual==='unassigned'):?><small>Vorschlag · <?php p((int)($document['suggestion_confidence']??0));?> %</small><?php endif;?></span>
   <span class="erp-dms-row-main"><strong><?php p($document['document_no']??$document['suggested_document_no']??'–');?></strong><small><?php p($document['supplier_name']??$document['customer_name']??'');?></small></span>
   <span>›</span>
  </a>
 <?php endforeach;?></div><?php endif;?>
</section>

<section class="erp-card erp-wide"><div class="erp-section-head"><div><h2>Buchhaltungsordner in Nextcloud</h2><p class="erp-muted">Die Belege liegen physisch im Nextcloud-Dateispeicher und werden nach Jahr und Monat abgelegt.</p></div></div><div class="erp-actions">
<?php foreach($folders as $key=>$path):?><a class="button" target="_blank" rel="noopener" href="<?php p($filesBase.'?dir='.rawurlencode($path));?>"><?php p($types[$key]);?></a><?php endforeach;?>
</div></section>
</div></div>
