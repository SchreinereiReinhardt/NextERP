<?php
require __DIR__.'/_nav.php';
$url = \OC::$server->getURLGenerator();
\OCP\Util::addScript('reinhardterp', 'document_detail');
$types = [
    'unassigned' => 'Bitte auswählen',
    'incoming_invoice' => 'Eingangsrechnung',
    'outgoing_invoice' => 'Ausgangsrechnung',
    'delivery_note' => 'Lieferschein',
    'credit_note' => 'Gutschrift',
    'bank_statement' => 'Kontoauszug',
    'cash' => 'Kassenbeleg',
    'tax' => 'Steuerunterlage',
    'offer' => 'Angebot',
    'order' => 'Auftrag',
    'report' => 'Rapport',
    'drawing' => 'Zeichnung / Plan',
    'other' => 'Sonstiges',
];
if (!empty($_['missing'])): ?>
<div id="app-content"><div class="erp-page"><div class="erp-notice">Dokument wurde nicht gefunden.</div></div></div>
<?php return; endif;
$document = $_['document'];
$preview = $url->linkToRoute('reinhardterp.document.preview', ['id' => $document['id']]);
$previewImage = $url->linkToRoute('reinhardterp.document.previewImage', ['id' => $document['id']]);
$suggestedType = (string)($document['suggested_type'] ?? 'unassigned');
$selectedType = $document['document_type'] !== 'unassigned' ? $document['document_type'] : $suggestedType;
$selectedCustomer = (int)($document['customer_id'] ?? $document['suggested_customer_id'] ?? 0);
$selectedProject = (int)($document['project_id'] ?? $document['suggested_project_id'] ?? 0);
$selectedSupplier = (int)($document['supplier_id'] ?? $document['suggested_supplier_id'] ?? 0);
$extracted = is_array($_['extractedOffer'] ?? null) ? $_['extractedOffer'] : [];
$isAssigned = (($document['status'] ?? '') === 'assigned') || (($document['processing_status'] ?? '') === 'assigned');
if ($selectedCustomer === 0 && !empty($extracted['customer_id'])) { $selectedCustomer = (int)$extracted['customer_id']; }
if ($selectedProject === 0 && !empty($extracted['project_id'])) { $selectedProject = (int)$extracted['project_id']; }
$prefillDocumentNo = (string)($document['suggested_document_no'] ?? $extracted['document_no'] ?? '');
$prefillDocumentDate = (string)($document['suggested_document_date'] ?? $extracted['offer_date'] ?? date('Y-m-d'));
$prefillNet = $document['net_amount'] ?? $extracted['net_amount'] ?? '';
$prefillVat = $document['vat_amount'] ?? $extracted['vat_amount'] ?? '';
$prefillGross = $document['gross_amount'] ?? $extracted['gross_amount'] ?? '';
?>
<div id="app-content"><div class="erp-page erp-document-workspace">
<div class="erp-head">
    <div><h1><?php p($document['original_name']); ?></h1><p class="erp-sub"><?php p($document['file_path']); ?></p></div>
    <div class="erp-actions">
        <?php if (!$isAssigned): ?>
        <form method="post" action="<?php p($url->linkToRoute('reinhardterp.document.analyse', ['id' => $document['id']])); ?>">
            <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
            <button class="button">Erneut analysieren</button>
        </form>
        <?php endif; ?>
        <a class="button" href="<?php p($url->linkToRoute('reinhardterp.document.index')); ?>">Zurück zum Eingang</a>
        <a class="button" target="_blank" href="<?php p($preview); ?>">Vorschau im neuen Tab</a>
    </div>
</div>

<?php if (!empty($_['message'])): ?><div class="erp-notice"><?php p($_['message']); ?></div><?php endif; ?>
<?php if (!empty($_['error'])): ?><div class="erp-notice erp-notice-warning"><strong>Zuordnung nicht gespeichert:</strong> <?php p($_['error']); ?></div><?php endif; ?>
<?php if (!empty($_['duplicateWarning'])): ?>
    <div class="erp-notice erp-notice-warning"><strong>Mögliche Dublette:</strong> Eine Datei mit derselben Prüfsumme ist bereits im Dokumentenbestand vorhanden. Bitte vor der Zuordnung prüfen.</div>
<?php endif; ?>
<?php if (!$isAssigned && $suggestedType !== 'unassigned'): ?>
    <div class="erp-notice"><strong>Automatischer Vorschlag:</strong> <?php p($types[$suggestedType] ?? $suggestedType); ?> mit <?php p((int)($document['suggestion_confidence'] ?? 0)); ?> % Sicherheit.<br><small><?php p($document['suggestion_reason'] ?? 'Bitte vor dem Speichern kontrollieren.'); ?></small></div>
