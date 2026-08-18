## 1.9.11 – Mobile Projektakten Explorer

- Mobile Dokumentansicht nicht mehr als flache, durcheinander wirkende Dateiliste.
- Vorhandene NextERP-Projektpfade werden als echte Ordnerstruktur dargestellt.
- Ordner antippen öffnet dessen Dateien und Unterordner.
- Mobile Zurück-Navigation und Breadcrumbs ergänzt.
- Ordner zeigen die Anzahl der enthaltenen Dateien.
- Natürliche Sortierung für Ordner und Dateinamen.
- Bestehende Berechtigungs-/Freigabelogik und Datei-Links bleiben erhalten.
- Keine Änderung an der funktionierenden Kamera aus 1.9.10.

## 1.9.10 – Kamera Auslöser UX Fix

- Funktionierende Kamera-Technik aus 1.9.9 unverändert.
- Großer, kontrastreicher Kamera-Auslöser im Stil einer nativen Kamera-App.
- Beschriftung „Foto aufnehmen“ und deutlich größeres Touch-Ziel.
- Kurzes visuelles Touch-Feedback.

## 1.9.9 – Kamera CSP / JavaScript Fix

- Ursache für den nicht reagierenden Kamera-Button behoben.
- Kamera-JavaScript aus dem Inline-Template in `js/mobile-camera.js` verschoben.
- Das Script wird nun über Nextcloud `script()` CSP-konform geladen.
- Nextcloud 34 blockiert Inline-JavaScript standardmäßig; dadurch konnte der Kamera-Handler zuvor still ausfallen.
- Live-Kamera mit `getUserMedia()`, Fotoaufnahme, Vorschau und Projekt-Upload bleiben erhalten.
- Dynamische Upload-URL und Request-Token werden sicher über Data-Attribute an das externe Script übergeben.
- Konkrete Kamera-Fehler werden weiterhin direkt in der mobilen Oberfläche angezeigt.

## 1.9.8 – Live-Kamera Projektfoto

- Kamera technisch neu aufgebaut: Live-Videostream direkt in NextERP.
- Rückkamera wird bevorzugt; kein `capture`-Kamera-Dateifeld mehr.
- Foto wird direkt aus dem Videostream als JPEG erzeugt.
- Vorschau, Neuaufnahme und Speichern direkt in der PWA.
- Kamera-Stream wird beim Abbrechen/Verlassen beendet.
- Datei-Auswahl bleibt separat erhalten.

## 1.9.7 – Kamera Auto-Speichern Fix

- Fehlerhaften Verweis auf das nicht vorhandene Element `camera-recapture` entfernt.
- Native Kamera speichert das bestätigte Foto nach dem Haken automatisch ins Projekt.
- Keine zweite fehleranfällige Kamera-Vorschau/Bestätigung mehr nötig.
- Sichtbarer Status während des Uploads und konkrete Fehlermeldung bei Fehlschlag.
- Datei-Upload bleibt separat: auswählen, Vorschau prüfen, anschließend speichern.
- Android/iOS-PWA-Workflow weiter ohne Nextcloud-Core-Änderungen.

## 1.9.6 – Android Kamera Rückgabe / Vorschau Fix

- Kamera- und Datei-Auswahl verwenden jetzt echte native File-Inputs direkt als Touch-Ziel.
- Kein synthetischer Label-/Click-Umweg beim Kamera-Start.
- Android-Kamerarückgabe wird unmittelbar aus `input.files[0]` übernommen und als Vorschau angezeigt.
- Separater nativer Input für „Neu aufnehmen“, damit die gleiche Aufnahme erneut gewählt werden kann.
- Fehlermeldung, wenn die Kamera ohne Bild zurückkehrt.
- Foto-Upload-Fix aus 1.9.5 bleibt erhalten.

## 1.9.5 – Mobile Projektfoto Speichern Fix
- Foto-Upload liefert jetzt eine klare JSON-Antwort statt eines Redirects.
- Speichern zeigt konkrete Serverfehler direkt in der mobilen Oberfläche.
- Bildtyp wird serverseitig aus der Datei geprüft, nicht nur aus Browser-Metadaten.
- Eindeutige Dateinamen verhindern Kollisionen.
- CSRF-Token wird beim Fetch zusätzlich als Header übertragen.
- Beschreibung wird zuverlässig aus dem Request übernommen.

