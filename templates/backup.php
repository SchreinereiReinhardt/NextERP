<?php
/** @var array $_ */
require __DIR__ . '/_nav.php';
$u = $_['urlGenerator'];
$backups = $_['backups'] ?? [];
$settings = $_['settings'] ?? [];
$preview = $_['restorePreview'] ?? null;
?>
<div id="app-content">
	<div id="app-content-wrapper">
		<div class="erp-page erp-settings-page">
			<div class="erp-head">
				<div>
					<h1>Backup &amp; Wiederherstellung</h1>
					<p class="erp-sub">Betrio-Datenbank sichern und bei Bedarf wiederherstellen</p>
				</div>
			</div>

			<?php if (!empty($_['message'])): ?>
				<div class="erp-notice"><strong><?php p((string)$_['message']); ?></strong></div>
			<?php endif; ?>
			<?php if (!empty($_['error'])): ?>
				<div class="erp-card erp-card-warning"><strong>Fehler:</strong> <?php p((string)$_['error']); ?></div>
			<?php endif; ?>

			<section class="erp-card erp-wide">
				<h2>Manuelles Backup</h2>
				<p>Erstellt sofort eine Sicherung der Betrio-Datenbanktabellen. Andere Nextcloud-Daten und Dateien sind nicht Bestandteil dieser Sicherung.</p>
				<form method="post" action="<?php p($u->linkToRoute('reinhardterp.backup.create')); ?>">
					<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken'] ?? ''); ?>">
					<div class="erp-actions"><button class="button primary" type="submit">Backup jetzt erstellen</button></div>
				</form>
			</section>

			<section class="erp-card erp-wide">
				<h2>Automatische Backups</h2>
				<form class="erp-settings-form" method="post" action="<?php p($u->linkToRoute('reinhardterp.backup.settings')); ?>">
					<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken'] ?? ''); ?>">
					<div class="erp-form-grid">
						<div><label for="backupSchedule">Intervall</label><select id="backupSchedule" name="schedule"><option value="off" <?php if (($settings['schedule'] ?? 'off') === 'off'): ?>selected<?php endif; ?>>Aus</option><option value="daily" <?php if (($settings['schedule'] ?? '') === 'daily'): ?>selected<?php endif; ?>>Täglich</option><option value="weekly" <?php if (($settings['schedule'] ?? '') === 'weekly'): ?>selected<?php endif; ?>>Wöchentlich</option></select></div>
						<div><label for="backupRetention">Aufbewahrung</label><select id="backupRetention" name="retention"><?php foreach ([7,14,30] as $n): ?><option value="<?php p((string)$n); ?>" <?php if ((int)($settings['retention'] ?? 7) === $n): ?>selected<?php endif; ?>><?php p((string)$n); ?> automatische Backups</option><?php endforeach; ?></select></div>
					</div>
					<?php if (!empty($settings['last'])): ?><p class="erp-muted">Letztes automatisches Backup: <?php p((string)$settings['last']); ?></p><?php endif; ?>
					<div class="erp-actions"><button class="button primary" type="submit">Einstellungen speichern</button></div>
				</form>
			</section>

			<section class="erp-card erp-wide">
				<h2>Vorhandene Backups</h2>
				<?php if (!$backups): ?>
					<p class="erp-muted">Noch keine Backups vorhanden.</p>
				<?php else: ?>
					<div class="erp-table"><table><thead><tr><th>Datei</th><th>Erstellt</th><th>Größe</th><th>Aktionen</th></tr></thead><tbody>
					<?php foreach ($backups as $b): ?><tr>
						<td><?php p((string)$b['name']); ?></td>
						<td><?php p(date('d.m.Y H:i', (int)$b['mtime'])); ?></td>
						<td><?php p(number_format(((int)$b['size']) / 1024, 1, ',', '.').' KB'); ?></td>
						<td><div class="erp-actions"><a class="button" href="<?php p($u->linkToRoute('reinhardterp.backup.download', ['name'=>$b['name']])); ?>">Herunterladen</a><form method="post" action="<?php p($u->linkToRoute('reinhardterp.backup.delete', ['name'=>$b['name']])); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken'] ?? ''); ?>"><button class="button" type="submit" onclick="return confirm('Backup wirklich löschen?')">Löschen</button></form></div></td>
					</tr><?php endforeach; ?>
					</tbody></table></div>
				<?php endif; ?>
			</section>

			<section class="erp-card erp-wide erp-card-warning">
				<h2>Backup wiederherstellen</h2>
				<p><strong>Achtung:</strong> Ein Restore ersetzt die aktuellen Betrio-Daten. Vor der Wiederherstellung erstellt Betrio automatisch ein separates Sicherheitsbackup.</p>
				<p class="erp-muted">Schritt 1 prüft die Datei ausschließlich. Dabei werden keine Daten verändert.</p>
				<form class="erp-settings-form" method="post" enctype="multipart/form-data" action="<?php p($u->linkToRoute('reinhardterp.backup.inspect')); ?>">
					<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken'] ?? ''); ?>">
					<div><label for="backupFile">Betrio-Backup auswählen</label><input id="backupFile" type="file" name="backup" accept=".json,.gz" required></div>
					<div class="erp-actions"><button class="button primary" type="submit">Backup prüfen</button></div>
				</form>

				<?php if (is_array($preview) && !empty($preview['manifest'])): $m=$preview['manifest']; ?>
					<hr>
					<h3>Prüfung erfolgreich</h3>
					<div class="erp-table"><table><tbody>
						<tr><th>Datei</th><td><?php p((string)($preview['name'] ?? '')); ?></td></tr>
						<tr><th>Erstellt</th><td><?php p((string)($m['created_at'] ?? '')); ?></td></tr>
						<tr><th>Betrio-Version</th><td><?php p((string)($m['version'] ?? '')); ?></td></tr>
						<tr><th>Backup-Format</th><td><?php p((string)($m['format'] ?? '')); ?></td></tr>
						<tr><th>Tabellen</th><td><?php p((string)($m['actual_tables'] ?? $m['tables'] ?? '')); ?></td></tr>
						<tr><th>Datensätze</th><td><?php p((string)($m['actual_rows'] ?? $m['rows'] ?? '')); ?></td></tr>
					</tbody></table></div>
					<p><strong>Die Datei ist für die aktuell installierte Betrio-Version freigegeben.</strong> Erst der folgende Schritt verändert Daten.</p>
					<form class="erp-settings-form" method="post" action="<?php p($u->linkToRoute('reinhardterp.backup.restore')); ?>">
						<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken'] ?? ''); ?>">
						<input type="hidden" name="token" value="<?php p((string)($preview['token'] ?? '')); ?>">
						<label><input type="checkbox" name="confirm" value="RESTORE" required> Ich bestätige die vollständige Wiederherstellung der Betrio-Daten.</label>
						<div class="erp-actions"><button class="button" type="submit" onclick="return confirm('Betrio wirklich auf diesen geprüften Sicherungsstand zurücksetzen?')">Wiederherstellung starten</button></div>
					</form>
				<?php endif; ?>
			</section>
		</div>
	</div>
</div>
