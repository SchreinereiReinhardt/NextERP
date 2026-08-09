# NextERP 1.2.4 – Mobile Navigation Final Fix

- Bottom-Navigation aus dem Nextcloud App-Content-Markup entfernt.
- Navigation wird als separates Viewport-Element nach dem mobilen Seiteninhalt gerendert.
- Kein JavaScript-Verschieben mehr.
- `position: fixed` bezieht sich damit nicht mehr auf den scrollenden App-Content.
- Safe-Area und Inhaltsabstand bleiben erhalten.
- Keine Datenbankmigration.
