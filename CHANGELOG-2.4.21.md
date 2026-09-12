# Betrio 2.4.21

## Fixes

- Fixed Nextcloud 32 activation failure caused by NOT NULL boolean columns in database migrations.
- Boolean migration columns are now nullable where required for Nextcloud 32 database schema validation.
- Boolean defaults remain represented as 0 and 1.
- Nextcloud support remains 32 through 35.

## Notes

This release supersedes 2.4.20 for Nextcloud 32 compatibility and addresses the reported activation error for `re_erp_customers.is_archived` consistently across Betrio migrations.