## 1.9.4 – Native Kamera Trigger Fix

- Kamera-Button ist jetzt direkt als Label mit dem nativen `capture=environment`-Dateifeld verbunden.
- Kein synthetischer JavaScript-`click()` mehr, der auf Android/PWA blockiert werden kann.
- Datei-Auswahl bleibt separat ohne `capture`.
- „Neu aufnehmen“ öffnet wieder direkt die Systemkamera.

# NextERP 1.9.3 – Kamera Workflow Fix

- Projektfoto-Button „Kamera“ öffnet jetzt bewusst die native Android/iOS-Systemkamera über `capture=environment`.
- Kein still scheiternder `getUserMedia()`-Live-Stream mehr im Foto-Workflow.
- „Datei“ ist getrennt und verwendet eine normale Bild-/Dateiauswahl ohne `capture`.
- Nach Kamera oder Dateiauswahl erscheint dieselbe Vorschau mit Speichern bzw. „Neu aufnehmen“.
- Barcode-Scanner bleibt unverändert als Live-Kamera-Workflow mit BarcodeDetector/ZXing.
- PWA-Service-Worker-Version auf 1.9.3 angehoben.

# NextERP 1.9.2 – Mobile/PWA Start-Fix

- PWA-Service-Worker stark vereinfacht und Navigation nicht mehr gecacht/intercepted.
- Alte NextERP-PWA-Caches werden beim Aktivieren vollständig entfernt.
- Manifest-Startversion auf 1.9.2 aktualisiert.
- Manifest-Scope wird jetzt aus der echten Mobile-Route ohne Query-String erzeugt.
- ZXing-Scanner aus 1.9.1 bleibt erhalten.

# NextERP 1.9.1 – Plattformübergreifender ZXing-Scanner

- ZXing Browser 0.2.1 als automatischer Scanner-Fallback für Browser ohne BarcodeDetector vorbereitet.
- Android nutzt weiterhin bevorzugt die native BarcodeDetector-API.
- iOS/Safari kann auf den ZXing-MultiFormat-Reader ausweichen.
- Kamera, Taschenlampe, Scan-Treffer und Materialsuche bleiben in derselben mobilen Ansicht.
- Service-Worker-Cache auf v1.9.1 angehoben.

# NextERP 1.9.0 – Mobile Kamera & Scanner

- Kamera für Projektfotos als hochwertige In-App-Kamera mit Live-Vorschau umgesetzt.
- Rückkamera wird auf Android und iOS bevorzugt; klassische Dateiauswahl bleibt als Fallback erhalten.
- Foto-Vorschau vor dem Upload, Wiederholen und direktes Speichern in `07_Fotos`.
- Barcode-Scanner als Vollbild-Kamera mit Suchrahmen, Statusanzeige und sauberem Kamera-Lifecycle überarbeitet.
- Native `BarcodeDetector`-Erkennung für unterstützte Browser; EAN-13, EAN-8, Code 128, Code 39, UPC und QR.
- Taschenlampe wird angeboten, wenn Browser und Kamera sie unterstützen.
- iOS/Browser ohne BarcodeDetector erhalten einen klaren Kamera-/Foto-Fallback und manuelle Barcode-Suche statt eines defekten Scanners.
- Kamera wird beim Seitenwechsel und beim Wechsel in den Hintergrund zuverlässig beendet.
- HTTPS/Secure Context wird für Live-Kamera und Scanner geprüft und verständlich erklärt.
- Service-Worker-Cache auf v1.9.0 angehoben.

# NextERP 1.8.3 – Mobile Navigation konsistent

