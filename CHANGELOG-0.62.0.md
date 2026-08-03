# NextERP 0.62.0 – Smart DMS

## Tatsächlich umgesetzt

- deterministische Dokumentklassifizierung anhand des Dateinamens
- Vorschläge für Dokumentart, Belegnummer und Belegdatum
- Vorschläge für Kunde, Projekt und Lieferant anhand vorhandener Stammdaten
- Vorbelegung der Vorschläge im Prüfarbeitsplatz
- Suchfeld für Dateiname, Belegnummer, Kunde, Projekt und Lieferant
- Filter nach Dokumentstatus und Dokumentart
- Dublettenwarnung anhand der vorhandenen Dateiprüfsumme
- erneute Analyse eines Dokuments per Schaltfläche
- Datenbankmigration für Analyse- und Vorschlagsfelder
- bestehende Jahres-/Monatsablage bleibt unverändert erhalten

## Bewusste Grenze

Diese Version verwendet noch keine OCR und liest keine PDF-Positionen aus. Sie analysiert zuverlässig die vorhandenen Dateinamen und Stammdaten. OCR und PDF-Textanalyse können darauf in einer späteren Version aufsetzen.
