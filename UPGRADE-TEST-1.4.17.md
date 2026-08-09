# NextERP 1.4.17 – Bestandsdaten-/Update-Test

Automatische Quellcodeprüfungen können nicht beweisen, dass reale Bestandsdaten auf einem fremden Server nach einem Update vollständig erhalten bleiben. Deshalb ist dieser Test auf einer Testinstanz mit realistischen Daten auszuführen.

## A. Vor dem Update
1. Aktuelle NextERP-Version notieren.
2. Nextcloud-Backup und Datenbank-Backup erstellen.
3. Je einen Testdatensatz anlegen bzw. vorhandenen Testdatensatz notieren:
   - Firma und Logo
   - Benutzer/Rolle
   - Kunde
   - Projekt
   - Zeitbuchung
   - Materialposition
   - Dokument
   - Foto
   - Rapport
   - Unterschrift
   - Rapport-PDF
   - archiviertes Projekt/Rapport, soweit vorhanden
4. Systemprüfung ausführen und Diagnosebericht sichern.

## B. Update
1. Release-ZIP ausrollen.
2. Eigentümer auf `www-data:www-data` setzen.
3. `occ upgrade` ausführen.
4. `occ maintenance:repair` ausführen.
5. Wartungsmodus deaktivieren.
6. Installierte NextERP-Version kontrollieren.

## C. Nach dem Update
Alle in Abschnitt A notierten Daten öffnen und vergleichen. Zusätzlich:
- Nextcloud-Admin kann NextERP öffnen.
- Büro-/Monteur-Testkonten besitzen nur ihre vorgesehenen Rechte.
- Projektordnerfreigaben funktionieren.
- Rapport lässt sich öffnen, unterschreiben und als PDF herunterladen.
- Mobile Login/Token, Projektliste, Dokumente und Foto-Upload funktionieren.
- Systemprüfung enthält keine neuen Fehler.
- Diagnosebericht enthält keine Kunden-/Projektinhalte, Passwörter oder Tokens.

## D. Ergebnis
Nur wenn alle Prüfpunkte bestanden sind, den konkreten Upgrade-Pfad (z. B. 1.4.16 → 1.4.17) als praktisch getestet markieren. Bei Fehlern Backup zurückspielen und Ursache vor einem öffentlichen Release beheben.