- Ursache des springenden Bottom-Menüs behoben: mobile Seiten luden die Stylesheets in unterschiedlicher Reihenfolge.
- `style.css` wird nun auf allen Mobile-Routen zuerst geladen, `mobile.css` danach als verbindliche Mobile-UX-Schicht.
- Alte 7-Spalten-/9px-Navigationsregeln aus dem historischen Mobile-CSS neutralisiert.
- Bottom-Navigation hat auf Heute, Projekte, Zeit, Scanner und Mehr identische Geometrie.
- Beschriftungen auf 13px erhöht (12px auf sehr schmalen Displays), Icons 22px.
- Keine Größen-, Scale- oder Layoutänderung beim aktiven Menüpunkt.
- PWA-Cache auf v1.8.3 angehoben.

# NextERP 1.8.2 – Mobile Navigation Stabilität & Lesbarkeit

- Bottom-Navigation vollständig stabilisiert: keine Scale-/Tap-Animationen oder Größenänderungen mehr.
- Feste Navigationshöhe für Android und iOS inklusive Safe-Area.
- Lesbarkeit deutlich verbessert: 12 px Beschriftungen, höherer Kontrast und 24 px Icons.
- Aktiver Bereich wird über eine ruhige Fläche und einen festen Indikator markiert, ohne Layout-Verschiebung.
- Transparenz/Backdrop-Blur entfernt, um Darstellungs- und Compositing-Sprünge in mobilen Browsern zu vermeiden.
- Zusätzlicher Inhaltsabstand verhindert Überdeckung durch die Navigation.
- PWA-Cache auf v1.8.2 angehoben.

# NextERP 1.8.1 – Mobile Navigation / Native UX Fix

- Untere Mobile-Navigation vollständig auf eine ruhige, native Bottom-Bar umgestellt.
- Alle fünf Navigationspunkte besitzen jetzt identische Abmessungen; der herausstehende Zeit-Button wurde entfernt.
- Aktive Navigation wird über eine dezente, feste Hervorhebung statt Größen- oder Positionsänderung dargestellt.
- Safe-Area-Unterstützung für iPhone/iPad und Android verbessert.
- Touch-Feedback ohne Layout-Sprünge, größere und konsistente Touch-Ziele.
- Standalone-PWA erhält eine kompaktere, appartige Darstellung ohne unnötige Browser-/Nextcloud-Flächen.
- Mobile Seiten reservieren exakt den Platz der Bottom-Bar, damit Inhalte nicht verdeckt werden.
- Service-Worker-Cache auf v1.8.1 angehoben.

# NextERP 1.8.0 – Mobile UX / PWA First

- Mobile Web/PWA vollständig auf einen App-ähnlichen Arbeitsablauf optimiert.
- Einheitliche mobile 5-Punkt-Navigation: Heute, Projekte, Zeit, Scanner, Mehr.
- Zeit erfassen als zentrale Primäraktion in der unteren Navigation.
- Projektlisten führen jetzt konsequent in die mobile Projektakte statt in Desktop-Ansichten.
- Mobile Projektsuche nach Projektnummer, Titel und Status.
- Mobile Projektakte mit Schnellaktionen Zeit, Foto und Rapport.
- Installationsbereich für PWA/Startbildschirm ergänzt.
- Mobile Dokumente und Fotos bleiben beim Öffnen im gleichen Web-App-Kontext.
- Interne Nutzung von OC::$server in mobilen Templates entfernt; öffentliche URL-Generator-Daten werden verwendet.
- Mobile Styles zentralisiert und für kleine Touch-Displays, Safe Areas und lange Projektnamen optimiert.
- Bestehende Offline-/Service-Worker-Funktion bleibt erhalten.

# NextERP 1.7.0 – Nextcloud Dashboard Integration

- Drei native Nextcloud-Dashboard-Widgets: Heute, Handlungsbedarf und Projekte.
- Direkte Links aus den Widgets in NextERP.
- Rollen- und Projektberechtigungen werden berücksichtigt.
- Native Dashboard API V2 für Nextcloud 34, ohne Änderungen am Nextcloud-Core.
- Dashboard 2.0 und Typografie-Fix aus 1.6.3 bleiben erhalten.

# NextERP 1.6.3 – Dashboard 2.0 Typografie-Fix

- Zu große Schriften in den „Jetzt wichtig“-Karten korrigiert.
- Projekttitel unter „Aktuelle Projekte“ auf eine einheitliche Größe begrenzt.
- Zeilenhöhe und Umbruch langer Titel verbessert.
- Responsive Schriftgrößen für schmale Displays ergänzt.
- Keine Funktions- oder Datenbankänderungen.

