<?php
require __DIR__ . '/_nav.php';
use OCP\IURLGenerator;
use OCP\App\IAppManager;

$url = \OC::$server->get(IURLGenerator::class);
$mailEnabled = false;
try {
    $mailEnabled = \OC::$server->get(IAppManager::class)->isEnabledForUser('mail');
} catch (\Throwable $e) {
    $mailEnabled = false;
}
script('reinhardterp', 'customers');
?>
<div id="app-content"><div class="erp-page erp-list-page">
<div class="erp-head">
    <div><h1>Kunden</h1><p class="erp-sub">Kundenakten und Nextcloud Kontakte gemeinsam verwalten.</p></div>
    <div class="erp-actions">
        <a class="button primary" href="<?php p($url->linkToRoute('reinhardterp.page.customerForm')); ?>">+ Neuer Kunde</a>
        <?php if (!empty($_['contactsEnabled'])): ?>
            <a class="button" href="<?php p($url->linkToRoute('reinhardterp.integration.customerImport')); ?>">↓ Aus Nextcloud importieren</a>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($_['message'])): ?><div class="erp-notice"><?php p($_['message']); ?></div><?php endif; ?>
<?php if (!$_['customers']): ?>
<div class="erp-card erp-empty">Noch keine Kunden vorhanden.</div>
<?php else: ?>
<section class="erp-customer-filter" aria-label="Kunden filtern">
    <div class="erp-customer-search-wrap">
        <span aria-hidden="true">⌕</span>
        <input id="erpCustomerSearch" type="search" autocomplete="off" placeholder="Kunde, Ansprechpartner, Nummer, Telefon, Mobilnummer oder E-Mail suchen …" aria-label="Kunden durchsuchen">
        <button id="erpCustomerSearchClear" type="button" class="erp-search-clear" hidden aria-label="Suche leeren">×</button>
    </div>
    <div class="erp-alpha-filter" id="erpCustomerAlpha" aria-label="Kunden nach Anfangsbuchstaben filtern">
        <button type="button" class="is-active" data-letter="all">Alle</button>
        <?php foreach (range('A', 'Z') as $letter): ?>
            <button type="button" data-letter="<?php p($letter); ?>"><?php p($letter); ?></button>
        <?php endforeach; ?>
        <button type="button" data-letter="#">#</button>
    </div>
    <div class="erp-filter-summary"><strong id="erpCustomerVisibleCount"><?php p(count($_['customers'])); ?></strong> von <?php p(count($_['customers'])); ?> Kunden sichtbar</div>
</section>

<div class="erp-record-grid erp-customer-grid" id="erpCustomerGrid">
<?php foreach ($_['customers'] as $c):
    $name = trim((string)$c->getName());
    $initial = mb_strtoupper(mb_substr($name, 0, 1));
    $alpha = preg_match('/^[A-ZÄÖÜ]$/u', $initial) ? strtr($initial, ['Ä'=>'A','Ö'=>'O','Ü'=>'U']) : '#';
    $search = mb_strtolower(implode(' ', array_filter([
        $c->getCustomerNo(), $name, $c->getContactName(), $c->getPhone(), $c->getMobile(), $c->getEmail()
    ])));
    $email = trim((string)$c->getEmail());
    $mailHref = '';
    if ($email !== '') {
        $mailHref = $mailEnabled
            ? $url->linkTo('mail', 'compose') . '?uri=' . rawurlencode('mailto:' . $email)
            : 'mailto:' . $email;
    }
?>
<article class="erp-record-card erp-customer-card" data-letter="<?php p($alpha); ?>" data-search="<?php p($search); ?>">
<div class="erp-record-card-head"><div><span class="erp-record-kicker">Kunde <?php p($c->getCustomerNo()); ?></span><h2><a href="<?php p($url->linkToRoute('reinhardterp.page.customerDetail',['id'=>$c->getId()])); ?>"><?php p($name); ?></a></h2></div><span class="erp-avatar-placeholder"><?php p($initial); ?></span></div>
<div class="erp-record-card-body"><dl class="erp-data-list">
<div><dt>Ansprechpartner</dt><dd><?php p($c->getContactName() ?: '—'); ?></dd></div>
<div><dt>Telefon</dt><dd><?php if ($c->getPhone()): ?><a href="tel:<?php p($c->getPhone()); ?>"><?php p($c->getPhone()); ?></a><?php else: ?>—<?php endif; ?></dd></div>
<div><dt>Mobil</dt><dd><?php if ($c->getMobile()): ?><a href="tel:<?php p($c->getMobile()); ?>">📱 <?php p($c->getMobile()); ?></a><?php else: ?>—<?php endif; ?></dd></div>
<div><dt>E-Mail</dt><dd><?php if ($email): ?><a class="erp-mail-link" href="<?php p($mailHref); ?>"<?php if ($mailEnabled): ?> title="In Nextcloud Mail schreiben"<?php endif; ?>>✉ <?php p($email); ?></a><?php else: ?>—<?php endif; ?></dd></div>
<div><dt>Nextcloud</dt><dd><?php if ($c->getNcContactId()): ?><span class="erp-sync-chip">✓ verbunden</span><?php else: ?>nicht verbunden<?php endif; ?></dd></div>
</dl></div>
<div class="erp-record-card-actions">
<a class="button primary" href="<?php p($url->linkToRoute('reinhardterp.page.customerDetail',['id'=>$c->getId()])); ?>">Kundenakte öffnen</a>
<?php if ($email): ?><a class="button" href="<?php p($mailHref); ?>"<?php if ($mailEnabled): ?> title="Neue Nachricht in Nextcloud Mail"<?php endif; ?>>✉ Mail</a><?php endif; ?>
<a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.customerForm',['id'=>$c->getId()])); ?>">Bearbeiten</a>
<form method="post" action="<?php p($url->linkToRoute('reinhardterp.customer.archive',['id'=>$c->getId()])); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button">Archivieren</button></form>
</div>
</article>
<?php endforeach; ?>
</div>
<div id="erpCustomerEmptyFilter" class="erp-card erp-empty" hidden>Keine Kunden für diesen Filter gefunden.</div>
<?php endif; ?>
</div></div>
