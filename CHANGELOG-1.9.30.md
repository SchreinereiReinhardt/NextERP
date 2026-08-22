# Betrio 1.9.30

- `customerId` in Projektobjekten geprüft und als bestehende API-Eigenschaft beibehalten.
- Neuer Mobile-Endpunkt `GET /api/mobile/v1/reports/open`.
- Liefert nur offene, nicht archivierte Rapporte aus nicht archivierten Projekten.
- Projektberechtigungen werden berücksichtigt.
- Bestehender korrigierter Dashboard-Zähler `countOpenReports($uid)` bleibt erhalten.
- Mobile-API-Dokumentation aktualisiert.
