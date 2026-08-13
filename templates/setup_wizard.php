<?php
declare(strict_types=1);
use OCP\Util;
Util::addStyle('reinhardterp','style');
$company=$_['company']??[];
?>
<div id="erp-app" class="erp-shell">
<main class="erp-main erp-setup-wizard">
<section class="erp-card erp-wide">
<div class="erp-wizard-head"><div><span class="erp-kicker">ERSTEINRICHTUNG</span><h1>Willkommen bei NextERP</h1><p>Dieser Assistent führt durch die wichtigsten Schritte für einen neuen Betrieb. Die Einstellungen können später jederzeit in der Verwaltung geändert werden.</p></div><div class="erp-wizard-badge">1.5.1</div></div>
<div class="erp-wizard-steps"><span class="active">1 Firma</span><span>2 Benutzer</span><span>3 Grundeinstellungen</span><span>4 Kalender</span><span>5 Mobile</span><span>6 Systemprüfung</span><span>✓ Bereit</span></div>
</section>

<form class="erp-settings-form" method="post" action="<?=p(\OC::$server->getURLGenerator()->linkToRoute('reinhardterp.page.saveSetupWizard'))?>">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
<section class="erp-card erp-wide"><h2>1. Firma</h2><p>Diese Daten erscheinen unter anderem im Briefkopf der Rapporte.</p>
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
</div></section>

<section class="erp-card erp-wide"><h2>2. Benutzer & Rollen</h2><p>Benutzer werden weiterhin in Nextcloud angelegt. Danach werden Büro, Administratoren und Monteure den vorgesehenen Gruppen bzw. NextERP-Rechten zugeordnet.</p><div class="erp-doc-box">Empfehlung: Mindestens einen Büro-/Admin-Benutzer und einen Monteur als Testbenutzer einrichten und die Projektsicht mit beiden Konten prüfen.</div></section>

<section class="erp-card erp-wide"><h2>3. Grundeinstellungen</h2><p>Nach dem Assistenten unter <b>Verwaltung → Einstellungen</b> Nummernkreise, Stundensätze und weitere betriebliche Vorgaben kontrollieren.</p></section>

<section class="erp-card erp-wide"><h2>4. Teamkalender</h2><p>Aktuell ausgewählt: <b><?php p($_['selectedCalendarName']?:'noch kein Kalender ausgewählt');?></b>. Die Kalenderauswahl kann anschließend in den Einstellungen geändert werden.</p></section>

<section class="erp-card erp-wide"><h2>5. Mobile</h2><p>Mobile Adresse für Monteure:</p><div class="erp-codebox"><?php p($_['mobileUrl']);?></div><p>Auf dem Mobilgerät HTTPS, Kamera-/Dateiberechtigungen und die Anmeldung testen. Die nativen Apps für bekannte Stores sind als weiterer Vertriebskanal vorgesehen.</p></section>

<section class="erp-card erp-wide"><h2>6. Systemprüfung</h2><p>Vor dem Produktivstart die NextERP-Systemprüfung öffnen und erkannte Warnungen bearbeiten. Danach einen vollständigen Test durchführen: Kunde → Projekt → Zeit/Material → Rapport → Unterschrift → PDF.</p></section>

<section class="erp-card erp-wide erp-wizard-finish"><h2>Bereit für NextERP</h2><p>Mit „Einrichtung abschließen“ werden die Firmendaten gespeichert und der Assistent als abgeschlossen markiert. Alle Einstellungen bleiben später änderbar.</p><button class="button primary" type="submit">Einrichtung abschließen</button></section>
</form>
</main></div>
