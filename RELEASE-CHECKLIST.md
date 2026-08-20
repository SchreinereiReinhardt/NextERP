# Betrio Release Checklist

This checklist is intended for maintainers before publishing a release.

## Package
- [ ] `appinfo/info.xml` version matches the release tag.
- [ ] ZIP contains exactly one top-level `reinhardterp/` directory.
- [ ] No backups, editor files, temporary files, secrets or development dumps are included.
- [ ] README, COPYING, CHANGELOG and THIRD_PARTY are present.
- [ ] PHP syntax check passes for all PHP files.
- [ ] Every declared route resolves to an existing controller method.

## Installation and update
- [ ] Fresh install on a supported Nextcloud version.
- [ ] Existing installation upgraded from the previous public release.
- [ ] Customers, projects, times, materials, reports, signatures, settings and archives remain intact.
- [ ] Nextcloud administrator can always open Betrio.
- [ ] Setup wizard completes without manual source-code changes.

## Functional smoke test
- [ ] Company data and logo.
- [ ] Customer and project creation.
- [ ] Project permissions for office and employee accounts.
- [ ] Time and material entry.
- [ ] Documents and photos.
- [ ] Report creation, signature and PDF download.
- [ ] Archive and reopen workflows.
- [ ] Calendar integration.
- [ ] Mobile login, project list, documents, photo upload and report workflow.
- [ ] System check and diagnostics download.

## Security
- [ ] Anonymous access rejected.
- [ ] Unconfigured Nextcloud user rejected.
- [ ] Admin / office / employee roles tested separately.
- [ ] Direct URL access to a foreign project rejected.
- [ ] Mobile API access to a foreign project rejected.
- [ ] Folder restrictions apply to document lists and uploads.
- [ ] Upload type restrictions tested.
- [ ] Diagnostics contain no customer data, passwords or tokens.

## Recovery
- [ ] Backup created before upgrade (database + config + data directory).
- [ ] Restore tested on a non-production instance and business data compared.
- [ ] Disable/enable app tested with existing business data.
- [ ] App-code removal tested separately from business-data deletion.
- [ ] Removal behaviour documented and verified; no manual database/folder deletion used as uninstall procedure.

A release is not considered rollout-tested until the applicable unchecked items have been verified on real Nextcloud installations.
