# Betrio Mobile API v1

Basis-URL: `/index.php/apps/reinhardterp/api/mobile/v1`

## Authentifizierung

`POST /login` mit JSON `{"username":"...","password":"...","deviceName":"Android"}`. Das Passwort kann ein Nextcloud-App-Passwort sein. Alle weiteren Aufrufe verwenden `Authorization: Bearer <accessToken>`.

## Endpunkte

- `POST /login`
- `POST /refresh`
- `POST /logout`
- `GET /bootstrap`
- `GET /dashboard`
- `GET /projects`
- `GET /project/{id}`
- `GET /project/{id}/documents`
- `GET /project/{id}/photos`
- `GET /material?q=...`
- `POST /report`
- `POST /time`
- `POST /upload` (multipart: `file`, `projectId`, `type`)
- `POST /scan` (multipart: `file`, `projectId`)
- `POST /sync`

Alle Antworten verwenden das Schema `success`, `data`, `errors`, `message` und optional `code`.

## Betrio 1.9.30

`customerId` ist bereits Bestandteil der Projektobjekte und bleibt verbindlich für die Zuordnung Kunde/Projekt.

### GET /api/mobile/v1/reports/open
Liefert die tatsächlichen offenen Rapporte für die Mobile-App. Berücksichtigt `locked = 0`, `archived = 0`, nicht archivierte Projekte und die Projektberechtigungen des angemeldeten Mobile-Benutzers.

Felder: `id`, `projectId`, `customerId`, `reportNo`, `title`, `reportDate`, `status`, `projectNo`, `projectName`, `customer`, `createdAt`.

Der Dashboard-Zähler `reportsOpen` verwendet bereits `countOpenReports($uid)` und bleibt damit berechtigungs- und archivgefiltert.
