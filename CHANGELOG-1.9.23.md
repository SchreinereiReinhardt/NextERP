# Betrio 1.9.23

## Mobile Berechtigungs- und Foto-Upload-Fix

- `PermissionService` wird jetzt korrekt in `MobileService` injiziert.
- Behebt `Call to a member function canAccessProjectFolder() on null`
  in der mobilen Dokument- und Fotoverwaltung.
- Projektordner-Berechtigungen funktionieren dadurch wieder in
  `projectDocuments()` und `upload()`.
- Behebt zusätzlich den bisher nicht initialisierten `$target` beim mobilen Upload.
- Foto-/Dokument-Zielordner werden für den angemeldeten Benutzer sicher angelegt.
- Der Upload wird über `FolderService` in den vorgesehenen Projektordner geschrieben.
- Die PDF-Fixes aus 1.9.20 bis 1.9.22 bleiben vollständig erhalten.
