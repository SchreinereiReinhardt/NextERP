# NextERP 1.2.7 – PWA Force-Mobile Hotfix

- Installierte NextERP-PWA erzwingt jetzt die Mobile-Route.
- Wenn Chrome im Standalone-Modus eine normale NextERP-Seite wiederherstellt,
  leitet `pwa-guard.js` sofort auf `/index.php/apps/reinhardterp/mobile` um.
- Normale Browser-Nutzung wird nicht umgeleitet.
- Andere Nextcloud-Apps werden nicht beeinflusst.
- Manifest start_url enthält einen eindeutigen PWA-Marker.
- launch_handler fordert eine neue Navigation beim App-Start an.
- Keine Datenbankmigration.
