<?php
style('reinhardterp','style');
$url = \OC::$server->getURLGenerator();
$customerPath = trim((string)($customer->getFolderPath() ?? ''), '/');
$filesBase = $url->linkToRoute('files.view.index');
$folderUrl = static fn(string $path): string => $filesBase.'?dir='.rawurlencode('/'.trim($path, '/'));
$fileUrl = static function(string $path) use ($filesBase): string { $path=trim($path,'/'); return $filesBase.'?dir='.rawurlencode(dirname('/'.$path)).'&scrollto='.rawurlencode(basename($path)); };
$formatSize = static function(int $bytes): string { if($bytes<1024)return $bytes.' B'; if($bytes<1048576)return number_format($bytes/1024,1,',','.').' KB'; if($bytes<1073741824)return number_format($bytes/1048576,1,',','.').' MB'; return number_format($bytes/1073741824,1,',','.').' GB'; };
$statusOrder=['Anfrage'=>12,'Angebot'=>25,'Auftrag'=>38,'Fertigung'=>50,'Montage'=>65,'Abnahme'=>78,'Abrechnung'=>90,'Abgeschlossen'=>100,'offen'=>15];
?>
<div id="app-content"><div id="app-content-wrapper"><?php print_unescaped($this->inc('_nav')); ?><main class="erp-main erp-customer-pro">
<?php if(!empty($_['message'])): ?><div class="erp-notice"><?php p($_['message']); ?></div><?php endif; ?>
<header class="erp-customer-hero"><div><span class="erp-record-kicker">Kundenakte Pro</span><h1><?=p($customer->getCustomerNo().' · '.$customer->getName())?></h1><p><?=p($customer->getContactName()??'')?><?php if($customer->getPhone()): ?> · <a href="tel:<?=p($customer->getPhone())?>"><?=p($customer->getPhone())?></a><?php endif; ?><?php if($customer->getEmail()): ?> · <a href="mailto:<?=p($customer->getEmail())?>"><?=p($customer->getEmail())?></a><?php endif; ?></p></div><div class="erp-actions"><?php if($customerPath!==''): ?><a class="button primary" target="_blank" rel="noopener noreferrer" href="<?=p($folderUrl($customerPath))?>">📁 Kundenordner</a><?php endif; ?><a class="button" href="<?=p($url->linkToRoute('reinhardterp.page.projectForm',['customerId'=>$customer->getId()]))?>">+ Neues Projekt</a><a class="button" href="<?=p($url->linkToRoute('reinhardterp.page.customerForm',['id'=>$customer->getId()]))?>">✏ Bearbeiten</a></div></header>

<section class="erp-customer-kpis">
<div><span>Projekte</span><strong><?=p($stats['projects'])?></strong></div>
<div><span>Rapporte</span><strong><?=p($stats['reports'])?></strong><small><?=p($stats['openReports'])?> offen</small></div>
<div><span>Dokumente</span><strong><?=p($stats['documents'])?></strong><small>letzte 40 erfasst</small></div>
<div><span>Arbeitszeit</span><strong><?=p(number_format((float)$stats['hours'],2,',','.'))?></strong><small>Stunden gesamt</small></div>
</section>

<div class="erp-customer-layout">
<div class="erp-customer-main">
<section class="erp-card erp-wide"><div class="erp-section-head"><div><h2>Projekte</h2><p class="erp-muted">Laufende und abgeschlossene Projekte dieses Kunden.</p></div></div>
<?php if(empty($projects)): ?><p class="erp-muted">Noch keine Projekte vorhanden.</p><?php else: ?><div class="erp-customer-project-grid"><?php foreach($projects as $project): $progress=$statusOrder[$project['status']]??15; ?><a class="erp-customer-project-card" href="<?=p($url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$project['id']]))?>"><div><small><?=p($project['project_no'])?></small><h3><?=p($project['title'])?></h3></div><span class="erp-status-pill"><?=p($project['status'])?></span><div class="erp-progress"><i style="width:<?=p($progress)?>%"></i></div><footer><span><?=p($progress)?> % Fortschritt</span><strong>Öffnen →</strong></footer></a><?php endforeach; ?></div><?php endif; ?></section>

