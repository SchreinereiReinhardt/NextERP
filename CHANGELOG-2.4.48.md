# Betrio 2.4.48

- Mobile project documents expose Nextcloud Collaborative Tags as `collaborativeTags`.
- Files added directly through Nextcloud Files remain the source of truth; no duplicate tag storage is introduced.
- Added authenticated Mobile API `GET /api/mobile/v1/working-time/day?date=YYYY-MM-DD`.
- The working-time endpoint delegates to the existing `WorkingTimeService` provider architecture; no HR provider is hard-coded.
- Existing Betrio project time tracking remains unchanged.
