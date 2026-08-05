# NextERP 0.66.4

- Mobile-API `/project/{id}/documents` liest jetzt zusätzlich die echten Dateien aus dem Nextcloud-Projektordner.
- Vorhandene Datensätze aus `re_erp_project_documents` und physische Dateien werden zusammengeführt und nach Pfad dedupliziert.
- Dateiname, Pfad, MIME-Typ, Größe, Änderungsdatum und Dokumenttyp werden an die Android-App geliefert.
- Der Dokumenttyp wird bei nicht klassifizierten Dateien anhand des Projekt-Unterordners abgeleitet.
