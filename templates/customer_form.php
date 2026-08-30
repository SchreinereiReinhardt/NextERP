<?php
require __DIR__ . '/_nav.php';
use OCP\IURLGenerator;
$url = \OC::$server->get(IURLGenerator::class);
$customer = $_['customer'];
$isLinked = $customer && $customer->getNcContactId();
?>
<div id="app-content"><div class="erp-page erp-form-page">
<div class="erp-head"><div><h1><?php p($customer ? 'Kunde bearbeiten' : 'Neuer Kunde'); ?></h1><p class="erp-sub"><?php p($customer ? 'Stammdaten ändern. Ein verbundener Nextcloud-Kontakt wird automatisch aktualisiert.' : 'Der neue Kunde kann gleichzeitig in Nextcloud Kontakte angelegt werden.'); ?></p></div></div>
<form method="post" action="<?php p($url->linkToRoute('reinhardterp.customer.save')); ?>">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
<?php if ($customer): ?><input type="hidden" name="id" value="<?php p($customer->getId()); ?>"><?php endif; ?>
<div class="erp-form-grid">
<div><label>Kundennummer</label><input name="customerNo" readonly value="<?php p($customer?->getCustomerNo() ?? ''); ?>" placeholder="wird automatisch vergeben"></div>
<div><label>Firma / Kundenname *</label><input name="name" required value="<?php p($customer?->getName() ?? ''); ?>"></div>
<div><label>Ansprechpartner</label><input name="contactName" value="<?php p($customer?->getContactName() ?? ''); ?>"></div>
<div><label>Telefon</label><input name="phone" inputmode="tel" autocomplete="tel" value="<?php p($customer?->getPhone() ?? ''); ?>"></div>
<div><label>Mobilnummer</label><input name="mobile" inputmode="tel" autocomplete="tel-national" value="<?php p($customer?->getMobile() ?? ''); ?>"></div>
</div>
<label>E-Mail</label><input name="email" type="email" value="<?php p($customer?->getEmail() ?? ''); ?>">
<fieldset class="erp-address-fields">
<legend>Adresse</legend>
<div class="erp-form-grid">
<div><label>Straße und Hausnummer</label><input name="street" autocomplete="street-address" value="<?php p($customer?->getStreet() ?? ''); ?>" placeholder="Korbacher Straße 300"></div>
<div><label>Postleitzahl</label><input name="postalCode" autocomplete="postal-code" value="<?php p($customer?->getPostalCode() ?? ''); ?>" placeholder="34270"></div>
<div><label>Ort</label><input name="city" autocomplete="address-level2" value="<?php p($customer?->getCity() ?? ''); ?>" placeholder="Schauenburg"></div>
<div><label>Land</label><input name="country" autocomplete="country-name" value="<?php p($customer?->getCountry() ?? 'Deutschland'); ?>" placeholder="Deutschland"></div>
</div>
</fieldset>
<section class="erp-card" style="margin-top:18px">
<h2>E-Rechnung & Abrechnung</h2>
<p class="erp-muted">Optionale Rechnungsdaten des Kunden. Leitweg-ID und Käuferreferenzen werden später automatisch für XRechnungen verwendet.</p>
<div class="erp-form-grid">
<div><label>Kundentyp</label><select name="customerType"><?php $ct=$customer?->getCustomerType() ?: 'business'; ?><option value="private" <?= $ct==='private'?'selected':'' ?>>Privatkunde</option><option value="business" <?= $ct==='business'?'selected':'' ?>>Unternehmen</option><option value="public" <?= $ct==='public'?'selected':'' ?>>Öffentlicher Auftraggeber</option></select></div>
<div><label>Bevorzugtes Rechnungsformat</label><select name="invoiceFormat"><?php $if=$customer?->getInvoiceFormat() ?: 'pdf'; ?><option value="pdf" <?= $if==='pdf'?'selected':'' ?>>PDF</option><option value="xrechnung" <?= $if==='xrechnung'?'selected':'' ?>>XRechnung</option><option value="zugferd" <?= $if==='zugferd'?'selected':'' ?>>ZUGFeRD</option></select></div>
<div><label>Rechnungs-E-Mail</label><input type="email" name="invoiceEmail" value="<?php p($customer?->getInvoiceEmail() ?? ''); ?>" placeholder="rechnung@kunde.de"></div>
<div><label>USt-IdNr.</label><input name="vatId" value="<?php p($customer?->getVatId() ?? ''); ?>" placeholder="DE123456789"></div>
<div><label>Steuernummer</label><input name="taxNo" value="<?php p($customer?->getTaxNo() ?? ''); ?>"></div>
<div><label>Leitweg-ID</label><input name="leitwegId" value="<?php p($customer?->getLeitwegId() ?? ''); ?>" placeholder="z. B. 991-...-...-.."></div>
<div><label>Lieferantennummer beim Kunden</label><input name="supplierNo" value="<?php p($customer?->getSupplierNo() ?? ''); ?>"></div>
<div><label>Käuferreferenz / Buyer Reference</label><input name="buyerReference" value="<?php p($customer?->getBuyerReference() ?? ''); ?>"></div>
<div><label>Standard-Bestellreferenz</label><input name="purchaseOrderReference" value="<?php p($customer?->getPurchaseOrderReference() ?? ''); ?>"></div>
<div><label>Kostenstelle</label><input name="costCenter" value="<?php p($customer?->getCostCenter() ?? ''); ?>"></div>
</div>
</section>
<label>Notizen</label><textarea name="notes" rows="4"><?php p($customer?->getNotes() ?? ''); ?></textarea>

<?php if (!empty($_['contactsEnabled'])): ?>
<section class="erp-card erp-contact-save-box">
<h2>Nextcloud Kontakte</h2>
<?php if ($isLinked): ?>
<div class="erp-integration-state is-connected"><span>✓ Verbunden</span><strong><?php p($customer->getNcContactLabel() ?: 'Nextcloud-Kontakt'); ?></strong><small>Änderungen an Name, Ansprechpartner, Telefon, Mobilnummer, E-Mail und Anschrift werden beim Speichern automatisch übertragen.</small></div>
<?php elseif (!empty($_['addressBooks'])): ?>
<label class="erp-check-row"><input type="checkbox" name="saveToNextcloudContacts" value="1" checked><span><strong>Auch als Nextcloud-Kontakt speichern</strong><small>Der Kunde steht danach automatisch im Adressbuch und auf synchronisierten Handys zur Verfügung.</small></span></label>
<label>Ziel-Adressbuch</label>
<select name="addressBookKey" required>
<?php foreach ($_['addressBooks'] as $book): ?><option value="<?php p($book['key']); ?>"><?php p($book['name']); ?><?php if ($book['shared']): ?> · geteilt<?php endif; ?></option><?php endforeach; ?>
</select>
<?php else: ?>
<div class="erp-notice">Kein persönliches oder geteiltes Nextcloud-Adressbuch verfügbar.</div>
<?php endif; ?>
</section>
<?php endif; ?>

<div class="erp-actions"><button class="button primary"><?php p($customer ? 'Änderungen speichern' : 'Kunde speichern und Ordner anlegen'); ?></button><?php if ($customer): ?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.customerDetail',['id'=>$customer->getId()])); ?>">Zur Kundenakte</a><?php else: ?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.customers')); ?>">Abbrechen</a><?php endif; ?></div>
</form>
</div></div>