<section class="erp-card erp-wide erp-document-center"><div class="erp-section-head"><div><h2>Dokumentencenter</h2><p class="erp-muted">Letzte Dateien aus dem gesamten Kundenordner.</p></div></div>
<?php if($customerPath!==''): ?><form class="erp-upload-bar" method="post" enctype="multipart/form-data" action="<?=p($url->linkToRoute('reinhardterp.page.uploadCustomerDocument',['id'=>$customer->getId()]))?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><input type="file" name="document" required><select name="targetFolder"><option value="01_Kundendaten">Kundendaten</option><option value="02_Anfragen">Anfragen</option><option value="03_Angebote">Angebote</option><option value="04_Auftraege">Aufträge</option><option value="06_Rechnungen">Rechnungen</option><option value="07_Korrespondenz">Korrespondenz</option><option value="08_Bilder">Bilder</option><option value="09_Sonstiges" selected>Sonstiges</option></select><button class="button primary">Datei hochladen</button></form><?php endif; ?>
<?php if(empty($documents)): ?><p class="erp-muted">Noch keine Dateien im Kundenordner.</p><?php else: ?><div class="erp-document-list"><?php foreach($documents as $doc): ?><a class="erp-document-row" target="_blank" rel="noopener noreferrer" href="<?=p($fileUrl($doc['path']))?>"><span class="erp-file-icon"><?=str_starts_with((string)$doc['mime'],'image/')?'🖼️':(((string)$doc['mime']==='application/pdf')?'📄':'📎')?></span><span class="erp-file-main"><strong><?=p($doc['name'])?></strong><small><?=p(dirname($doc['path']))?></small></span><span class="erp-file-meta"><?=p($formatSize((int)$doc['size']))?><br><?=p(date('d.m.Y H:i',(int)$doc['mtime']))?></span><span class="button">Öffnen</span></a><?php endforeach; ?></div><?php endif; ?></section>

<section class="erp-card erp-wide"><div class="erp-section-head"><div><h2>Kundenhistorie</h2><p class="erp-muted">Chronik aller Kunden-, Projekt-, Dokument- und Rapportaktivitäten.</p></div></div><?php print_unescaped($this->inc('_activity_timeline',['activities'=>$activities])); ?></section>
</div>

<aside class="erp-customer-sidebar">
<section class="erp-card erp-nextcloud-link-card"><div class="erp-section-head"><div><h2>Nextcloud Kontakte</h2><p class="erp-muted">Kundenstammdaten mit einem vorhandenen Nextcloud-Kontakt verbinden.</p></div></div>
<?php if (!$contactsIntegrationEnabled): ?>
<div class="erp-notice">Die Nextcloud-Kontakte-Schnittstelle ist nicht verfügbar.</div>
<?php elseif ($customer->getNcContactId()): ?>
<div class="erp-integration-state is-connected"><span>✓ Verbunden</span><strong><?=p($customer->getNcContactLabel() ?: 'Nextcloud-Kontakt')?></strong><small><?php if ($customer->getNcContactSyncedAt()): ?>Zuletzt synchronisiert: <?=p($customer->getNcContactSyncedAt()->format('d.m.Y H:i'))?><?php else: ?>Noch nicht synchronisiert<?php endif; ?></small></div>
<div class="erp-actions">
<form method="post" action="<?=p($url->linkToRoute('reinhardterp.integration.syncCustomerContact',['customerId'=>$customer->getId()]))?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button primary">Kontakt synchronisieren</button></form>
<form method="post" action="<?=p($url->linkToRoute('reinhardterp.integration.unlinkCustomerContact',['customerId'=>$customer->getId()]))?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button">Verbindung trennen</button></form>
</div>
<?php else: ?>
<?php if (empty($nextcloudContacts)): ?>
<p class="erp-muted">Keine Kontakte in deinen persönlichen oder geteilten Nextcloud-Adressbüchern gefunden.</p>
<?php else: ?>
<form class="erp-settings-form" method="post" action="<?=p($url->linkToRoute('reinhardterp.integration.linkCustomerContact',['customerId'=>$customer->getId()]))?>">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
<label>Nextcloud-Kontakt</label>
<select name="contactSelection" required onchange="const [book,id]=this.value.split('::');this.form.addressBookKey.value=book;this.form.contactId.value=id;">
<option value="">Kontakt auswählen …</option>
<?php foreach ($nextcloudContacts as $ncContact): ?>
<option value="<?=p($ncContact['addressBookKey'].'::'.$ncContact['id'])?>"><?=p($ncContact['label'].' · '.$ncContact['addressBookName'])?><?php if ($ncContact['email']): ?> · <?=p($ncContact['email'])?><?php endif; ?></option>
<?php endforeach; ?>
</select>
<input type="hidden" name="addressBookKey" value="">
<input type="hidden" name="contactId" value="">
<p class="erp-muted">Name, Telefon, Mobilnummer, E-Mail und Anschrift werden aus dem Kontakt übernommen. Die Kundennummer und ERP-Daten bleiben erhalten.</p>
<button class="button primary">Kontakt verbinden</button>
</form>
<?php endif; ?>
<?php endif; ?>
</section>
<section class="erp-card"><h2>Kundendaten</h2><dl class="erp-info-list"><div><dt>Ansprechpartner</dt><dd><?=p($customer->getContactName()??'—')?></dd></div><div><dt>Telefon</dt><dd><?php if($customer->getPhone()):?><a href="tel:<?=p($customer->getPhone())?>"><?=p($customer->getPhone())?></a><?php else:?>—<?php endif;?></dd></div><div><dt>Mobil</dt><dd><?php if($customer->getMobile()):?><a href="tel:<?=p($customer->getMobile())?>">📱 <?=p($customer->getMobile())?></a><?php else:?>—<?php endif;?></dd></div><div><dt>E-Mail</dt><dd><?php if($customer->getEmail()):?><a href="mailto:<?=p($customer->getEmail())?>"><?=p($customer->getEmail())?></a><?php else:?>—<?php endif;?></dd></div><div><dt>Adresse</dt><dd><?php $addressLines=array_filter([$customer->getStreet(),trim((string)$customer->getPostalCode().' '.(string)$customer->getCity()),$customer->getCountry()]); ?><?= $addressLines ? nl2br(p(implode("\n",$addressLines))) : '—' ?></dd></div></dl><?php if($customer->getNotes()):?><p class="erp-note-box"><?=nl2br(p($customer->getNotes()))?></p><?php endif;?></section>

