# NextERP 1.4.2 – Full Audit Fix

Vollprüfung des 1.4.1-Stands:
- PHP-Syntax aller 115 PHP-Dateien geprüft.
- Alle registrierten Routen auf vorhandene Controller/Methoden geprüft.
- Doppelte Routennamen und Route/Verb-Kollisionen geprüft.
- Direkte problematische `getURLGenerator()`-/`getConfig()`-Aufrufe bleiben entfernt.
- Fehler in der Lagerbewegung behoben: Web-Lagerbuchung referenzierte fälschlich `clientOperationId`.
- Systemprüfung prüft nun alle 35 von den Migrationen angelegten NextERP-Tabellen statt nur einer Teilmenge.
- Suche nach fest verdrahteter eigener Cloud-Adresse negativ.
- Keine Datenbankmigration.
