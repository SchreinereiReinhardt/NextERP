# Betrio – Backup, Restore und App-Entfernung

## Was gesichert werden muss
Betrio ist eine Nextcloud-App. Für eine belastbare Wiederherstellung genügt deshalb nicht nur das App-Verzeichnis.

Vor einem Update oder einer geplanten Entfernung sollten mindestens gemeinsam gesichert werden:
1. Nextcloud-Datenbank
2. Nextcloud `config/`
3. vollständiges Nextcloud-Datenverzeichnis
4. Betrio-App-Verzeichnis bzw. das verwendete Release-Paket

Je nach Installation können außerdem Webserver-, PHP-, Cron- und externe Storage-Konfigurationen relevant sein.

## Empfohlener Backup-Ablauf
1. Benutzer über Wartungsfenster informieren.
2. Nextcloud in Wartungsmodus setzen.
3. Datenbank sichern.
4. `config/` und Datenverzeichnis sichern.
5. verwendete Betrio-Version notieren.
6. Wartungsmodus wieder deaktivieren.
7. Backup nicht nur erzeugen, sondern regelmäßig auf einer separaten Testinstanz wiederherstellen.

## Restore-Test
Ein Restore gilt erst als erfolgreich, wenn danach mindestens geprüft wurde:
- Nextcloud startet ohne Fehler.
- Betrio-Version und Systemprüfung sind plausibel.
- Firmendaten und Logo vorhanden.
- Benutzer/Rollen vorhanden.
- Kunden und Projekte vorhanden.
- Zeiten und Material vorhanden.
- Dokumente und Fotos erreichbar.
- Rapporte und Unterschriften vorhanden.
- Rapport-PDF kann erzeugt werden.
- Archivdaten vorhanden.
- Mobile/API funktioniert für ein Testkonto.

## Deaktivieren, Entfernen und Daten löschen
Diese drei Vorgänge dürfen nicht verwechselt werden.

### App deaktivieren
Das Deaktivieren von Betrio ist ein Betriebszustand. Vor dem erneuten Aktivieren sollte die gleiche App-Version bzw. ein unterstützter Update-Pfad verwendet werden.

### App entfernen
Vor dem Entfernen der App immer ein vollständiges Backup und wichtige Rapporte/Projektunterlagen sichern. Das Entfernen des App-Codes ist nicht als Verfahren zur Löschung oder Archivierung von Geschäftsdaten zu verwenden.

### Geschäftsdaten löschen
Geschäftsdaten dürfen nur über ausdrücklich dafür vorgesehene Funktionen und nach Prüfung gesetzlicher Aufbewahrungsanforderungen gelöscht werden. Datenbanktabellen oder Projektordner nicht manuell als „Deinstallation“ löschen.

## Öffentlicher Rollout
Vor einem öffentlichen Release müssen Disable/Enable, Backup/Restore und App-Entfernung auf einer separaten Testinstanz praktisch durchgeführt werden. Ein Quellcode-Audit allein kann das Verhalten einer realen Nextcloud-Installation nicht beweisen.
