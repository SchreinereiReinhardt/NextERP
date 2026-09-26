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
<div id="app-content"><div class="erp-page erp-list-page erp-customers-premium">
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

<div class="erp-customer-list" id="erpCustomerGrid">
<div class="erp-customer-list-head" aria-hidden="true">
    <span>Kunde</span><span>Ansprechpartner</span><span>Kontakt</span><span>Nextcloud</span><span></span>
</div>
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
    $detailHref = $url->linkToRoute('reinhardterp.page.customerDetail',['id'=>$c->getId()]);
?>
<article class="erp-customer-row erp-customer-card" data-letter="<?php p($alpha); ?>" data-search="<?php p($search); ?>">
    <a class="erp-customer-row-main" href="<?php p($detailHref); ?>" aria-label="Kundenakte <?php p($name); ?> öffnen">
        <span class="erp-customer-ident"><span class="erp-customer-mini-avatar"><?php p($initial); ?></span><span><small>Kunde <?php p($c->getCustomerNo()); ?></small><strong><?php p($name); ?></strong></span></span>
        <span class="erp-customer-contact"><small>Ansprechpartner</small><strong><?php p($c->getContactName() ?: '—'); ?></strong></span>
        <span class="erp-customer-contact"><small>Kontakt</small><strong><?php p($c->getPhone() ?: ($c->getMobile() ?: '—')); ?></strong><?php if ($email): ?><em><?php p($email); ?></em><?php endif; ?></span>
        <span class="erp-customer-nc"><?php if ($c->getNcContactId()): ?><span class="erp-sync-chip">✓ verbunden</span><?php else: ?><span class="erp-muted-chip">nicht verbunden</span><?php endif; ?></span>
    </a>
    <div class="erp-customer-row-actions">
        <a class="button primary" href="<?php p($detailHref); ?>">Öffnen</a>
        <details class="erp-row-more">
            <summary class="button" aria-label="Weitere Aktionen">Mehr</summary>
            <div class="erp-row-more-menu">
                <a href="<?php p($url->linkToRoute('reinhardterp.page.customerForm',['id'=>$c->getId()])); ?>">Bearbeiten</a>
                <?php if ($email): ?><a href="<?php p($mailHref); ?>">Mail schreiben</a><?php endif; ?>
                <?php if ($c->getPhone()): ?><a href="tel:<?php p($c->getPhone()); ?>">Anrufen</a><?php elseif ($c->getMobile()): ?><a href="tel:<?php p($c->getMobile()); ?>">Anrufen</a><?php endif; ?>
                <form method="post" action="<?php p($url->linkToRoute('reinhardterp.customer.archive',['id'=>$c->getId()])); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button type="submit">Archivieren</button></form>
            </div>
        </details>
    </div>
</article>
<?php endforeach; ?>
</div>
<div id="erpCustomerEmptyFilter" class="erp-card erp-empty" hidden>Keine Kunden für diesen Filter gefunden.</div>
<?php endif; ?>
</div></div>
