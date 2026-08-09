# NextERP 1.2.0 Mobile CSS Hotfix

- Lädt das vorhandene NextERP-Stylesheet auch in der eigenständigen Mobile-Ansicht.
- Ursache: Die Desktop-Ansichten laden `style.css` über `_nav.php`; die Mobile-Ansicht bindet `_nav.php` absichtlich nicht ein.
- Mobile Karten, Schnellzugriffe, Projektliste und Bottom-Navigation werden dadurch korrekt formatiert.
- Keine Datenbankmigration erforderlich.
