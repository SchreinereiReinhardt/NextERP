<?php
require __DIR__.'/_nav.php';
$url=\OC::$server->getURLGenerator();
$filesBase=$url->linkToRoute('files.view.index');
$types=['unassigned'=>'Nicht erkannt','incoming_invoice'=>'Eingangsrechnung','outgoing_invoice'=>'Ausgangsrechnung','delivery_note'=>'Lieferschein','credit_note'=>'Gutschrift','bank_statement'=>'Kontoauszug','cash'=>'Kassenbeleg','tax'=>'Steuerunterlage','offer'=>'Angebot','order'=>'Auftragsbestätigung','report'=>'Rapport','drawing'=>'Zeichnung / Plan','other'=>'Sonstiges'];
$processingLabels=['all'=>'Alle','new'=>'Neu','review'=>'In Prüfung','assigned'=>'Zugeordnet','error'=>'Fehler'];
?>
<div id="app-content"><div class="erp-page erp-dms-page">
<div class="erp-head"><div><h1>Belege</h1><p class="erp-sub">Angebote, Auftragsbestätigungen, Rechnungen, Lieferscheine und Gutschriften importieren, prüfen und Kunde, Projekt sowie Auftrag zuordnen.</p></div><div class="erp-actions">
<form method="post" action="<?php p($url->linkToRoute('reinhardterp.document.scan')); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button">Jetzt einlesen</button></form>
<a class="button" target="_blank" href="<?php p($filesBase.'?dir='.rawurlencode('/ERP/00_Dokumenteneingang/01_Unbearbeitet')); ?>">Eingangsordner</a></div></div>
<?php if(!empty($_['message'])):?><div class="erp-notice"><?php p($_['message']);?></div><?php endif;?>
<?php if(!empty($_['error'])):?><div class="erp-notice erp-notice-warning"><strong>Fehler:</strong> <?php p($_['error']);?></div><?php endif;?>
<div class="erp-dms-scan-status"><strong>Automatik aktiv</strong><span>Überwachung alle 5 Minuten</span><span>Besitzer: <?php p($_['scanInfo']['owner'] ?: 'wird beim ersten Öffnen gesetzt'); ?></span><span>Letzter Lauf: <?php p($_['scanInfo']['last_at'] ?: 'noch nicht ausgeführt'); ?></span></div>
<nav class="erp-document-categories" aria-label="Dokumentbereiche">
<?php
$categoryLinks=[
 ['📥','Eingang',['processing'=>'new']],
 ['📤','Ausgangsrechnungen',['type'=>'outgoing_invoice']],
 ['📥','Eingangsrechnungen',['type'=>'incoming_invoice']],
 ['🚚','Lieferscheine',['type'=>'delivery_note']],
 ['📋','Angebote',['type'=>'offer']],
 ['✓','Auftragsbestätigungen',['type'=>'order']],
 ['€','Rechnungen',['type'=>'incoming_invoice']],
 ['↩','Gutschriften',['type'=>'credit_note']],
 ['🏦','Kontoauszüge',['type'=>'bank_statement']],
 ['📚','Archiv',['processing'=>'assigned']],
];
foreach($categoryLinks as [$icon,$label,$params]): ?>
<a href="<?php p($url->linkToRoute('reinhardterp.document.index',$params)); ?>"><span><?php p($icon); ?></span><strong><?php p($label); ?></strong></a>
<?php endforeach; ?>
</nav>

