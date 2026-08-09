# NextERP 1.2.4 – Mobile Navigation Hotfix 2

- Bottom-Navigation wird aus dem Nextcloud-Scrollcontainer herausgelöst.
- Navigation wird direkt an document.body angehängt.
- Dadurch bleibt sie unabhängig von #app-content, transform und overflow fest am Viewport.
- Extrem hoher z-index verhindert Überlagerung durch Nextcloud-Elemente.
- Keine Datenbankmigration.
