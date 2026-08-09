# NextERP 1.4.1 – Nextcloud Compatibility Fix

- Nicht portable Aufrufe von `OC::$server->getURLGenerator()` entfernt.
- URL-Generator wird über den Nextcloud-Servicecontainer als `OCP\IURLGenerator` bezogen.
- Betrifft Dashboard, Kunden-/Projekt-/Dokumentansichten sowie Mobile/PWA-Templates.
- Systemprüfung verwendet `OCP\IConfig` statt der internen `getConfig()`-Convenience-Methode.
- Gesamter PHP-Code auf direkte `OC::$server`-Methoden geprüft; verbleibend werden nur Servicecontainer-Aufrufe `get(...)` verwendet.
- Versionsanzeige auf 1.4.1 aktualisiert.
- Keine Datenbankmigration.
