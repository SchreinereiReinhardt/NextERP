# Betrio 2.4.89 – Prozessintegration

## Verbunden
- Projektbezogene Lagerentnahmen werden im bestehenden Abrechnungsassistenten als eigene Materialquelle berücksichtigt.
- Lagerentnahmen können in eine Rechnung übernommen, als bereits enthalten markiert oder bewusst nicht berechnet werden.
- Offene Lagerentnahmen fließen in die bestehende Projektkontrolle „noch nicht entschieden“ ein.
- Die vorhandene Tabelle `re_erp_billing_checks` wird weiterverwendet; es entsteht keine parallele Abrechnungs- oder Materialstruktur.
- Rapportmaterial und Lagerentnahmen bleiben als ursprüngliche Quellen unterscheidbar. Bei Überschneidungen kann bewusst „Bereits enthalten“ gewählt werden.

## Dokumentation
- Material/Lager und Auswertung/Abrechnung beschreiben den verbundenen Prozess.

## Unverändert
- Keine neue Datenbankmigration.
- Banking bleibt unverändert.
- Bestehende Zeit-, Rapport-, Dokument-, Lieferanten-, Rechnungs-, Zahlungs- und DATEV-Prozesse bleiben erhalten.
