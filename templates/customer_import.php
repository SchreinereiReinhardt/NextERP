<?php
require __DIR__ . '/_nav.php';
use OCP\IURLGenerator;
$url = \OC::$server->get(IURLGenerator::class);
$linked = $_['linkedContacts'] ?? [];
\OCP\Util::addScript('reinhardterp', 'customer_import');
?>
<div id="app-content"><div class="erp-page erp-list-page">
<div class="erp-head"><div><h1>Kunden aus Nextcloud importieren</h1><p class="erp-sub">Kontakte auswählen und als verknüpfte Betrio-Kunden übernehmen.</p></div><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.customers')); ?>">Zurück zu Kunden</a></div>
<?php if (empty($_['contactsEnabled'])): ?>
<div class="erp-notice">Die Nextcloud-Kontakte-Schnittstelle ist nicht verfügbar.</div>
<?php elseif (empty($_['contacts'])): ?>
<div class="erp-card erp-empty">Keine Kontakte in deinen Nextcloud-Adressbüchern gefunden.</div>
<?php else: ?>
<form method="post" action="<?php p($url->linkToRoute('reinhardterp.integration.importCustomers')); ?>" id="customerImportForm">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
<div class="erp-import-toolbar"><input id="contactImportSearch" type="search" placeholder="Kontakte durchsuchen …"><button type="button" class="button" id="selectVisibleContacts">Sichtbare auswählen</button><button class="button primary">Ausgewählte Kunden importieren</button></div>
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
