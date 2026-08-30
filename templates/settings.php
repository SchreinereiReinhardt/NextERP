<?php
require __DIR__ . '/_nav.php';
?>
<div id="app-content">
	<div id="app-content-wrapper">
		<div class="erp-page erp-settings-page">
			<div class="erp-head">
				<div>
					<h1>Einstellungen</h1><p><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.setupWizard')); ?>">Ersteinrichtungsassistent öffnen</a></p>
					<p class="erp-sub">Firmenangaben, Logo und Ausgabe der Rapporte</p>
				</div>
                <div class="erp-actions"><a class="button" href="<?php p($url->linkToRoute('reinhardterp.systemCheck.index')); ?>">Systemprüfung</a></div>
			</div>

			<section class="erp-card erp-wide">
    <h2>Firmendaten & Briefkopf</h2>
    <p>Diese Angaben werden zentral in Betrio gespeichert und automatisch auf Rapporten und Rapport-PDFs verwendet.</p>
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
        <fieldset class="erp-card erp-document-settings erp-number-settings">
 <legend><strong>Nummernkreise</strong></legend>
 <p class="erp-muted erp-number-settings-intro">Bestehende Nummern bleiben unverändert. Hier legst du nur fest, wie neue Nummern weiterlaufen.</p>
 <div class="erp-number-grid erp-number-grid-head"><span>Bereich</span><span>Präfix</span><span>Jahr</span><span>Trenner</span><span>Stellen</span><span>Nächste Nummer</span><span>Vorschau</span></div>
 <?php foreach (($_['numberSettings'] ?? []) as $type => $num): ?>
 <div class="erp-number-grid">
  <strong class="erp-number-label"><?php p($num['label']); ?></strong>
  <input name="number_<?php p($type); ?>_prefix" value="<?php p($num['prefix']); ?>" maxlength="12" aria-label="Präfix <?php p($num['label']); ?>">
  <label class="erp-number-year"><input type="checkbox" name="number_<?php p($type); ?>_yearly" value="1" <?php if (!empty($num['yearly'])): ?>checked<?php endif; ?>><span>Jahr</span></label>
  <select name="number_<?php p($type); ?>_separator" aria-label="Trenner <?php p($num['label']); ?>"><option value="" <?php if (($num['separator']??'')===''): ?>selected<?php endif; ?>>kein</option><option value="-" <?php if (($num['separator']??'')==='-'): ?>selected<?php endif; ?>>-</option><option value="/" <?php if (($num['separator']??'')==='/'): ?>selected<?php endif; ?>>/</option><option value="." <?php if (($num['separator']??'')==='.'): ?>selected<?php endif; ?>>.</option></select>
  <input type="number" min="1" max="10" name="number_<?php p($type); ?>_width" value="<?php p($num['width']); ?>" aria-label="Stellen <?php p($num['label']); ?>">
  <input type="number" min="1" name="number_<?php p($type); ?>_next" value="<?php p($num['next']); ?>" aria-label="Nächste Nummer <?php p($num['label']); ?>">
  <code class="erp-number-preview"><?php p($num['preview']); ?></code>
 </div>
 <?php endforeach; ?>
 <p class="erp-muted erp-number-settings-example">Beispiel: RE + Jahr + „-“ + 4 Stellen → <code>2026-RE0128</code></p>
</fieldset>
<div class="erp-actions"><button class="button primary" type="submit">Einstellungen speichern</button></div>
    




<fieldset class="erp-card erp-document-settings">
 <legend><strong>Zahlungsbedingungen</strong></legend>
 <p class="erp-muted">Fertige Vorlagen stehen sofort zur Verfügung. Zusätzlich kannst du drei eigene Zahlungsbedingungen hinterlegen.</p>
 <?php $ps=$_['paymentSettings']??['default'=>'net14','custom'=>[]];?>
 <div class="erp-form-grid"><div><label>Standard bei neuen Rechnungen</label><select name="payment_terms_default"><?php foreach(['due'=>'Sofort ohne Abzug','net10'=>'10 Tage netto','net14'=>'14 Tage netto','net30'=>'30 Tage netto','skonto2_10_30'=>'2 % Skonto / 10 Tage, 30 Tage netto','skonto3_10_30'=>'3 % Skonto / 10 Tage, 30 Tage netto','custom1'=>'Eigene Vorlage 1','custom2'=>'Eigene Vorlage 2','custom3'=>'Eigene Vorlage 3'] as $k=>$l):?><option value="<?php p($k);?>" <?php if(($ps['default']??'net14')===$k):?>selected<?php endif;?>><?php p($l);?></option><?php endforeach;?></select></div></div>
 <?php for($pi=1;$pi<=3;$pi++):$pc=$ps['custom'][$pi]??[];?>
 <div class="erp-form-grid"><div><label>Eigene Vorlage <?php p($pi);?> – Name</label><input name="payment_custom_<?php p($pi);?>_label" value="<?php p($pc['label']??'');?>" placeholder="z. B. Stammkunde 7 Tage"></div><div><label>Zahlungsziel in Tagen</label><input type="number" min="0" max="365" name="payment_custom_<?php p($pi);?>_days" value="<?php p($pc['days']??14);?>"></div><div style="grid-column:1/-1"><label>Text auf der Rechnung</label><input name="payment_custom_<?php p($pi);?>_text" value="<?php p($pc['text']??'');?>" placeholder="Zahlbar innerhalb von ..."></div></div>
 <?php endfor;?>
