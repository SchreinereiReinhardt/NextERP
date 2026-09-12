# Betrio 2.4.24

- Fixes Nextcloud 32 compatibility for OC\DB\ResultAdapter.
- Replaces unsupported fetchAllAssociative() calls with fetchAll().
- Replaces unsupported fetchFirstColumn() calls with fetchAll(PDO::FETCH_COLUMN).
- Replaces unsupported fetchNumeric() calls with fetch(PDO::FETCH_NUM).
- Keeps the existing Nextcloud 32 migration/index fixes and English localization from 2.4.23.

- Added explicit short primary-key names to all migration-created tables for Nextcloud 32 compatibility.
