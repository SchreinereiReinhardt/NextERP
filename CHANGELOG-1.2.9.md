# NextERP 1.2.9 – Offline Schreiben & Sync

- Arbeitszeiten können ohne Verbindung lokal in eine Warteschlange gelegt werden.
- Materialentnahmen können ohne Verbindung lokal in eine Warteschlange gelegt werden.
- Automatischer Sync beim Wiederkehren der Internetverbindung.
- Manueller Sync über die sichtbare Warteschlangen-Anzeige.
- Jede Offline-Buchung erhält eine eindeutige Client-Operation-ID.
- Server erkennt bereits erfolgreich verarbeitete Operationen und ignoriert Wiederholungen.
- Fehlgeschlagene Übertragungen bleiben in der lokalen Queue.
- Queue wird erst nach erfolgreicher HTTP-Antwort entfernt.
- Sichtbare Anzahl wartender Änderungen.
- Rapporte/Unterschriften sind noch nicht Teil der Offline-Schreibqueue.
- Keine Datenbankmigration.
