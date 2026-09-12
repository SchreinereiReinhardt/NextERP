# Betrio 2.4.22

## Fixes

- Fixed another Nextcloud 32 activation blocker caused by overly long database index names.
- Shortened affected invoice and project-document index names to satisfy Nextcloud 32 schema validation.
- Retains the 2.4.21 boolean migration compatibility fix.
- Nextcloud support declaration remains 32 through 35.

## Notes

This release supersedes 2.4.21 for Nextcloud 32 compatibility. It addresses the index-name validation error reported after the boolean schema issue was resolved.
