# NextERP 0.61.5

- Eigene geschützte Vorschau-Route `/api/documents/{id}/preview`
- PDF- und Bildausgabe nur für angemeldete Benutzer mit Dokumentenrecht
- PDF wird als `application/pdf` inline ausgegeben
- Eingebettete Vorschau verwendet keine blockierte Nextcloud-Preview-URL mehr
- MIME-Fallback anhand der Dateiendung
- Button zum vollständigen Öffnen bleibt erhalten
