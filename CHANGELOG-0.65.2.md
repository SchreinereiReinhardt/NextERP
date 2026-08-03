# NextERP 0.65.2

- PDF-Angebote werden mit `pdftotext` ausgelesen.
- Angebotsnummer, Datum, Titel/Bauvorhaben, Netto, Umsatzsteuer und Brutto werden vorgeschlagen.
- Kunden und Projekte werden anhand des PDF-Texts vorgeschlagen.
- Einfach strukturierte Angebotspositionen werden erkannt und als echte Angebotspositionen übernommen.
- Der Importbereich wird auch bei älteren Datensätzen mit bereits gesetztem Verarbeitungsstatus angezeigt.
- Klare Meldung bei Scan-PDFs oder fehlendem `poppler-utils`.
