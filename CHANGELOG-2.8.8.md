# Betrio 2.8.8

- Mobile Anwesenheitsaktionen sind idempotent.
- Wiederholtes Kommen durch Retry/Doppeltipp erzeugt keinen HTTP-400-Fehler mehr.
- Pause, Fortsetzen und Gehen tolerieren bereits erreichte Zustände und liefern den aktuellen Status zurück.