<section class="erp-card"><div class="erp-section-head"><div><h2>Ansprechpartner</h2><p class="erp-muted">Zusätzliche Kontakte beim Kunden.</p></div></div>
<?php if(empty($contacts)):?><p class="erp-muted">Noch keine zusätzlichen Ansprechpartner.</p><?php else:?><div class="erp-contact-list"><?php foreach($contacts as $contact):?><article><div><strong><?=p($contact['name'])?></strong><?php if(!empty($contact['is_primary'])):?><span class="erp-primary-chip">Hauptkontakt</span><?php endif;?><small><?=p($contact['position']??'')?></small></div><p><?php if($contact['phone']):?><a href="tel:<?=p($contact['phone'])?>">☎ <?=p($contact['phone'])?></a><br><?php endif;?><?php if($contact['mobile']):?><a href="tel:<?=p($contact['mobile'])?>">📱 <?=p($contact['mobile'])?></a><br><?php endif;?><?php if($contact['email']):?><a href="mailto:<?=p($contact['email'])?>">✉ <?=p($contact['email'])?></a><?php endif;?></p><form method="post" action="<?=p($url->linkToRoute('reinhardterp.customerWorkspace.deleteContact',['customerId'=>$customer->getId(),'id'=>$contact['id']]))?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button">Entfernen</button></form></article><?php endforeach;?></div><?php endif;?>
<details class="erp-inline-create"><summary>+ Ansprechpartner hinzufügen</summary><form method="post" action="<?=p($url->linkToRoute('reinhardterp.customerWorkspace.addContact',['customerId'=>$customer->getId()]))?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><input name="name" placeholder="Name" required><input name="position" placeholder="Funktion / Position"><input name="phone" placeholder="Telefon"><input name="mobile" placeholder="Mobil"><input type="email" name="email" placeholder="E-Mail"><textarea name="notes" rows="2" placeholder="Notiz"></textarea><label class="erp-check-row"><input type="checkbox" name="isPrimary" value="1"><span>Als Hauptkontakt markieren</span></label><button class="button primary">Speichern</button></form></details></section>

<section class="erp-card"><div class="erp-section-head"><div><h2>Wiedervorlagen</h2><p class="erp-muted">Rückrufe und nächste Schritte.</p></div></div>
<?php if(empty($reminders)):?><p class="erp-muted">Keine Wiedervorlagen.</p><?php else:?><div class="erp-reminder-list"><?php foreach($reminders as $reminder):$overdue=empty($reminder['is_done'])&&$reminder['due_date']<date('Y-m-d');?><form class="erp-reminder-row <?=$reminder['is_done']?'is-done':''?> <?=$overdue?'is-overdue':''?>" method="post" action="<?=p($url->linkToRoute('reinhardterp.customerWorkspace.toggleReminder',['customerId'=>$customer->getId(),'id'=>$reminder['id']]))?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button title="Status ändern"><?=$reminder['is_done']?'✓':'○'?></button><span><strong><?=p($reminder['title'])?></strong><small><?=p(date('d.m.Y',strtotime($reminder['due_date'])))?><?= $overdue?' · überfällig':'' ?></small></span></form><?php endforeach;?></div><?php endif;?>
<details class="erp-inline-create"><summary>+ Wiedervorlage anlegen</summary><form method="post" action="<?=p($url->linkToRoute('reinhardterp.customerWorkspace.addReminder',['customerId'=>$customer->getId()]))?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><input name="title" placeholder="Aufgabe / Rückruf" required><input type="date" name="dueDate" value="<?=p(date('Y-m-d'))?>" required><textarea name="notes" rows="2" placeholder="Notiz"></textarea><button class="button primary">Speichern</button></form></details></section>

<section class="erp-card"><h2>Letzte Rapporte</h2><?php if(empty($reports)):?><p class="erp-muted">Noch keine Rapporte.</p><?php else:?><div class="erp-compact-links"><?php foreach(array_slice($reports,0,8) as $report):?><a href="<?=p($url->linkToRoute('reinhardterp.module.reportDetail',['id'=>$report['id']]))?>"><span><strong><?=p($report['report_no'])?></strong><small><?=p($report['project_no'].' · '.$report['report_date'])?></small></span><b><?=p($report['status'])?></b></a><?php endforeach;?></div><?php endif;?></section>
</aside>
</div>
</main></div></div>
