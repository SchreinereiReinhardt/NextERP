# Betrio 1.9.31

## Mobile Projekt bearbeiten
- Neuer Endpunkt `PUT /api/mobile/v1/project/{id}`.
- Projektname, Status, Startdatum, Fälligkeitsdatum und Beschreibung können mobil geändert werden.
- Stammdaten- und Projektberechtigung werden serverseitig geprüft.
- Der bestehende Nextcloud-Projektordner wird beim Umbenennen des Projekts bewusst nicht verschoben oder neu angelegt.