<?php endif; ?>

<?php if (($selectedType === 'offer' || ($document['document_type'] ?? '') === 'offer') && !empty($extracted)): ?>
<section class="erp-card erp-wide erp-pdf-extraction-summary">
    <div class="erp-section-head"><div><h2>PDF-Auslesung</h2><p class="erp-muted">Digitale PDF-Texte werden automatisch ausgewertet. Bitte alle Werte vor der Übernahme kontrollieren.</p></div></div>
    <?php if (!empty($extracted['error'])): ?>
        <div class="erp-notice erp-notice-warning"><?php p($extracted['error']); ?></div>
    <?php elseif (!empty($extracted['has_text'])): ?>
        <div class="erp-kpis erp-kpis-compact">
            <div><span>Titel / Bauvorhaben</span><strong><?php p($extracted['title'] ?: '–'); ?></strong></div>
            <div><span>Angebotsnummer</span><strong><?php p($extracted['document_no'] ?: '–'); ?></strong></div>
            <div><span>Netto</span><strong><?php p($extracted['net_amount'] !== null ? number_format((float)$extracted['net_amount'], 2, ',', '.').' €' : '–'); ?></strong></div>
            <div><span>USt.-Betrag</span><strong><?php p($extracted['vat_amount'] !== null ? number_format((float)$extracted['vat_amount'], 2, ',', '.').' €' : '–'); ?></strong></div>
            <div><span>Brutto</span><strong><?php p($extracted['gross_amount'] !== null ? number_format((float)$extracted['gross_amount'], 2, ',', '.').' €' : '–'); ?></strong></div>
            <div><span>Positionen</span><strong><?php p(count($extracted['positions'] ?? [])); ?></strong></div>
        </div>
        <?php if (!empty($extracted['amount_warning'])): ?><div class="erp-notice erp-notice-warning"><?php p($extracted['amount_warning']); ?></div><?php endif; ?>
        <?php if (empty($extracted['positions'])): ?><p class="erp-muted">Kopf- und Summendaten wurden gelesen; eine eindeutig strukturierte Positionstabelle wurde nicht erkannt.</p><?php endif; ?>
    <?php endif; ?>
</section>
<?php endif; ?>
<div class="erp-document-workspace-grid">
<section class="erp-card erp-document-preview-card">
    <div class="erp-document-preview-head"><strong>Dokumentvorschau</strong><span><?php p(number_format(((int)$document['file_size']) / 1024, 1, ',', '.')); ?> KB</span></div>
    <?php if (str_starts_with((string)$document['mime_type'], 'image/')): ?>
        <img class="erp-document-image-preview" src="<?php p($preview); ?>" alt="Dokumentvorschau">
    <?php elseif ((string)$document['mime_type'] === 'application/pdf' || str_ends_with(strtolower((string)$document['file_name']), '.pdf')): ?>
        <div class="erp-document-pdf-image-wrap">
            <img id="documentPdfPreview" class="erp-document-pdf-image" src="<?php p($previewImage); ?>" alt="Erste Seite des PDF-Dokuments">
            <div id="documentPdfFallback" class="erp-document-no-preview is-hidden"><span>📄</span><p>Die PDF-Vorschau konnte nicht erzeugt werden.</p></div>
        </div>
        <div class="erp-document-preview-actions"><a class="button primary" target="_blank" rel="noopener" href="<?php p($preview); ?>">PDF vollständig öffnen</a></div>
    <?php else: ?>
        <div class="erp-document-no-preview"><span>📄</span><p>Für diesen Dateityp ist keine eingebettete Vorschau verfügbar.</p><a class="button primary" target="_blank" rel="noopener" href="<?php p($preview); ?>">Datei öffnen</a></div>
    <?php endif; ?>
</section>

<section class="erp-card erp-document-form-card"><h2><?=$isAssigned ? 'Dokumentdaten' : 'Dokument zuordnen'?></h2>
<?php if ($isAssigned): ?>
    <div class="erp-dms-meta">
        <div><span>Dokumentart</span><strong><?php p($types[$document['document_type']] ?? $document['document_type']); ?></strong></div>
        <div><span>Datum</span><strong><?php p($document['document_date'] ?? '–'); ?></strong></div>
        <div><span>Belegnummer</span><strong><?php p($document['document_no'] ?? '–'); ?></strong></div>
        <div><span>Brutto</span><strong><?php p($document['gross_amount'] !== null ? number_format((float)$document['gross_amount'], 2, ',', '.').' '.$document['currency'] : '–'); ?></strong></div>
        <div class="erp-span-2"><span>Ablage</span><strong><?php p($document['file_path']); ?></strong></div>
    </div>
