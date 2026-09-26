# Betrio 2.4.50

- Idempotente Mobile-Schreibvorgaenge mit `clientId` fuer Zeiten, Rapporte, Projektnotizen und Datei-Uploads.
- Mobile Sync akzeptiert `time`, `report` und `note` und verwendet die Sync-UUID als `clientId`.
- Erfolgreiche Vorgänge werden in `re_erp_mobile_sync_receipts` gespeichert, sodass Retries keine Dubletten erzeugen.
- Bestehende Clients ohne `clientId` bleiben kompatibel.
