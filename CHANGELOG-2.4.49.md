# Betrio 2.4.49

- Mobile Tageszuordnung erweitert: projektbezogene Tagesaufteilung direkt aus den bestehenden Betrio-Zeiteinträgen.
- `GET /api/mobile/v1/working-time/day` liefert zusätzlich `projectHours`, `travelHours` und `allocations`.
- Fahrzeit wird anhand der Aktivität (Anfahrt/Fahrt/Travel) separat ausgewiesen, bleibt aber Teil der gesamten zugeordneten Zeit.
- Bestehende Provider-Architektur bleibt unverändert; kein HR-Anbieter wird fest eingebaut.
- API bleibt abwärtskompatibel zu 2.4.48.