<?php else: ?>
<form id="documentAssignForm" method="post" novalidate action="<?php p($url->linkToRoute('reinhardterp.document.assign', ['id' => $document['id']])); ?>">
    <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
    <div class="erp-form-grid">
        <div class="erp-span-2"><label>Dokumentart</label><select name="documentType" required><?php foreach ($types as $key => $label): ?><option value="<?php p($key); ?>" <?=$selectedType === $key ? 'selected' : ''?>><?php p($label); ?></option><?php endforeach; ?></select></div>
        <div><label>Belegdatum</label><input type="date" name="documentDate" value="<?php p($prefillDocumentDate); ?>"></div>
        <div><label>Fällig am</label><input type="date" name="dueDate"></div>
        <div class="erp-span-2"><label>Belegnummer</label><input name="documentNo" value="<?php p($prefillDocumentNo); ?>" autocomplete="off"></div>
        <div class="erp-span-2"><label>Kunde</label><select id="documentCustomer" name="customerId"><option value="">– kein Kunde –</option><?php foreach ($_['customers'] as $customer): ?><option value="<?php p($customer['id']); ?>" <?=$selectedCustomer === (int)$customer['id'] ? 'selected' : ''?>><?php p(($customer['customer_no'] ?? '').' '.$customer['name']); ?></option><?php endforeach; ?></select></div>
        <div class="erp-span-2"><label>Projekt</label><select id="documentProject" name="projectId"><option value="">– kein Projekt –</option><?php foreach ($_['projects'] as $project): ?><option value="<?php p($project['id']); ?>" data-customer-id="<?php p($project['customer_id'] ?? ''); ?>" <?=$selectedProject === (int)$project['id'] ? 'selected' : ''?>><?php p(($project['project_no'] ?? '').' '.$project['title']); ?></option><?php endforeach; ?></select><small class="erp-muted">Nach Auswahl eines Kunden werden passende Projekte bevorzugt angezeigt.</small></div>
        <div class="erp-span-2"><label>Lieferant</label><select name="supplierId"><option value="">– kein Lieferant –</option><?php foreach ($_['suppliers'] as $supplier): ?><option value="<?php p($supplier['id']); ?>" <?=$selectedSupplier === (int)$supplier['id'] ? 'selected' : ''?>><?php p($supplier['name']); ?></option><?php endforeach; ?></select></div>
        <div><label>Netto</label><input id="documentNet" type="number" step="0.01" name="netAmount" value="<?php p($prefillNet); ?>"></div>
        <div><label>USt.</label><input id="documentVat" type="number" step="0.01" name="vatAmount" value="<?php p($prefillVat); ?>"></div>
        <div><label>Brutto</label><input id="documentGross" type="number" step="0.01" name="grossAmount" value="<?php p($prefillGross); ?>"></div>
        <div><label>Währung</label><input name="currency" value="EUR" maxlength="3"></div>
        <div class="erp-span-2"><label>Bemerkung</label><textarea name="notes" rows="4"></textarea></div>
    </div>
    <div class="erp-actions"><button class="button primary">Zuordnen, umbenennen und ablegen</button><a class="button" href="<?php p($url->linkToRoute('reinhardterp.document.index')); ?>">Später bearbeiten</a></div>
    <?php if ($selectedType === 'offer'): ?>
        <div class="erp-notice"><strong>Nächster Schritt:</strong> Nach dem Zuordnen erscheint hier automatisch der Bereich <em>ERP-Angebot</em> mit dem Button <strong>„Als ERP-Angebot übernehmen“</strong>.</div>
    <?php endif; ?>
</form>
<?php endif; ?>

