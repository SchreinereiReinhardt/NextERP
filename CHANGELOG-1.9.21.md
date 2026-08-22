# Betrio 1.9.21

## Mobile PDF-Berechtigungsfix
- Behebt den Fehler `Call to a member function canAccessProjectFolder() on null`.
- Der PDF-Endpunkt greift nicht mehr auf eine im MobileService nicht injizierte PermissionService-Instanz zu.
- Stattdessen wird geprüft, ob die angeforderte Datei in der bereits berechtigungsgefilterten Projekt-Dokumentliste enthalten ist.
- Projektzugriff, Projektordner-Begrenzung und PDF-MIME-Auslieferung bleiben erhalten.