<div class="erp-kpis erp-dms-kpis"><div><span>Neu</span><strong><?php p($_['counts']['new']??0);?></strong></div><div><span>In Prüfung</span><strong><?php p($_['counts']['review']??0);?></strong></div><div><span>Zugeordnet</span><strong><?php p($_['counts']['assigned']??0);?></strong></div><div><span>Fehler</span><strong><?php p($_['counts']['error']??0);?></strong></div></div>
<section class="erp-card erp-wide"><h2>PDF-Beleg importieren</h2><form class="erp-inline-upload" method="post" enctype="multipart/form-data" action="<?php p($url->linkToRoute('reinhardterp.document.upload')); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><input type="file" name="document" accept="application/pdf,image/*" required><button class="button primary">PDF hochladen und auslesen</button></form><p class="erp-muted">Scanner/WebDAV-Ziel: <b>ERP/00_Dokumenteneingang/01_Unbearbeitet</b></p></section>
<section class="erp-card erp-wide erp-dms-toolbar-card"><form class="erp-dms-search-form" method="get" action="<?php p($url->linkToRoute('reinhardterp.document.index')); ?>"><input type="hidden" name="processing" value="<?php p($_['processing']??'all');?>"><input type="search" name="q" value="<?php p($_['q']??'');?>" placeholder="Datei, Belegnummer, Kunde, Projekt oder Lieferant suchen …"><select name="type"><option value="all">Alle Dokumentarten</option><?php foreach($types as $key=>$label):if($key==='unassigned')continue;?><option value="<?php p($key);?>" <?=($_['type']??'all')===$key?'selected':''?>><?php p($label);?></option><?php endforeach;?></select><button class="button primary">Suchen</button><a class="button" href="<?php p($url->linkToRoute('reinhardterp.document.index'));?>">Zurücksetzen</a></form>
<div class="erp-dms-filter"><?php foreach($processingLabels as $key=>$label):?><a class="button <?=($_['processing']??'all')===$key?'primary':''?>" href="<?php p($url->linkToRoute('reinhardterp.document.index',['processing'=>$key,'type'=>$_['type']??'all','q'=>$_['q']??'']));?>"><?php p($label);?></a><?php endforeach;?></div></section>
<section class="erp-card erp-wide erp-dms-list-card"><?php if(empty($_['documents'])):?><p class="erp-muted">Keine passenden Dokumente vorhanden.</p><?php else:?><div class="erp-dms-list"><?php foreach($_['documents'] as $document):$suggestedType=(string)($document['suggested_type']??'unassigned');$confidence=(int)($document['suggestion_confidence']??0);$shownType=$document['document_type']!=='unassigned'?$document['document_type']:$suggestedType;$state=(string)($document['processing_status']??'new');?><a class="erp-dms-row" href="<?php p($url->linkToRoute('reinhardterp.document.review',['id'=>$document['id']]));?>"><span class="erp-dms-row-icon"><?=str_starts_with((string)$document['mime_type'],'image/')?'🖼️':'📄'?></span><span class="erp-dms-row-main"><strong title="<?php p($document['original_name']);?>"><?php p($document['original_name']);?></strong><small><?php p(number_format(((int)$document['file_size'])/1024,1,',','.'));?> KB · <?php p($document['created_at']??'');?></small></span><span class="erp-dms-row-type"><?php p($types[$shownType]??$shownType);?><?php if($document['document_type']==='unassigned'&&$suggestedType!=='unassigned'):?><small>Vorschlag · <?php p($confidence);?> %<?php if(!empty($document['auto_rule_id'])):?> · Regel<?php endif;?></small><?php endif;?></span><?php if(!empty($document['duplicate_of'])):?><span class="erp-status-pill erp-status-warning">Mögliche Dublette</span><?php else:?><span class="erp-status-pill erp-dms-state-<?php p($state);?>"><?php p($processingLabels[$state]??$state);?></span><?php endif;?><span>›</span></a><?php endforeach;?></div><?php endif;?></section>
<section class="erp-card erp-wide"><details><summary><strong>Automatische Dokumentenregeln</strong> <span class="erp-muted">Dateinamen automatisch zuordnen</span></summary><form class="erp-rule-form" method="post" action="<?php p($url->linkToRoute('reinhardterp.document.createRule'));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input name="name" placeholder="Regelname" required><input name="matchValue" placeholder="Dateiname enthält …" required><select name="documentType"><option value="">Dokumentart unverändert</option><?php foreach($types as $key=>$label):if($key==='unassigned')continue;?><option value="<?php p($key);?>"><?php p($label);?></option><?php endforeach;?></select><input type="number" name="priority" value="100" min="1" max="999"><button class="button primary">Regel anlegen</button></form><?php if(empty($_['rules'])):?><p class="erp-muted">Noch keine Regeln vorhanden.</p><?php else:?><div class="erp-rule-list"><?php foreach($_['rules'] as $rule):?><div><span><strong><?php p($rule['name']);?></strong><small> enthält „<?php p($rule['match_value']);?>“ · Priorität <?php p($rule['priority']);?></small></span><form method="post" action="<?php p($url->linkToRoute('reinhardterp.document.deleteRule',['id'=>$rule['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><button class="button">Löschen</button></form></div><?php endforeach;?></div><?php endif;?></details></section>
</div></div>