<?php if ($isAssigned && ($document['document_type'] === 'offer' || $selectedType === 'offer')): ?>
<div class="erp-dms-import-box">
    <h3>ERP-Angebot</h3>
    <?php if (!empty($document['offer_id'])): ?>
        <p>Dieses Dokument wurde bereits als Angebot übernommen.</p>
        <a class="button primary" href="<?php p($url->linkToRoute('reinhardterp.business.offerDetail', ['id' => $document['offer_id']])); ?>">Angebot öffnen</a>
    <?php else: ?>
        <p class="erp-muted">Übernimmt die geprüften Dokumentdaten in das Angebotsmodul. Positionen können anschließend im Angebot ergänzt werden.</p>
        <form method="post" action="<?php p($url->linkToRoute('reinhardterp.document.importOffer', ['id' => $document['id']])); ?>">
            <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
            <div class="erp-form-grid">
                <div class="erp-span-2"><label>Titel</label><input name="title" required value="<?php p((string)($extracted['title'] ?? '') ?: pathinfo((string)$document['original_name'], PATHINFO_FILENAME)); ?>"></div>
                <div class="erp-span-2"><label>Leistungsbeschreibung</label><textarea name="description" rows="4"><?php p((string)($extracted['description'] ?? '') ?: 'Importiert aus '.$document['original_name']); ?></textarea></div>
                <div><label>Angebotsdatum</label><input type="date" name="offerDate" value="<?php p($document['document_date'] ?? $extracted['offer_date'] ?? date('Y-m-d')); ?>"></div>
                <div><label>Gültig bis</label><input type="date" name="validUntil" value="<?php p($extracted['valid_until'] ?? ''); ?>"></div>
                <div><label>Netto</label><input id="offerImportNet" type="number" step="0.01" name="netAmount" value="<?php p($document['net_amount'] ?? $extracted['net_amount'] ?? ''); ?>"></div>
                <div><label>USt.-Satz %</label><input id="offerImportVatRate" type="number" step="0.01" name="vatRate" value="<?php p($extracted['vat_rate'] ?? 19); ?>"></div>
                <div><label>USt.-Betrag</label><input id="offerImportVatAmount" type="number" step="0.01" name="vatAmount" value="<?php p($document['vat_amount'] ?? $extracted['vat_amount'] ?? ''); ?>"></div>
                <div><label>Brutto</label><input id="offerImportGross" type="number" step="0.01" name="grossAmount" value="<?php p($document['gross_amount'] ?? $extracted['gross_amount'] ?? ''); ?>"></div>
                <div class="erp-span-2"><small id="offerImportAmountCheck" class="erp-muted">Prüfung: Netto + USt.-Betrag = Brutto</small></div>
                <div><label>Kunde</label><select name="customerId" required><option value="">Bitte wählen</option><?php foreach ($_['customers'] as $customer): ?><option value="<?php p($customer['id']); ?>" <?=((int)($document['customer_id'] ?? 0) === (int)$customer['id']) ? 'selected' : ''?>><?php p(($customer['customer_no'] ?? '').' '.$customer['name']); ?></option><?php endforeach; ?></select></div>
                <div class="erp-span-2"><label>Vorhandenes Projekt</label><select name="projectId"><option value="">– ohne Projekt –</option><?php foreach ($_['projects'] as $project): ?><option value="<?php p($project['id']); ?>" <?=((int)($document['project_id'] ?? 0) === (int)$project['id']) ? 'selected' : ''?>><?php p(($project['project_no'] ?? '').' '.$project['title']); ?></option><?php endforeach; ?></select></div>
                <div class="erp-span-2 erp-checkbox-line"><label><input type="checkbox" name="createProject" value="1"> Neues Projekt anlegen, falls kein Projekt ausgewählt ist</label></div>
                <div class="erp-span-2"><label>Name des neuen Projekts</label><input name="projectTitle" placeholder="z. B. Küche Familie Müller" value="<?php p($extracted['title'] ?? ''); ?>"></div>
                <?php if (!empty($extracted['positions'])): ?>
                <div class="erp-span-2"><h4>Erkannte Angebotspositionen</h4><div class="erp-table erp-offer-import-items"><table><thead><tr><th>Beschreibung</th><th>Menge</th><th>Einheit</th><th>Einzelpreis</th><th>Gesamt</th></tr></thead><tbody>
                <?php foreach ($extracted['positions'] as $position): ?><tr>
                    <td><input name="positionDescription[]" value="<?php p($position['description']); ?>"></td>
                    <td><input type="number" step="0.01" name="positionQuantity[]" value="<?php p($position['quantity']); ?>"></td>
                    <td><input name="positionUnit[]" value="<?php p($position['unit']); ?>"></td>
                    <td><input type="number" step="0.01" name="positionUnitPrice[]" value="<?php p($position['unit_price']); ?>"></td>
                    <td><input type="number" step="0.01" name="positionTotal[]" value="<?php p($position['total_price']); ?>"></td>
                </tr><?php endforeach; ?>
                </tbody></table></div></div>
                <?php endif; ?>
                <div class="erp-span-2"><label>Notizen</label><textarea name="notes" rows="3"><?php if (!empty($extracted['text_excerpt'])): ?>PDF automatisch ausgelesen. Werte vor Übernahme geprüft.<?php endif; ?></textarea></div>
            </div>
            <button class="button primary">Als ERP-Angebot übernehmen</button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>
</section>
</div></div></div>
