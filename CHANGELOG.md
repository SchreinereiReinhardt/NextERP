# Changelog

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
