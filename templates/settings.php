<?php
require __DIR__ . '/_nav.php';
?>
<div id="app-content">
	<div id="app-content-wrapper">
		<div class="erp-page erp-settings-page">
			<div class="erp-head">
				<div>
					<h1>Einstellungen</h1>
					<p class="erp-sub">Firmenangaben, Logo und Ausgabe der Rapporte</p>
				</div>
			</div>

			<section class="erp-card erp-wide">
				<h2>Firmenlogo für Rapporte</h2>
				<p>Das Logo erscheint oben rechts in der Druckansicht, der HTML-Datei und der PDF. Empfohlen ist eine PNG-Datei mit transparentem oder weißem Hintergrund.</p>

				<?php if (!empty($_['logoDataUri'])): ?>
					<div class="erp-logo-preview">
						<img src="<?php p($_['logoDataUri']); ?>" alt="Gespeichertes Firmenlogo">
					</div>
					<p class="erp-muted">Gespeichert unter <code><?php p($_['logoPath']); ?></code></p>
				<?php else: ?>
					<div class="erp-notice">Noch kein Firmenlogo hinterlegt.</div>
				<?php endif; ?>

				<form class="erp-settings-form" method="post" action="<?php p($url->linkToRoute('reinhardterp.module.saveSettings')); ?>" enctype="multipart/form-data">
					<input type="hidden" name="requesttoken" value="<?php p($requestToken); ?>">
					<label for="companyLogo">Logo auswählen</label>
					<input id="companyLogo" type="file" name="companyLogo" accept="image/png,image/jpeg" required>
					<p class="erp-muted">PNG oder JPG, maximal 5 MB.</p>
					<button class="button primary" type="submit">Firmenlogo speichern</button>
				</form>
			</section>


			<section class="erp-card erp-wide">
				<h2>Stundensätze</h2>
				<p>Diese Sätze werden automatisch in der Zeitauswertung verwendet. Bereits vorgemerkte oder abgerechnete Zeiten behalten ihren festgeschriebenen Satz.</p>
				<form class="erp-settings-form" method="post" action="<?php p($url->linkToRoute('reinhardterp.module.saveHourlyRate')); ?>">
					<input type="hidden" name="requesttoken" value="<?php p($requestToken); ?>">
					<div class="erp-form-grid">
						<div><label>Bezeichnung</label><input name="name" required placeholder="z. B. Monteur"></div>
						<div><label>Kürzel</label><input name="code" required placeholder="MONTEUR"></div>
						<div><label>Verrechnungssatz netto</label><input type="number" name="salesRate" min="0" step="0.01" required placeholder="68,00"></div>
						<div><label>Interner Kostensatz</label><input type="number" name="costRate" min="0" step="0.01" placeholder="31,00"></div>
						<div><label>Gültig ab</label><input type="date" name="validFrom"></div>
						<div><label>Status</label><select name="active"><option value="1">Aktiv</option><option value="0">Inaktiv</option></select></div>
					</div>
					<div class="erp-actions"><button class="button primary" type="submit">Stundensatz anlegen</button></div>
				</form>
				<div class="erp-table"><table><thead><tr><th>Bezeichnung</th><th>Kürzel</th><th>Verrechnung</th><th>Interne Kosten</th><th>Gültig ab</th><th>Status</th></tr></thead><tbody>
				<?php foreach ($_['hourlyRates'] as $rate): ?><tr><td><strong><?php p($rate['name']); ?></strong></td><td><code><?php p($rate['code']); ?></code></td><td><?php p(number_format((float)$rate['sales_rate'], 2, ',', '.')); ?> €</td><td><?php p($rate['cost_rate'] !== null ? number_format((float)$rate['cost_rate'], 2, ',', '.').' €' : '—'); ?></td><td><?php p($rate['valid_from'] ? date('d.m.Y', strtotime((string)$rate['valid_from'])) : 'sofort'); ?></td><td><span class="erp-badge"><?php p(!empty($rate['active']) ? 'Aktiv' : 'Inaktiv'); ?></span></td></tr><?php endforeach; ?>
				<?php if (empty($_['hourlyRates'])): ?><tr><td colspan="6" class="erp-empty">Noch keine Stundensätze hinterlegt.</td></tr><?php endif; ?>
				</tbody></table></div>
			</section>

			<section class="erp-card erp-wide">
				<h2>Dateiablage</h2>
				<p>Kunden- und Projektordner werden im persönlichen Nextcloud-Dateibereich des jeweiligen Benutzers unter <code>ERP/Kunden</code> erzeugt.</p>
				<p>Angemeldeter Benutzer: <strong class="erp-inline-strong"><?php p($_['uid']); ?></strong></p>
			</section>

			<section class="erp-card erp-wide">
				<h2>Rapportgestaltung</h2>
				<p>Rapporte werden neutral in Schwarz, Weiß und Grautönen ausgegeben. Das hinterlegte Firmenlogo wird automatisch in neue und bestehende Rapportdateien übernommen.</p>
			</section>
		</div>
	</div>
</div>
