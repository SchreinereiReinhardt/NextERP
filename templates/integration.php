<?php
require __DIR__ . '/_nav.php';
$status = $_['status'] ?? [];
$books = $status['addressBooks'] ?? [];
$calendars = $status['calendars'] ?? [];
?>
<div id="app-content"><main class="erp-page erp-integration-page">
  <?php if (!empty($_GET['success'])): ?><div class="erp-notice is-success"><?php p((string)$_GET['success']); ?></div><?php endif; ?>
  <?php if (!empty($_GET['error'])): ?><div class="erp-notice is-error"><?php p((string)$_GET['error']); ?></div><?php endif; ?>
  <div class="erp-page-head">
    <div><span class="erp-eyebrow">NEXTCLOUD</span><h1>Integration</h1><p>Direkte Verbindung zum nativen DAV-Speicher von Contacts, Calendar und Files.</p></div>
    <div class="erp-actions">
      <form method="post" action="<?php p($url->linkToRoute('reinhardterp.integration.repair')); ?>">
        <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
        <button class="button" type="submit">Integration reparieren</button>
      </form>
      <a class="button" href="<?php p($url->linkToRoute('reinhardterp.integration.customerImport')); ?>">Kontakte prüfen</a>
      <a class="button primary" href="<?php p($url->linkToRoute('reinhardterp.module.settings')); ?>">Kalender auswählen</a>
    </div>
  </div>

  <div class="erp-stat-grid erp-integration-stats">
    <section class="erp-stat-card"><span>Contacts</span><strong><?php p((string)($status['contactCount'] ?? 0)); ?></strong><small><?php p((string)($status['addressBookCount'] ?? 0)); ?> Adressbücher</small></section>
    <section class="erp-stat-card"><span>Calendar</span><strong><?php p((string)($status['calendarCount'] ?? 0)); ?></strong><small><?php p((string)($status['selectedCalendarName'] ?: 'nicht ausgewählt')); ?></small></section>
    <section class="erp-stat-card"><span>Letzter Abgleich</span><strong class="erp-integration-value"><?php p((string)($status['lastCalendarSync'] ?: '–')); ?></strong><small><?php p((string)($status['lastCalendarError'] ?: 'kein Fehler')); ?></small></section>
  </div>

  <div class="erp-two-column erp-integration-grid">
    <section class="erp-card"><h2>Adressbücher</h2>
      <?php if (!$books): ?><p class="erp-muted">Keine Benutzer-Adressbücher gefunden.</p><?php else: ?>
      <div class="erp-simple-list"><?php foreach ($books as $book): ?><div><strong><?php p($book['name']); ?></strong><small><?php p($book['system'] ? 'Systemadressbuch' : ($book['shared'] ? 'Geteilt' : 'Persönlich')); ?></small></div><?php endforeach; ?></div>
      <?php endif; ?>
    </section>
    <section class="erp-card"><h2>Kalender</h2>
      <?php if (!$calendars): ?><p class="erp-muted">Keine Kalender gefunden.</p><?php else: ?>
      <div class="erp-simple-list"><?php foreach ($calendars as $calendar): ?><div><strong><?php p($calendar['name']); ?></strong><small><?php p($calendar['selected'] ? 'Ausgewählt' : ($calendar['writable'] ? 'Beschreibbar' : 'Schreibgeschützt')); ?></small></div><?php endforeach; ?></div>
      <?php endif; ?>
    </section>
  </div>

  <section class="erp-card"><h2>Status</h2><p>Provider: <strong><?php p((string)($status['provider'] ?? 'Native DAV')); ?></strong></p><p>Contacts: <strong><?php p(($status['contactsEnabled'] ?? false) ? 'aktiv' : 'nicht verfügbar'); ?></strong></p><p>Calendar: <strong><?php p(($status['selectedCalendarKey'] ?? '') !== '' ? 'verbunden' : 'noch nicht ausgewählt'); ?></strong></p><p>Files: <strong>nativ verbunden</strong></p></section>
</main></div>
