# Betrio 2.5.17

## Dokumenterkennung

- Digitalen PDF-Text weiterhin bevorzugt direkt mit `pdftotext` auslesen.
- OCR-Fallback nicht mehr nur anhand von 30 Zeichen, sondern über eine Qualitätsprüfung des erkannten Belegtexts.
- Scan-PDFs mit 240 dpi, Graustufen und verlustfreiem PNG für Tesseract aufbereiten.
- OCR mit zwei Layout-Modi (PSM 6 und PSM 11); Betrio verwendet automatisch das plausiblere Ergebnis.
- Guten eingebetteten PDF-Text niemals durch ein schlechteres OCR-Ergebnis ersetzen.
- OCR für sehr große PDFs auf die ersten 12 Seiten begrenzen.
- Keine neue Pflichtabhängigkeit und keine Datenbankmigration.

### Elasticsearch / Nextcloud Fulltext Search
Nextcloud Fulltext Search bleibt bewusst optional. Die öffentlichen Nextcloud-Schnittstellen sind Such-/Index-Schnittstellen und liefern Betrio nicht stabil den vollständigen bereits extrahierten Text einer konkreten Datei. Betrio koppelt sich daher nicht an interne Fulltextsearch-/Elasticsearch-Klassen. So bleibt die App NC32–35-kompatibel und funktioniert unabhängig von der installierten Suchplattform.
