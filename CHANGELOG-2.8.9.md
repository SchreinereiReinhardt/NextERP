# Betrio 2.8.9

- Mobile Arbeitszeiterfassung für aktuelle Nextcloud-Version stabilisiert.
- Feste Mobile-API-Routen für Kommen, Pause, Fortsetzen und Gehen.
- HTTP-400-Fehler bei Attendance-Aktionen behoben.
- Attendance-Aktionen sind idempotent und tolerieren Retries/Doppeltipps sowie bereits erreichte Zustände.
- Temporäre Diagnose- und Debug-Ausgaben entfernt.
