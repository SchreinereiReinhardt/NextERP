# Betrio 2.4.60

## Smart Document Inbox – Erkennung verbessert

- Vorhandene Dokumenten-Inbox bleibt unverändert; keine zweite Inbox und kein externer KI-Dienst.
- PDF-Text/OCR wird bereits beim erstmaligen Einlesen für den Zuordnungsvorschlag ausgewertet.
- Kombinierte Bewertung aus Dokumenttyp, Projekt, Kunde, Lieferant und Referenznummern statt Einzel-Schlüsselwort.
- Flexible Erkennung von Schreibweisen und Trennzeichen (z. B. RE-2026-0041 / RE 2026 0041).
- Projekt- und Kundennummern werden stärker als allgemeine Namen gewichtet.
- Lieferanten-Cockpit wird einbezogen: Bestellnummer, AB-Nummer und Lieferant können Projekt und Lieferant sehr sicher vorschlagen.
- Projektvorschlag übernimmt den zugehörigen Kunden als konsistenten Vorschlag.
- Vorschlagsbegründung nennt die tatsächlich erkannten Signale.
- Keine automatische endgültige Zuordnung: Benutzer bestätigt den Vorschlag weiterhin selbst.
