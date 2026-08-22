# Betrio 1.9.20

## Mobile PDF-/Dokumentabruf

- Neuer authentifizierter Mobile-API-Endpunkt zum direkten Abruf von Projektdateien.
- Projektzugriff wird serverseitig geprüft.
- Der angeforderte Pfad muss innerhalb des Projektordners liegen.
- Ordner-/Projektberechtigungen werden vor dem Dateiabruf geprüft.
- PDFs werden mit `application/pdf` ausgeliefert.
- Grundlage für direktes Öffnen von PDFs in Betrio Mobile ohne Umweg über die Nextcloud-Dateiansicht.