</fieldset>

<fieldset class="erp-card erp-document-settings">
 <legend><strong>Geschäftsdokumente / Bankverbindungen</strong></legend>
 <p class="erp-muted">Diese Angaben werden automatisch in Angeboten, Rechnungen, Abschlags- und Schlussrechnungen, Gutschriften und Mahnungen verwendet.</p>
 <h3>Bankverbindung 1</h3>
 <div class="erp-form-grid">
  <div><label>Bank / Kreditinstitut</label><input name="company_bank1_name" value="<?php p($_['company']['bank1_name']??'');?>"></div>
  <div><label>IBAN</label><input name="company_bank1_iban" value="<?php p($_['company']['bank1_iban']??'');?>"></div>
  <div><label>BIC</label><input name="company_bank1_bic" value="<?php p($_['company']['bank1_bic']??'');?>"></div>
 </div>
 <h3>Bankverbindung 2</h3>
 <div class="erp-form-grid">
  <div><label>Bank / Kreditinstitut</label><input name="company_bank2_name" value="<?php p($_['company']['bank2_name']??'');?>"></div>
  <div><label>IBAN</label><input name="company_bank2_iban" value="<?php p($_['company']['bank2_iban']??'');?>"></div>
  <div><label>BIC</label><input name="company_bank2_bic" value="<?php p($_['company']['bank2_bic']??'');?>"></div>
 </div>
 <h3>Standardtexte</h3>
 <div><label>Einleitung Angebot</label><textarea name="company_default_offer_intro" rows="4"><?php p($_['company']['default_offer_intro']??'');?></textarea></div>
 <div><label>Schlusstext Angebot</label><textarea name="company_default_offer_outro" rows="4"><?php p($_['company']['default_offer_outro']??'');?></textarea></div>
 <div><label>Standardhinweis Rechnung / Zahlungsbedingungen</label><textarea name="company_default_invoice_note" rows="4"><?php p($_['company']['default_invoice_note']??'');?></textarea></div>
</fieldset>
<div class="erp-actions"><button class="button primary" type="submit">Einstellungen speichern</button></div>
</form>
</section>


			<section class="erp-card erp-wide">
				<h2>Nextcloud-Kalender</h2>
				<p>Wähle den führenden Kalender für Betrio. Neue ERP-Termine werden dort gespeichert; Termine und Änderungen vom Handy werden zurück in den Teamkalender eingelesen.</p>
				<form class="erp-settings-form" method="post" action="<?php p($url->linkToRoute('reinhardterp.integration.saveCalendarSettings')); ?>">
					<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
					<label for="calendarKey">Kalender für ERP-Termine</label>
					<select id="calendarKey" name="calendarKey">
						<option value="">Keine automatische Kalenderübernahme</option>
						<?php foreach ($_['calendars'] as $calendar): ?>
						<option value="<?php p($calendar['key']); ?>" <?php if ($calendar['selected']): ?>selected<?php endif; ?> <?php if (!$calendar['writable']): ?>disabled<?php endif; ?>><?php p($calendar['name']); ?><?php if (!$calendar['writable']): ?> (schreibgeschützt)<?php endif; ?></option>
						<?php endforeach; ?>
					</select>
					<?php if (!empty($_['selectedCalendarName'])): ?><div class="erp-integration-state is-connected"><span>✓ Aktiv</span><strong><?php p($_['selectedCalendarName']); ?></strong><small>Bidirektionaler Abgleich: Betrio schreibt Termine, Betrio liest Handy- und Nextcloud-Änderungen zurück.</small></div><?php endif; ?>
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
				<h2>Über Betrio</h2>
				<p><strong>Entwickler: André Reinhardt</strong></p>
				<p>Betrio wird als praxisnahes ERP für das Handwerk entwickelt.</p>
				<div class="erp-actions">
					<a class="button" href="https://www.schreinerei-reinhardt.de" target="_blank" rel="noopener">Website</a>
					<button class="button" type="button" onclick="navigator.clipboard.writeText('andrereinhardt@kassel-net.de');this.textContent='PayPal-Adresse kopiert ✓'">☕ Buy me a Coffee · PayPal-Adresse kopieren</button>
				</div>
				<p class="erp-muted">PayPal: <code>andrereinhardt@kassel-net.de</code></p>
			</section>
		</div>
	</div>
</div>
