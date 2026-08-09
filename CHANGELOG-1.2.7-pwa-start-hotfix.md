# NextERP 1.2.7 – PWA Start Hotfix

- PWA `start_url` zeigt explizit auf die NextERP-Mobile-Route.
- Manifest erhält eine stabile App-ID auf Basis der Mobile-Route.
- Scope wird aus der Mobile-Route abgeleitet.
- Manifest wird nicht mehr gecacht.
- Alte NextERP-PWA-Shell-Caches werden beim Aktivieren des Service Workers entfernt.
- Navigationsseiten werden weiterhin nicht offline aus dem Cache beantwortet.
- Manifest-Link erhält Cache-Busting.
- Keine Datenbankmigration.

Hinweis: Bereits installierte PWA einmal deinstallieren und nach dem Browser-Neuladen erneut installieren, damit Android/Chrome das korrigierte Manifest sicher übernimmt.