# NextERP 1.6.2 – Dashboard 2.0 / Nextcloud-34-CSP-Fix

- Nextcloud-34.0.2-Kompatibilitätsfehler im Projektakten-Explorer behoben.
- Nicht verfügbare CSP-Methode `ContentSecurityPolicy::addAllowedChildSrcDomain()` entfernt.
- PDF.js bleibt per `script-src` für cdnjs freigegeben; der iframe-freie Canvas-Viewer benötigt keine `child-src`-Freigabe.
- Bestehender Nextcloud-34-IURLGenerator-Fix aus 1.6.1 bleibt erhalten.
- Keine Datenbankmigration.

# NextERP 1.6.1 – Dashboard 2.0 / Nextcloud-34-Projektakten-Fix

- Projektakten-Explorer für Nextcloud 34 angepasst.
- Direkter Zugriff des Templates auf den internen Nextcloud-Servicecontainer `\OC::$server` entfernt.
- Der bereits injizierte öffentliche `OCP\IURLGenerator` wird nun vom Controller an das Template übergeben.
- PDF.js, Drag & Drop, Dateivorschau und bestehende Dashboard-2.0-Funktionen bleiben unverändert.
- Keine Datenbankmigration.

# NextERP 1.6.0 – Dashboard 2.0

- Dashboard als zentrale Arbeitsübersicht neu aufgebaut.
- Rollenabhängige Kennzahlen und Schnellaktionen für Administration, Büro, Projektleitung und Monteure.
- Neuer Bereich „Jetzt wichtig“ für überfällige Projekte, offene Rapporte, unbearbeitete Dokumente und kritische Lagerbestände.
- Aktuelle Projekte direkt vom Dashboard erreichbar.
- Termine, Aktivitäten und Schnellzugriffe in eine klare Tagesansicht integriert.
- Stundenanzeige für Monteure auf die eigenen heutigen Zeiten begrenzt.
- Rapport-Kennzahl berücksichtigt nur Projekte, auf die der angemeldete Benutzer Zugriff hat.
- Bestehende Module, Projektakten und PDF.js-Viewer unverändert beibehalten.

# Changelog

## 1.5.14 – Vollprüfung und Berechtigungs-Fixes

- Behebt einen Laufzeitfehler beim Anlegen von Dokumentenregeln durch den bislang nicht definierten Parameter `orderId`.
- Rapporte können nur noch mit gültiger Projektberechtigung wieder geöffnet, archiviert, wiederhergestellt oder unterschrieben werden.
- Archivierte Rapporte können nicht direkt wieder geöffnet oder unterschrieben werden.
- Bereits abgeschlossene Rapporte können nicht erneut über den Signatur-Endpunkt abgeschlossen werden.
- Beim Löschen von Rapportzeiten und Materialpositionen wird jetzt geprüft, dass der Datensatz tatsächlich zum angegebenen Rapport gehört.
- PHP- und JavaScript-Syntax sowie Release-Struktur vollständig geprüft.
- Keine Datenbankmigration.

## 1.5.13 – PDF.js Projektakten-Viewer

- PDFs werden nicht mehr über Browser-PDF-Viewer oder iframe dargestellt.
- NextERP rendert PDF-Seiten mit Mozilla PDF.js direkt als Canvas in der Projektakte.
- Mehrseitige PDFs werden untereinander dargestellt und können innerhalb der Vorschau gescrollt werden.
- Bilder bleiben direkt in der Projektakte sichtbar.
- Nextcloud Frame-Sicherheitsheader blockieren die PDF-Darstellung dadurch nicht mehr.
- PDF.js ist auf Version 5.4.54 festgelegt; CSP-Freigabe gilt nur für cdnjs auf der Projekt-Explorer-Seite.
- Drag & Drop bleibt unverändert.
- Keine Datenbankmigration.

## 1.5.12 – PDF-Vorschau direkt in der Projektakte

