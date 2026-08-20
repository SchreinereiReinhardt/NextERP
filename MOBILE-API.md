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
