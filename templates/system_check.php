<?php
require __DIR__.'/_nav.php';
?>
<div id="app-content"><div class="erp-page erp-system-check">
    <div class="erp-head">
        <div>
            <h1>Systemprüfung</h1>
            <p class="erp-sub">Server- und NextERP-Voraussetzungen auf einen Blick</p>
        </div>
        <div class="erp-actions"><a class="button" href="<?php p($url->linkToRoute('reinhardterp.module.settings')); ?>">Zurück zu Einstellungen</a><a class="button primary" href="<?php p($url->linkToRoute('reinhardterp.systemCheck.index')); ?>">Erneut prüfen</a></div>
    </div>

    <section class="erp-card erp-wide erp-health-summary <?= !empty($_['healthy']) ? 'is-healthy' : 'has-errors' ?>">
        <strong><?= !empty($_['healthy']) ? 'Server für NextERP bereit' : 'Prüfung mit Fehlern' ?></strong>
        <span><?php p((string)$_['failed']); ?> Fehler · <?php p((string)$_['warnings']); ?> Hinweise · geprüft am <?php p((string)$_['checkedAt']); ?></span>
    </section>

    <section class="erp-card erp-wide">
        <div class="erp-check-list">
        <?php foreach ($_['checks'] as $check): ?>
            <div class="erp-check-item is-<?php p($check['status']); ?>">
                <span class="erp-check-state"><?= $check['status'] === 'ok' ? '✓' : ($check['status'] === 'warning' ? '!' : '×') ?></span>
                <div><strong><?php p($check['name']); ?></strong><p><?php p($check['message']); ?></p></div>
            </div>
        <?php endforeach; ?>
        </div>
    </section>

    <section class="erp-card erp-wide">
        <h2>Stable-Core-Regeln</h2>
        <ul class="erp-stable-rules">
            <li>Nummern werden serverseitig und innerhalb einer Datenbanktransaktion vergeben.</li>
            <li>Schreibende Aktionen bleiben durch Nextcloud-CSRF-Prüfung und ERP-Rechte geschützt.</li>
            <li>Kunden, Projekte und abgeschlossene Rapporte werden archiviert oder gesperrt statt unkontrolliert gelöscht.</li>
            <li>Dateien verbleiben in Nextcloud Files; NextERP speichert die fachliche Verknüpfung.</li>
        </ul>
    </section>
</div></div>
