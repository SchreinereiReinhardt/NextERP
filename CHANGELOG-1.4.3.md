# NextERP 1.4.3 – Navigation Small-Screen Fix

- Linke NextERP-Navigation erhält einen eigenen vertikalen Scrollbereich.
- Ausgeklappte Bereiche wie Verwaltung bleiben auch auf kleinen Notebook-Displays vollständig erreichbar.
- Hauptinhalt und Navigation können unabhängig voneinander gescrollt werden.
- `100dvh` mit `100vh`-Fallback berücksichtigt unterschiedliche Browser/Viewport-Höhen.
- Horizontales Überlaufen der Navigation wird verhindert.
- Dezente Scrollbar für Firefox, Chromium und WebKit.
- Mobile/PWA-Navigation wird nicht verändert.
- Desktop-Navigationsicons übernehmen jetzt automatisch die in Nextcloud eingestellte Primär-/Akzentfarbe statt eines fest verdrahteten Blautons.
- Keine Datenbankmigration.
