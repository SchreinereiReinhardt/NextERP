# Betrio 2.4.23

## Changes

- Commercial PDF metadata block aligned consistently: clerk, date and document number now use the same horizontal columns.
- Added automatic English UI support for non-German Nextcloud user languages.
- Added English translations for the main Betrio navigation, forms, business processes, statuses and common messages.
- Commercial and report PDFs now use English labels for users whose Nextcloud language is not German.
- German remains unchanged for German-language Nextcloud users.
- Retains the Nextcloud 32 migration compatibility fixes from 2.4.21 and 2.4.22.
- Supported Nextcloud versions remain 32 through 35.

## Language behavior

Betrio follows the language of the signed-in Nextcloud user. German users keep the German interface. Other languages currently fall back to English until a dedicated translation is available.

- Expanded English UI coverage for navigation, dashboard, common ERP actions and status texts.
- Added dynamic English dashboard greeting while preserving the user's display name.

- Fixed Nextcloud 32 database result compatibility by avoiding ResultAdapter::fetchAssociative().

- Expanded English localization to the documentation, quick start and help content.

- Expanded English documentation coverage for company setup, setup checklist, security, inventory and administration sections.

- Expanded English localization across customer import and common Customers, Projects, Sales, Employees, Inventory, Purchasing, Finance and Reports UI.

- Expanded English localization for the field worker/mobile web view and remaining desktop quick actions.
