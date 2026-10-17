<?php
require __DIR__ . '/_nav.php';
use OCP\IURLGenerator;
$url = \OCP\Server::get(IURLGenerator::class);
$linked = $_['linkedContacts'] ?? [];
\OCP\Util::addScript('reinhardterp', 'customer_import');
?>
<div id="app-content"><div class="erp-page erp-list-page">
<div class="erp-head"><div><h1>Kunden importieren</h1><p class="erp-sub">Kunden aus CSV oder Nextcloud Kontakte übernehmen.</p></div><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.customers')); ?>">Zurück zu Kunden</a></div>
<section class="erp-card erp-csv-import" id="erpCsvCustomerImport"
 data-analyze-url="<?php p($url->linkToRoute('reinhardterp.integration.analyzeCustomerCsv')); ?>"
 data-import-url="<?php p($url->linkToRoute('reinhardterp.integration.importCustomerCsv')); ?>">
<div class="erp-head"><div><h2>CSV-Kundenimport <span class="erp-pro-badge">PRO</span></h2><p class="erp-sub">CSV aus WISO MeinBüro, Lexware, DATEV/Excel oder anderen Programmen einlesen. Spalten werden automatisch erkannt und können manuell zugeordnet werden.</p></div></div>
<?php if (!empty($_['csvProLocked'])): ?>
<div class="erp-notice">Der CSV-Kundenimport ist eine Betrio Pro-Funktion. <a href="<?php p($_['betrioProUrl']); ?>" target="_blank" rel="noopener">Betrio Pro ansehen</a></div>
<?php else: ?>
<input type="hidden" id="csvRequestToken" value="<?php p($_['requesttoken']); ?>">
<div class="erp-form-grid"><label class="erp-field"><span>CSV-Datei</span><input id="customerCsvFile" type="file" accept=".csv,text/csv,text/plain"></label></div>
<div id="csvImportStatus" class="erp-notice" hidden></div>
<div id="csvMappingArea" hidden>
<h3>Spalten zuordnen</h3><p class="erp-sub">Betrio schlägt die Zuordnung vor. Jede CSV-Spalte kann geändert oder auf „Nicht importieren“ gesetzt werden.</p>
<div class="erp-table-wrap"><table class="erp-table"><thead><tr><th>CSV-Spalte</th><th>Beispiel</th><th>Betrio-Feld</th></tr></thead><tbody id="csvMappingRows"></tbody></table></div>
<div class="erp-actions"><button type="button" class="button" id="csvAnalyzeButton">Vorschau prüfen</button></div>
</div>
<div id="csvPreviewArea" hidden><h3>Import-Vorschau</h3><div class="erp-kpi-grid"><div class="erp-kpi"><strong id="csvTotal">0</strong><span>Datensätze</span></div><div class="erp-kpi"><strong id="csvNew">0</strong><span>Neu</span></div><div class="erp-kpi"><strong id="csvDuplicates">0</strong><span>Dubletten</span></div><div class="erp-kpi"><strong id="csvInvalid">0</strong><span>Fehlerhaft</span></div></div><div id="csvPreviewRows"></div><div class="erp-actions"><button type="button" class="button primary" id="csvImportButton">Neue Kunden importieren</button></div></div>
<?php endif; ?>
</section>

<h2>Aus Nextcloud Kontakte importieren</h2>
<?php if (empty($_['contactsEnabled'])): ?>
<div class="erp-notice">Die Nextcloud-Kontakte-Schnittstelle ist nicht verfügbar.</div>
<?php elseif (empty($_['contacts'])): ?>
<div class="erp-card erp-empty">Keine Kontakte in deinen Nextcloud-Adressbüchern gefunden.</div>
<?php else: ?>
<form method="post" action="<?php p($url->linkToRoute('reinhardterp.integration.importCustomers')); ?>" id="customerImportForm">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
<div class="erp-import-toolbar"><input id="contactImportSearch" type="search"><button type="button" class="button" id="selectVisibleContacts">Sichtbare auswählen</button><button class="button primary">Ausgewählte Kunden importieren</button></div>
<div class="erp-import-list" id="contactImportList">
<?php foreach ($_['contacts'] as $contact): $key=$contact['addressBookKey'].'::'.$contact['id']; $already=isset($linked[$key]); ?>
<label class="erp-import-contact <?php p($already ? 'is-linked' : ''); ?>" data-search="<?php p(mb_strtolower($contact['label'].' '.$contact['fullName'].' '.$contact['organisation'].' '.$contact['email'].' '.$contact['phone'].' '.$contact['addressBookName'])); ?>">
<input type="checkbox" name="contactSelections[]" value="<?php p($key); ?>" <?php if ($already): ?>disabled<?php endif; ?>>
<span class="erp-avatar-placeholder"><?php p(mb_strtoupper(mb_substr($contact['label'],0,1))); ?></span>
<span class="erp-import-contact-main"><strong><?php p($contact['label']); ?></strong><small><?php p($contact['addressBookName']); ?><?php if ($contact['fullName'] && $contact['organisation']): ?> · <?php p($contact['fullName']); ?><?php endif; ?></small><small><?php p(implode(' · ', array_filter([$contact['email'],$contact['phone']]))); ?></small></span>
<span><?php if ($already): ?><b class="erp-sync-chip">bereits Kunde: <?php p($linked[$key]); ?></b><?php else: ?>Importieren<?php endif; ?></span>
</label>
<?php endforeach; ?>
</div>
<div class="erp-actions"><button class="button primary">Ausgewählte Kunden importieren</button></div>
</form>

<?php endif; ?>
</div></div>
