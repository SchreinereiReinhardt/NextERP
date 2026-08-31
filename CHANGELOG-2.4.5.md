# Betrio Server 2.4.5

- Neuer Login-Endpunkt `/api/mobile/v1/session-login`.
- Mobile Zugangsdaten werden dort durch die native Nextcloud HTTP Basic Authentifizierung geprüft.
- Unterstützt dadurch Nextcloud App-Passwörter, Zwei-Faktor-Konstellationen und externe Authentifizierungs-Backends über Nextcloud Core.
- Der bisherige `/login` Endpunkt bleibt für ältere Mobile-Versionen erhalten.
- Betrio eigene Access- und Refresh-Tokens werden erst nach erfolgreicher Nextcloud-Authentifizierung ausgegeben.
