<?php
declare(strict_types=1);
use OCP\IURLGenerator;
use OCP\Util;
$url = $_['urlGenerator'];
Util::addStyle('reinhardterp','style');
Util::addScript('reinhardterp','setup_wizard');
$company=$_['company']??[];
?>
<div id="erp-app" class="erp-shell">
<main class="erp-main erp-setup-wizard">
<section class="erp-card erp-wide">
<div class="erp-wizard-head"><div><span class="erp-kicker">ERSTEINRICHTUNG</span><h1>Willkommen bei Betrio</h1><p>Dieser Assistent führt durch die wichtigsten Schritte für einen neuen Betrieb. Die Einstellungen können später jederzeit in der Verwaltung geändert werden.</p></div><div class="erp-wizard-badge"><?php p($_['appVersion']); ?></div></div>
<nav class="erp-wizard-steps" aria-label="Schritte der Ersteinrichtung"><a class="active" href="#wizard-company">1 Firma</a><a href="#wizard-users">2 Benutzer</a><a href="#wizard-settings">3 Grundeinstellungen</a><a href="#wizard-calendar">4 Kalender</a><a href="#wizard-mobile">5 Mobile</a><a href="#wizard-check">6 Systemprüfung</a><a href="#wizard-ready">✓ Bereit</a></nav>
</section>

<form class="erp-settings-form" method="post" action="<?=p($url->linkToRoute('reinhardterp.page.saveSetupWizard'))?>">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
<section class="erp-card erp-wide erp-wizard-section is-active" id="wizard-company" data-wizard-step="0"><h2>1. Firma</h2><p>Diese Daten erscheinen unter anderem im Briefkopf der Rapporte.</p>
<div class="erp-form-grid">
<div><label>Firmenname *</label><input name="company_name" value="<?php p($company['name']??'');?>" required></div>
<div><label>Inhaber / Geschäftsführung</label><input name="company_owner" value="<?php p($company['owner']??'');?>"></div>
<div><label>Straße / Hausnummer</label><input name="company_street" value="<?php p($company['street']??'');?>"></div>
<div><label>PLZ</label><input name="company_zip" value="<?php p($company['zip']??'');?>"></div>
<div><label>Ort</label><input name="company_city" value="<?php p($company['city']??'');?>"></div>
<div><label>Land</label><input name="company_country" value="<?php p($company['country']??'Deutschland');?>"></div>
<div><label>Telefon</label><input name="company_phone" value="<?php p($company['phone']??'');?>"></div>
<div><label>E-Mail</label><input type="email" name="company_email" value="<?php p($company['email']??'');?>"></div>
<div><label>Website</label><input name="company_website" value="<?php p($company['website']??'');?>"></div>
<div><label>USt-IdNr.</label><input name="company_vatId" value="<?php p($company['vatId']??'');?>"></div>
</div><div class="erp-wizard-actions"><button type="button" class="button primary erp-wizard-next" data-step="1">Weiter: Benutzer</button></div></section>

<section class="erp-card erp-wide erp-wizard-section" id="wizard-users" data-wizard-step="1"><h2>2. Benutzer & Rollen</h2><p>Benutzer werden weiterhin in Nextcloud angelegt. <b>Neu angelegte Benutzer müssen sich anschließend mindestens einmal mit ihrem eigenen Konto in Nextcloud anmelden.</b> Erst danach erscheinen sie in Betrio unter Benutzer & Rechte und können den vorgesehenen Rollen bzw. Betrio-Rechten zugeordnet werden.</p><div class="erp-doc-box">Empfehlung: Mindestens einen Büro-/Admin-Benutzer und einen Monteur als Testbenutzer einrichten und die Projektsicht mit beiden Konten prüfen.</div><div class="erp-wizard-actions"><button type="button" class="button erp-wizard-prev" data-step="0">Zurück</button><button type="button" class="button primary erp-wizard-next" data-step="2">Weiter: Grundeinstellungen</button></div></section>

<section class="erp-card erp-wide erp-wizard-section" id="wizard-settings" data-wizard-step="2"><h2>3. Grundeinstellungen</h2><p>Nach dem Assistenten unter <b>Verwaltung → Einstellungen</b> Nummernkreise, Stundensätze und weitere betriebliche Vorgaben kontrollieren.</p><div class="erp-wizard-actions"><button type="button" class="button erp-wizard-prev" data-step="1">Zurück</button><button type="button" class="button primary erp-wizard-next" data-step="3">Weiter: Kalender</button></div></section>

<section class="erp-card erp-wide erp-wizard-section" id="wizard-calendar" data-wizard-step="3"><h2>4. Teamkalender</h2><p>Aktuell ausgewählt: <b><?php p($_['selectedCalendarName']?:'noch kein Kalender ausgewählt');?></b>. Die Kalenderauswahl kann anschließend in den Einstellungen geändert werden.</p><div class="erp-wizard-actions"><button type="button" class="button erp-wizard-prev" data-step="2">Zurück</button><button type="button" class="button primary erp-wizard-next" data-step="4">Weiter: Mobile</button></div></section>

<section class="erp-card erp-wide erp-wizard-section" id="wizard-mobile" data-wizard-step="4"><h2>5. Mobile</h2><p>Mobile Adresse für Monteure:</p><div class="erp-codebox"><?php p($_['mobileUrl']);?></div><p>Auf dem Mobilgerät HTTPS, Kamera-/Dateiberechtigungen und die Anmeldung testen. Die nativen Apps für bekannte Stores sind als weiterer Vertriebskanal vorgesehen.</p><div class="erp-wizard-actions"><button type="button" class="button erp-wizard-prev" data-step="3">Zurück</button><button type="button" class="button primary erp-wizard-next" data-step="5">Weiter: Systemprüfung</button></div></section>

<section class="erp-card erp-wide erp-wizard-section" id="wizard-check" data-wizard-step="5"><h2>6. Systemprüfung</h2><p>Vor dem Produktivstart die Betrio-Systemprüfung öffnen und erkannte Warnungen bearbeiten. Danach einen vollständigen Test durchführen: Kunde → Projekt → Zeit/Material → Rapport → Unterschrift → PDF.</p><div class="erp-wizard-actions"><button type="button" class="button erp-wizard-prev" data-step="4">Zurück</button><button type="button" class="button primary erp-wizard-next" data-step="6">Weiter: Bereit</button></div></section>

<section class="erp-card erp-wide erp-wizard-finish erp-wizard-section" id="wizard-ready" data-wizard-step="6"><h2>Bereit für Betrio</h2><p>Mit „Einrichtung abschließen“ werden die Firmendaten gespeichert und der Assistent als abgeschlossen markiert. Alle Einstellungen bleiben später änderbar.</p>

<button class="button primary" type="submit">Einrichtung abschließen</button><div class="erp-wizard-actions"><button type="button" class="button erp-wizard-prev" data-step="5">Zurück</button></div></section>
</form>
</main></div>
