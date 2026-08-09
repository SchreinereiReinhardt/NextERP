# NextERP 1.2.8 – Offline-Basis

- Mobile-PWA speichert erfolgreich geladene Mobile-Seiten lokal.
- Bereits besuchte Projekt-/Mobile-Seiten können bei Verbindungsabbruch aus dem Cache geöffnet werden.
- Navigation arbeitet Network-First: online kommen immer aktuelle Serverdaten.
- Statische NextERP-/Nextcloud-Ressourcen werden lokal zwischengespeichert.
- Sichtbare Online-/Offline-Anzeige in der mobilen Oberfläche.
- Alte NextERP-PWA-Caches werden beim Aktivieren entfernt.
- Schreibvorgänge (POST) werden bewusst noch NICHT offline in eine Queue gestellt.
  Damit können in dieser Stufe keine Zeiten, Materialbuchungen oder Rapporte durch
  automatische Wiederholungen doppelt angelegt werden.
- Die sichere Schreib-Queue mit eindeutigen Client-IDs ist der nächste Sync-Schritt.
- Keine Datenbankmigration.