- PDF- und Bildvorschau öffnet nicht mehr in einem Dialog oder neuen Fenster.
- Vorschau erscheint direkt unter der Dateiliste innerhalb derselben Projektakte.
- Dateien werden per Fetch geladen und als lokale Blob-URL dargestellt; damit greifen die Nextcloud Frame-Sicherheitsheader nicht gegen die Vorschau.
- „Zurück zu Dateien“ blendet die Vorschau wieder aus.
- Der direkte Neu-Tab-Fallback wurde entfernt.
- Funktionierendes Drag & Drop bleibt unverändert.
- Keine Datenbankmigration.

## 1.5.11 – PDF-Vorschau Nextcloud-33-Fix

- Korrigiert den Konstruktor von `DataDownloadResponse` für Nextcloud 33.
- HTTP-Status wird korrekt als `200` übergeben statt als boolescher Wert.
- Inline-Ausgabe für PDF/Bilder bleibt erhalten.
- Funktionierendes Projektakten-Drag-&-Drop bleibt unverändert.
- Keine Datenbankmigration.

## 1.5.10 – PDF-Vorschau Response-Fix

- Behebt den HTTP-500 beim Öffnen von PDFs im Projektakten-Explorer.
- Entfernt den ungültigen Aufruf `Response::setContent()`.
- Binärdateien werden wieder über Nextcloud `DataDownloadResponse` ausgeliefert.
- PDF/Bild-Ausgabe bleibt `inline`, damit die integrierte Vorschau funktioniert.
- Das funktionierende Drag & Drop aus 1.5.9 bleibt unverändert.
- Keine Datenbankmigration.

## 1.5.9 – Projektakten Drag & Drop CSP-Fix

- Explorer-JavaScript aus dem Template in eine eigene Nextcloud-JavaScript-Datei verschoben.
- Dadurch funktionieren Drag & Drop und PDF-Vorschau auch mit der Nextcloud Content-Security-Policy.
- Browser-Standardaktion für hineingezogene Dateien wird auf Dokument- und Fensterebene blockiert.
- Mehrfachupload lädt Dateien nacheinander in den aktuell geöffneten Projektordner.
- Keine Datenbankmigration.

## 1.5.8 – Projektakten Explorer PDF & Drag-and-Drop Fix

- PDF/Bilder ausdrücklich inline für die Vorschau.
- Öffnen im neuen Tab als Browser-Fallback.
- Datei-Drops werden browserweit abgefangen, damit PDFs beim Reinziehen nicht gleichzeitig geöffnet werden.
- Ordnergröße bleibt leer.
- Keine Datenbankmigration.

# Changelog

## 1.5.7 – Projektakten Explorer Fix

- PDF- und Bilddateien werden für die integrierte Vorschau inline ausgeliefert statt als Download erzwungen.
- Dateinamen und Ordnernamen im Explorer deutlich kompakter dargestellt.
- Große Drag-&-Drop-Fläche wie bei Belegen direkt im geöffneten Projektordner.
- Mehrere Dateien können per Drag & Drop oder Dateiauswahl nacheinander hochgeladen werden.
- Upload erfolgt direkt in den aktuell geöffneten Projektordner.
- Keine Datenbankmigration.

## 1.5.6 – Projektakten Explorer

- Projektordner lassen sich direkt innerhalb von NextERP wie in einem Dateiexplorer öffnen.
- Ordnernavigation mit Breadcrumb-Pfad.
- Ordner werden vor Dateien angezeigt.
- PDF-Dateien öffnen direkt in einer integrierten Vorschau.
- Bilder öffnen ebenfalls direkt in NextERP.
- Dateien können im aktuell geöffneten Projektordner hochgeladen werden.
- Unterordner können direkt im ERP angelegt werden.
- Projektordner-Berechtigungen der Monteure werden auch im Explorer berücksichtigt.
- Für Projektleitung bleibt zusätzlich „In Nextcloud öffnen“ verfügbar.
- Keine Datenbankmigration.

## 1.5.5 – Projekt in Rapportübersicht

- Neue eigene Spalte „Projekt“ in der Übersicht aktiver und archivierter Rapporte.
- Anzeige von Projektnummer und Projektname.
- Kunde wird ergänzend unter dem Projekt angezeigt.
- Projekt ist direkt anklickbar und öffnet die zugehörige Projektakte.
- Keine Datenbankmigration.

