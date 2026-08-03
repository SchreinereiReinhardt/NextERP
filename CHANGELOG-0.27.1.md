# NextERP 0.27.1

- Korrigiert die Route für „Aus Nextcloud importieren“.
- Verhindert, dass `/customers/import` als Kunden-ID interpretiert wird.
- Kalender-Synchronisierung auf die offizielle Nextcloud-Kalenderabfrage über `IManager::newQuery()` und `searchForPrincipal()` umgestellt.
- Versionsnummer auf 0.27.1 erhöht.
