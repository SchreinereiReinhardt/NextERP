# Betrio

Native Handwerker-ERP-App für Nextcloud.

## Stable Core 0.23.0

Diese Version friert den Kern aus Kunden, Projekten, Zeiten, Rapporten, Dokumenten, Material und Rechten ein und ergänzt eine integrierte Systemprüfung unter **Einstellungen → Systemprüfung**.

Native ERP-App für Nextcloud.

## Neu in 0.16.0

- Direkter Button zum vollständigen Kundenordner
- Direkter Button zum vollständigen Projektordner
- Schnellzugriffe auf Aufmaß, Planung, Zeichnungen, Material, Bestellungen, Rapporte, Fotos, Abnahme und Abrechnung
- Ordner öffnen in einem neuen Nextcloud-Files-Tab
- Weiterhin interne App-ID `reinhardterp`, damit vorhandene Daten und Tabellen erhalten bleiben

Reinhardt ERP 0.15.2

Fix: Projekt wird bei nur einem vorhandenen Projekt automatisch geladen; sichtbarer Ladebutton; offene Zeiteinträge mit NULL oder 0 werden angeboten.


## Version 0.15.3
- stabile Pointer-Event-Unterschrift für Maus, Stift und Touch
- Signatur wird laufend in das Formular übernommen
- Name ist beim Abschluss Pflicht
- Druckansicht zeigt Signaturbild, Name und Datum
- gesperrte Rapporte ohne Signatur werden eindeutig gekennzeichnet


## Version 0.19.0
- Dokumentencenter in Kunden- und Projektakte
- letzte Dateien direkt im ERP sichtbar
- Upload direkt in definierte Kunden- und Projektordner
- Öffnen der Datei über Nextcloud Files


## 0.19.0
- Projektworkflow mit Status-Schnellwechsel
- automatisches Projekt- und Kundenjournal
- interne Projektnotizen
- neues Dashboard mit Tagesstunden und Aktivitäten


## Version 0.20.0

Einheitliches kompaktes UI-Design mit kleineren Schriftgrößen, Karten, Tabellen, Dokumentzeilen und Buttons.


## Version 0.22.1
Balanced UI mit flächennutzendem Dashboard sowie Kartenansichten für Kunden und Projekte.


## 0.22.1
- Kompakte Projektübersicht als effiziente Zeilenansicht
- Neue Projektzentrale mit Kennzahlen, Workflow, Dokumenten, Zeiten, Rapporten und Journal
- Bessere Nutzung großer Desktop-Bildschirme


## 0.22.1
- Projektstatuswechsel sendet den Nextcloud-CSRF-Token über den offiziellen `requesttoken`-Header.
- Statusänderungen funktionieren für Fertigung, Montage, Abnahme, Abrechnung und alle weiteren Workflow-Schritte.
- Fehler beim Statuswechsel werden sichtbar gemeldet, ohne die Projektakte zu verlassen.


## 0.22.2

Projektinfo in der Projektakte neu strukturiert: klare Beschriftungen, keine überlappenden Werte und direkter Ordner-Button statt technischem Pfad.

## 0.25.0

Projekt-Cockpit mit Live-Kennzahlen, Material- und Fotoübersicht.


## Nextcloud Contacts

Kunden können aus Nextcloud Contacts importiert werden. Neue Kunden lassen sich beim Speichern automatisch im ausgewählten Nextcloud-Adressbuch anlegen; verknüpfte Kontakte werden bei Änderungen aktualisiert.
