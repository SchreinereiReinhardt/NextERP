# Betrio 2.4.20

## Fixes

- Fixed activation on Nextcloud 32 where database schema validation rejected boolean `NOT NULL` columns with PHP boolean defaults.
- Boolean defaults in database migrations now use `0` and `1` for compatibility across supported Nextcloud/database combinations.
- Nextcloud support remains 32 through 35.

## Notes

This release specifically addresses the activation error reported on Nextcloud 32 for `re_erp_customers.is_archived` and applies the same compatibility fix consistently to all boolean defaults in Betrio migrations.