## 1.5.4 – Belegimport aufgeräumt

- Redundanten Bereich „PDF-Beleg importieren“ aus der Belegübersicht entfernt.
- Drag & Drop bleibt der zentrale manuelle Dokumentimport.
- Scanner/WebDAV-Ziel bleibt dauerhaft sichtbar direkt unter der Drag-&-Drop-Fläche.
- Scanner/WebDAV-Pfad ist gut lesbar und einfach markierbar.
- Keine Datenbankmigration.

## 1.5.3 – Lieferanten & intelligente Belegerkennung

- Eigene Lieferantenverwaltung unter Lager mit Adresse, Ansprechpartner, Kontakt, Kundennummer, Zahlungsziel, IBAN/BIC, Website, Notizen und Aktivstatus.
- Lieferantenübersicht zeigt verknüpfte Materialien und Belege.
- Belegfilter direkt unter Belege: Art, Jahr, Monat, Lieferant, Kunde, Projekt, Status und Suche.
- Digitale PDFs werden vollständig über alle Seiten ausgelesen.
- Bei PDFs ohne brauchbare Textschicht wird – sofern pdftoppm und Tesseract installiert sind – OCR über alle PDF-Seiten versucht.
- Bildbelege können per Tesseract erkannt werden.
- Dokumenttyp, Belegnummer, Datum sowie Kunde/Projekt/Lieferant werden zusätzlich aus dem Dokumentinhalt vorgeschlagen.
- Lieferantenerkennung nutzt bekannte Lieferanten aus dem Lieferantenstamm.
- Datenbankmigration erweitert den bestehenden Lieferantenstamm; vorhandene Lieferanten bleiben erhalten.

## 1.5.2 – Finanzfilter, Steuerbüro-Export & Dokumentablage

- Buchhaltungsbelege werden unabhängig von Projekt-/Lieferantenzuordnung immer als ein Original unter ERP/30_Finanzen abgelegt.
- Projekt, Kunde und Lieferant bleiben zusätzliche ERP-Verknüpfungen auf dasselbe Dokument.
- Finanzfilter nach Belegart, Jahr, Monat, Lieferant, Kunde, Projekt und Suche.
- ZIP-Gesamtexport für Steuerbüro mit Ordnern je Belegart und Beleguebersicht.csv.
- Mehrfach-Drag-&-Drop im Finanzbereich und Dokumenteneingang.
- PDF-Prüfung: digitale PDFs werden mit pdftotext vollständig ausgelesen; es besteht keine Beschränkung auf Seite 1.
- Keine Datenbankmigration.

## 1.5.1 – Finanzen & Belegmanagement

- Neuer eigener Bereich „Finanzen“ in der NextERP-Navigation.
- Drag-&-Drop-Zone für PDF- und Bildbelege.
- Vorhandene Dokumentenerkennung wird beim Import weiterverwendet.
- Finanzübersicht für Eingangs-/Ausgangsrechnungen, Kontoauszüge, Kasse, Gutschriften, Steuern und sonstige Buchhaltungsbelege.
- Finanzordner in Nextcloud um Gutschriften und sonstige Buchhaltungsbelege ergänzt.
- Ablage weiterhin automatisch nach Dokumentart, Jahr und Monat.
- Neuer Dokumenttyp „Sonstiger Buchhaltungsbeleg“.
- Direkte Links zu den Finanzordnern in Nextcloud.
- Dokumentation ergänzt.
- Keine Datenbankmigration.

## 1.5.0 – Final

- 1.5.0 RC1 nach erfolgreichem Praxistest als finalen Release-Stand übernommen.
- Keine neuen Kernfunktionen gegenüber RC1.
- Keine neue Datenbankmigration.
- Versions- und Release-Metadaten auf 1.5.0 final gesetzt.

## 1.5.0 RC1 – Release Candidate

- Aktuellen 1.4.x-Rollout-Stand als Release Candidate gebündelt.
- RC-Release-Notes und vollständige Smoke-Test-Checkliste ergänzt.
- Keine neue Kernfunktion und keine Datenbankmigration.
- Ziel: abschließender Praxis-, Update-, Rechte-, Mobile-, PDF- und Restore-Test vor 1.5.0.

