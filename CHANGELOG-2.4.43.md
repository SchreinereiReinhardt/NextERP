# Betrio 2.4.43

- Stornieren einer festgeschriebenen Rechnung erzeugt automatisch einen eigenen festgeschriebenen Gegenbeleg (Gutschrift) mit eigener Rechnungsnummer.
- Ursprungsrechnung bleibt unverändert erhalten und wird nachvollziehbar als storniert markiert.
- Ursprungsbeleg und Gegenbeleg werden über `related_invoice_id` und das Rechnungsprotokoll verknüpft.
- DATEV exportiert die Ursprungsrechnung weiterhin als Soll-Buchung; nur der separate Gegenbeleg wird als Haben-Buchung exportiert.
- Bestehende DATEV-Belegreferenz / Beleg-GUID bleibt erhalten.
- Keine Datenbankmigration erforderlich.
