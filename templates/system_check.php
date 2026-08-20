<?php
require __DIR__.'/_nav.php';
?>
<div id="app-content"><div class="erp-page erp-system-check">
    <div class="erp-head">
        <div>
            <h1>Systemprüfung</h1>
            <p class="erp-sub">Server- und Betrio-Voraussetzungen auf einen Blick</p>
        </div>
        <div class="erp-actions"><a class="button" href="<?php p($url->linkToRoute('reinhardterp.module.settings')); ?>">Zurück zu Einstellungen</a><a class="button" href="<?php p($url->linkToRoute('reinhardterp.systemCheck.diagnostics')); ?>">Diagnosebericht herunterladen</a><a class="button primary" href="<?php p($url->linkToRoute('reinhardterp.systemCheck.index')); ?>">Erneut prüfen</a></div>
    </div>

    <section class="erp-card erp-wide erp-health-summary <?= !empty($_['healthy']) ? 'is-healthy' : 'has-errors' ?>">
        <strong><?= !empty($_['healthy']) ? 'Server für Betrio bereit' : 'Prüfung mit Fehlern' ?></strong>
        <span><?php p((string)$_['failed']); ?> Fehler · <?php p((string)$_['warnings']); ?> Hinweise · geprüft am <?php p((string)$_['checkedAt']); ?></span>
    </section>

    <section class="erp-card erp-wide">
        <div class="erp-check-list">
        <?php foreach ($_['checks'] as $check): ?>
            <div class="erp-check-item is-<?php p($check['status']); ?>">
                <span class="erp-check-state"><?= $check['status'] === 'ok' ? '✓' : ($check['status'] === 'warning' ? '!' : '×') ?></span>
                <div>
                    <strong><?php p($check['name']); ?></strong>
                    <p><?php p($check['message']); ?></p>
                    <?php if ($check['status'] !== 'ok' && !empty($check['recommendation'])): ?>
                        <div class="erp-check-solution"><strong>Lösungsvorschlag:</strong> <?php p((string)$check['recommendation']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </section>

    <section class="erp-card erp-wide">
        <h2>Hinweise für Support & Rollout</h2>
        <p>Bei Problemen zuerst die Systemprüfung erneut ausführen. Der Diagnosebericht kann anschließend an den Support weitergegeben werden.</p>
        <ul class="erp-stable-rules">
            <li>Der Diagnosebericht enthält technische Versions- und Prüfinformationen, aber keine Kunden-, Projekt- oder Dateiinhalte.</li>
            <li>Passwörter, mobile Tokens und andere Zugangsdaten werden nicht in den Bericht aufgenommen.</li>
            <li>Vor Updates und Reparaturen sollte ein aktuelles Nextcloud-Backup vorhanden sein.</li>
            <li>Fehler und Warnungen möglichst zuerst anhand des eingeblendeten Lösungsvorschlags beheben.</li>
        </ul>
    </section>
</div></div>