## 1.4.18 – Backup/Restore & Deinstallation

- Systemstatus um Backup- und Deinstallationshinweise mit konkreten Lösungsvorschlägen ergänzt.
- Vollständige Betriebsanleitung für Backup, Restore-Test, Deaktivierung und App-Entfernung ergänzt.
- Dokumentation unterscheidet ausdrücklich zwischen App deaktivieren, App-Code entfernen und Geschäftsdaten löschen.
- Release-Checkliste um reale Restore- und Disable/Enable-Prüfungen erweitert.
- Keine Datenbankmigration und keine automatische Löschung von Geschäftsdaten.

## 1.4.17 – Update-/Bestandsdaten-Preflight

- Systemstatus prüft, ob versionierte NextERP-Datenbankmigrationen im Release vorhanden sind.
- Konkrete Upgrade-Testanleitung für reale Bestandsdaten ergänzt.
- Dokumentation um den vollständigen Bestandsdaten-Prüfumfang nach Updates ergänzt.
- Keine neue Datenbankmigration und keine Änderung vorhandener Geschäftsdaten.

## 1.4.16 – Release Audit

- Distribution package cleaned of historical per-version changelog/build clutter.
- Added maintainer release checklist for fresh install, update, permissions, mobile, PDF, backup/restore and removal tests.
- Added third-party component inventory.
- Added package-level checks for routes, PHP syntax and release metadata.
- No database migration.

# 0.61.6

PDF-Vorschau ohne iframe/CSP-Konflikt.

# 0.61.3

- Dokument-Zuordnung und Prüfroute repariert.

# 0.60.0 – Projektcenter

- Digitale Projektakte mit Bereichen für Angebote, Aufträge, Termine, Rapporte, Zeiten, Material, Fotos, Dokumente, Kosten und Timeline.
- Klassifizierter Dokumenteingang als Grundlage für späteren PDF-Import.
- Projektkostenübersicht aus Projektwert, Arbeitswert und Rapportmaterial.
- Projektbezogene Kalenderfelder und Dokumentmetadaten.

# 0.52.0

Materialgruppen, Lieferanten, Materialsuche und neue Lagerübersicht.

## 0.51.0
- Strukturierte Kundenadressen und korrigierte Nextcloud-Contacts-Synchronisation.

## 0.50.4
- Mobilnummer in Kundenstammdaten und Nextcloud-Kontakten.

# 0.29.0

Native DAV Core für Contacts und Calendar.

# 0.27.0

- Kunden aus Nextcloud Kontakte importieren.
- Neue Kunden direkt als Nextcloud-Kontakt speichern.
- Verbundene Kontakte beim Speichern automatisch aktualisieren.
- Importansicht mit Suche und Dublettenschutz.

# Changelog

## 0.25.0 – Projekt-Cockpit

- Projekt-Cockpit mit Tagesstunden, Team, Rapportstatus, Material, Fotos und Terminen
- Materialübersicht direkt in der Projektakte
- Projektfoto-Übersicht mit direktem Ordnerzugriff
- erweiterte Projektkennzahlen und Terminwarnung
- bestehende Projektakte, Dokumente, Zeiten und Journal bleiben integriert


## 0.24.0
- Kundenakte Pro mit Live-Kennzahlen
- Mehrere Ansprechpartner je Kunde
- Wiedervorlagen mit Fälligkeit und Erledigt-Status
- Projektkarten mit Fortschrittsanzeige
- Erweiterte Kundenhistorie und Dokumentenansicht
- Stable-Core-Migration 0.23 korrigiert (`is_archived`)


## 0.23.0 – Stable Core

- Integrierte Systemprüfung für Datenbank, Nummernkreise, Dateiablage, Logo, PHP und ERP-Zugriff
- Zusätzliche Datenbankindizes für Projekt-, Rapport- und Zeitauswertungen
- Einstellungen um direkten Diagnosezugriff erweitert
- Stable-Core-Regeln in der App dokumentiert
- Versions- und Releasebasis für den Weg zu 1.0 festgeschrieben
