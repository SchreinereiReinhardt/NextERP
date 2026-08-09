# NextERP 1.2.7 – PWA Response Hotfix

Ursache:
- `OCP\AppFramework\Http\Response` besitzt keine `setContent()`-Methode.
- Manifest, Service Worker und SVG-Icon führten deshalb beim direkten Aufruf zu HTTP 500.

Fix:
- Manifest, Service Worker und PWA-Icons verwenden jetzt `DataDisplayResponse`.
- Korrekte MIME-Typen und Cache-Header werden direkt im Response-Konstruktor gesetzt.
- Manifest-Cache-Busting auf `127-responsefix`.
- Alte PWA-Shell-Caches werden durch neuen Cache-Namen abgelöst.
- Keine Datenbankmigration.
