<?php require __DIR__.'/_nav.php'; ?>
<?php
$url=$_['urlGenerator'];
$mobilePath=$url->linkToRoute('reinhardterp.business.mobile');
$base=rtrim($url->getBaseUrl(),'/');
$mobileUrl=$base.$mobilePath;
?>
<style>
.ma{max-width:1100px;margin:0 auto;padding:28px}.ma h1{color:#0b1f55;margin-bottom:6px}.ma-lead{color:#64748b;margin-bottom:24px}.ma-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.ma-card{background:#fff;border:1px solid #dfe6ef;border-radius:18px;padding:22px}.ma-card.wide{grid-column:1/-1}.ma-card h2{margin:0 0 10px;color:#172554;font-size:19px}.ma-url{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.ma-url code{flex:1;min-width:260px;padding:12px;border-radius:10px;background:#f5f7fa;overflow:auto}.ma-btn{display:inline-block;border:0;border-radius:10px;padding:10px 14px;background:#1265d8;color:#fff!important;text-decoration:none;cursor:pointer;font-weight:700}.ma-btn.alt{background:#eef5ff;color:#1265d8!important}.ma-status{display:inline-block;padding:5px 9px;border-radius:999px;background:#eaf7ed;color:#237333;font-weight:800;font-size:12px}.ma-soon{display:flex;justify-content:space-between;padding:13px 0;border-bottom:1px solid #edf0f4}.ma-soon:last-child{border:0}.ma-muted{color:#64748b}.ma-list{padding-left:20px;color:#475569;line-height:1.65}.ma-qr{width:180px;height:180px;border:1px solid #dfe6ef;border-radius:14px;padding:10px;background:#fff}.ma-note{padding:13px;border-radius:11px;background:#fff7e5;border-left:4px solid #d38a00}@media(max-width:800px){.ma{padding:16px}.ma-grid{grid-template-columns:1fr}.ma-card.wide{grid-column:auto}}
</style>
<div id="app-content"><main class="ma">
<h1>NextERP Mobile</h1><p class="ma-lead">Mobile-Zugang für Monteure, Smartphone und Tablet verwalten.</p>
<div class="ma-grid">
<section class="ma-card wide"><h2>Mobile-Oberfläche</h2><p><span class="ma-status">Verfügbar</span></p><div class="ma-url"><code id="mobileUrl"><?php p($mobileUrl); ?></code><button class="ma-btn alt" id="copyMobile">URL kopieren</button><a class="ma-btn" href="<?php p($mobilePath); ?>" target="_blank" rel="noopener">Mobile öffnen</a></div><p class="ma-muted">Diese Adresse kann direkt an Mitarbeiter weitergegeben werden. Anmeldung erfolgt mit dem jeweiligen Nextcloud-Benutzerkonto.</p></section>
<section class="ma-card"><h2>QR-Code für Monteure</h2><div class="ma-qr" style="display:flex;align-items:center;justify-content:center;text-align:center;color:#64748b"><span><b>QR-Code</b><br><small>Vorbereitet für Store-/QR-Modul</small></span></div><p class="ma-muted">Die Mobile-URL kann bereits direkt kopiert und an Mitarbeiter gesendet werden. Ein scanbarer QR-Code kann später ohne Änderung des Mobile-Pfads ergänzt werden.</p></section>
<section class="ma-card"><h2>Als App installieren</h2><p><b>Android:</b> Mobile-Seite in Chrome öffnen → Menü ⋮ → <b>App installieren</b>.</p><p><b>iPhone/iPad:</b> Mobile-Seite in Safari öffnen → Teilen → <b>Zum Home-Bildschirm</b>.</p><p class="ma-muted">Die PWA startet anschließend wie eine eigenständige App.</p></section>
<section class="ma-card"><h2>App-Stores</h2><div class="ma-soon"><b>Google Play</b><span class="ma-muted">Demnächst verfügbar</span></div><div class="ma-soon"><b>Apple App Store</b><span class="ma-muted">Demnächst verfügbar</span></div><p class="ma-muted">Offizielle Store-Links werden hier ergänzt, sobald die jeweilige Version veröffentlicht ist.</p></section>
<section class="ma-card"><h2>Offline & Synchronisierung</h2><ul class="ma-list"><li>bereits geladene Mobile-Seiten offline verfügbar</li><li>Offline-Zeiterfassung</li><li>Offline-Materialentnahmen</li><li>automatischer Sync nach Wiederherstellung der Verbindung</li><li>sichtbare Warteschlange und manueller Sync</li></ul></section>
<section class="ma-card"><h2>Kamera & Barcode</h2><p>Für Projektfotos und Barcode-Erfassung benötigt der Browser Zugriff auf die Kamera. HTTPS ist für die Mobile-Funktionen erforderlich.</p><div class="ma-note">Bei Kameraproblemen zuerst die Website-Berechtigung des Browsers kontrollieren.</div></section>
<section class="ma-card"><h2>Monteur vorbereiten</h2><ol class="ma-list"><li>Eigenes Nextcloud-Benutzerkonto anlegen.</li><li>Passende Mitarbeiter-/Monteur-Gruppe zuweisen.</li><li>NextERP-Rechte kontrollieren.</li><li>Benötigte Projekte freigeben.</li><li>Mobile-Link oder QR-Code an den Mitarbeiter geben.</li></ol><a class="ma-btn alt" href="<?php p($url->linkToRoute('reinhardterp.business.documentation')); ?>#users">Anleitung öffnen</a></section>
<section class="ma-card"><h2>Sicherheit</h2><p>Jeder Monteur sollte ein eigenes Konto verwenden. Bei verlorenem Gerät den Benutzerzugriff bzw. aktive Sitzungen in Nextcloud unverzüglich prüfen und sperren.</p><p class="ma-muted">Offline gespeicherte Daten sind ein zusätzlicher Grund für Gerätesperre/PIN.</p></section>
<section class="ma-card wide"><h2>Systemstatus</h2><p><b>NextERP:</b> Version 1.5.1 &nbsp; · &nbsp; <b>Mobile PWA:</b> aktiv &nbsp; · &nbsp; <b>Offline-Basis:</b> aktiv &nbsp; · &nbsp; <b>Zeit-/Material-Sync:</b> aktiv</p><p class="ma-muted">Weitere technische Prüfungen finden Sie unter Verwaltung → Systemprüfung.</p></section>
</div></main></div>
<script>
(function(){
const u=document.getElementById('mobileUrl').textContent;
document.getElementById('copyMobile').onclick=async function(){try{await navigator.clipboard.writeText(u);this.textContent='Kopiert ✓';setTimeout(()=>this.textContent='URL kopieren',1600)}catch(e){window.prompt('Mobile-URL kopieren:',u)}};

})();
</script>
