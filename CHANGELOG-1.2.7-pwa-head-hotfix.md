# NextERP 1.2.7 – PWA HEAD Hotfix

Ursache des falschen App-Starts:
- Das NextERP-PWA-Manifest wurde bisher aus dem Body-Template eingebunden.
- Nextcloud selbst besitzt bereits Head-Metadaten/Manifest-Kontext.
- Browser können dadurch beim Installieren die normale Nextcloud-Seite als App-Basis übernehmen.

Fix:
- NextERP-Manifest wird jetzt über `OCP\Util::addHeader()` in den echten HTML-HEAD der Mobile-Seiten eingefügt.
- Mobile-Web-App- und Apple-Metadaten ebenfalls im echten HEAD.
- Body-Manifest-Link entfernt, um doppelte/konkurrierende Manifeste zu vermeiden.
- Manifest-Cache-Busting auf `127-headfix`.
- `start_url` bleibt explizit die NextERP-Mobile-Route.
- Scope umfasst die NextERP-App-Routen.
- Service Worker bleibt registriert.
- Keine Datenbankmigration.

Nach Installation:
1. Alte NextERP-PWA deinstallieren.
2. Chrome Website-Daten für die Nextcloud-Domain möglichst schließen/neu laden.
3. Direkt `/index.php/apps/reinhardterp/mobile` öffnen.
4. Erst auf dieser Seite „App installieren“ wählen.
