# Betrio 1.9.22

## Mobile PDF-Pfadfix

- Behebt die fälschliche Meldung „Keine Berechtigung für dieses Dokument“ beim direkten PDF-Abruf.
- `projectDocumentContent()` vergleicht den angeforderten Pfad jetzt mit `file_path`, genau wie `mobileDocumentRow()` ihn an die Mobile-App ausliefert.
- Die vorhandene Projekt- und Ordnerberechtigungsprüfung über `projectDocuments()` bleibt bestehen.
- Direkte PDF-Auslieferung aus Betrio 1.9.20/1.9.21 bleibt erhalten.
