# NextERP 1.2.5 – Scanner Hotfix

- Kamera wird jetzt unabhängig von BarcodeDetector über getUserMedia geöffnet.
- Bevorzugt die rückseitige Kamera.
- Klare Meldungen für blockierte Berechtigung, fehlende Kamera und nicht unterstützte Barcode-Erkennung.
- Native Handykamera (`capture=environment`) als zusätzlicher Fallback.
- Scanner kann explizit geschlossen werden.
- Kamera-Stream wird beim Verlassen der Seite sauber beendet.
- Automatische Barcode-Erkennung bleibt aktiv, wenn BarcodeDetector verfügbar ist.
- Manuelle Barcode-Suche bleibt immer möglich.
- Keine Datenbankmigration.
