<?php
require __DIR__ . '/_nav.php';
?>
<div id="app-content">
	<div id="app-content-wrapper">
		<div class="erp-page erp-settings-page">
			<div class="erp-head">
				<div>
					<h1>Einstellungen</h1><p><a class="button" href="<?=p(\OC::$server->getURLGenerator()->linkToRoute('reinhardterp.page.setupWizard'))?>">Ersteinrichtungsassistent öffnen</a></p>
					<p class="erp-sub">Firmenangaben, Logo und Ausgabe der Rapporte</p>
				</div>
                <div class="erp-actions"><a class="button" href="<?php p($url->linkToRoute('reinhardterp.systemCheck.index')); ?>">Systemprüfung</a></div>
			</div>

			<section class="erp-card erp-wide">
    <h2>Firmendaten & Briefkopf</h2>
    <p>Diese Angaben werden zentral in NextERP gespeichert und automatisch auf Rapporten und Rapport-PDFs verwendet.</p>
    <form class="erp-settings-form" method="post" action="<?php p($url->linkToRoute('reinhardterp.module.saveSettings')); ?>" enctype="multipart/form-data">
        <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
        <?php $company = $_['company'] ?? []; ?>
        <div class="erp-form-grid">
            <div><label>Firmenname *</label><input name="company_name" value="<?php p($company['name'] ?? ''); ?>" placeholder="Muster Schreinerei GmbH" required></div>
            <div><label>Inhaber / Geschäftsführung</label><input name="company_owner" value="<?php p($company['owner'] ?? ''); ?>" placeholder="Max Mustermann"></div>
            <div><label>Straße / Hausnummer</label><input name="company_street" value="<?php p($company['street'] ?? ''); ?>" placeholder="Musterstraße 1"></div>
            <div><label>PLZ</label><input name="company_zip" value="<?php p($company['zip'] ?? ''); ?>" placeholder="34100"></div>
            <div><label>Ort</label><input name="company_city" value="<?php p($company['city'] ?? ''); ?>" placeholder="Kassel"></div>
            <div><label>Land</label><input name="company_country" value="<?php p($company['country'] ?? 'Deutschland'); ?>" placeholder="Deutschland"></div>
            <div><label>Telefon</label><input name="company_phone" value="<?php p($company['phone'] ?? ''); ?>"></div>
            <div><label>E-Mail</label><input type="email" name="company_email" value="<?php p($company['email'] ?? ''); ?>"></div>
            <div><label>Website</label><input name="company_website" value="<?php p($company['website'] ?? ''); ?>" placeholder="www.example.de"></div>
            <div><label>Steuernummer</label><input name="company_taxNo" value="<?php p($company['taxNo'] ?? ''); ?>"></div>
            <div><label>USt-IdNr.</label><input name="company_vatId" value="<?php p($company['vatId'] ?? ''); ?>"></div>
            <div><label>Registergericht</label><input name="company_registerCourt" value="<?php p($company['registerCourt'] ?? ''); ?>"></div>
            <div><label>Registernummer</label><input name="company_registerNo" value="<?php p($company['registerNo'] ?? ''); ?>"></div>
        </div>
        <div class="erp-settings-logo-row">
            <div>
                <label for="companyLogo">Firmenlogo</label>
                <input id="companyLogo" type="file" name="companyLogo" accept="image/png,image/jpeg">
                <p class="erp-muted">PNG oder JPG, maximal 5 MB. Ein vorhandenes Logo bleibt bestehen, wenn keine neue Datei gewählt wird.</p>
            </div>
            <?php if (!empty($_['logoDataUri'])): ?><img class="erp-settings-logo-preview" src="<?php p($_['logoDataUri']); ?>" alt="Firmenlogo"><?php endif; ?>
        </div>
        <div class="erp-actions"><button class="button primary" type="submit">Firmendaten speichern</button></div>
    </form>
</section>


			<section class="erp-card erp-wide">
				<h2>Nextcloud-Kalender</h2>
				<p>Wähle den führenden Kalender für NextERP. Neue ERP-Termine werden dort gespeichert; Termine und Änderungen vom Handy werden zurück in den Teamkalender eingelesen.</p>
				<form class="erp-settings-form" method="post" action="<?php p($url->linkToRoute('reinhardterp.integration.saveCalendarSettings')); ?>">
					<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
					<label for="calendarKey">Kalender für ERP-Termine</label>
					<select id="calendarKey" name="calendarKey">
						<option value="">Keine automatische Kalenderübernahme</option>
						<?php foreach ($_['calendars'] as $calendar): ?>
						<option value="<?php p($calendar['key']); ?>" <?php if ($calendar['selected']): ?>selected<?php endif; ?> <?php if (!$calendar['writable']): ?>disabled<?php endif; ?>><?php p($calendar['name']); ?><?php if (!$calendar['writable']): ?> (schreibgeschützt)<?php endif; ?></option>
						<?php endforeach; ?>
					</select>
					<?php if (!empty($_['selectedCalendarName'])): ?><div class="erp-integration-state is-connected"><span>✓ Aktiv</span><strong><?php p($_['selectedCalendarName']); ?></strong><small>Bidirektionaler Abgleich: NextERP schreibt Termine, NextERP liest Handy- und Nextcloud-Änderungen zurück.</small></div><?php endif; ?>
					<?php if (empty($_['calendars'])): ?><div class="erp-notice">Es wurden keine Nextcloud-Kalender gefunden. Prüfe, ob die Kalender-App und der DAV-Hintergrunddienst aktiv sind.</div><?php endif; ?>
					<button class="button primary" type="submit">Kalenderauswahl speichern</button>
				</form>
			</section>

			<section class="erp-card erp-wide">
				<h2>Stundensätze</h2>
				<p>Diese Sätze werden automatisch in der Zeitauswertung verwendet. Bereits vorgemerkte oder abgerechnete Zeiten behalten ihren festgeschriebenen Satz.</p>
				<form class="erp-settings-form" method="post" action="<?php p($url->linkToRoute('reinhardterp.module.saveHourlyRate')); ?>">
					<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
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

			<section class="erp-card erp-wide">
				<h2>Über NextERP</h2>
				<p><strong>Entwickler: André Reinhardt</strong></p>
				<p>NextERP wird als praxisnahes ERP für das Handwerk entwickelt.</p>
				<div class="erp-actions">
					<a class="button" href="https://www.schreinerei-reinhardt.de" target="_blank" rel="noopener">Website</a>
					<button class="button" type="button" onclick="navigator.clipboard.writeText('andrereinhardt@kassel-net.de');this.textContent='PayPal-Adresse kopiert ✓'">☕ Buy me a Coffee · PayPal-Adresse kopieren</button>
				</div>
				<p class="erp-muted">PayPal: <code>andrereinhardt@kassel-net.de</code></p>
			</section>
		</div>
	</div>
</div>
