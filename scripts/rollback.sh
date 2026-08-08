#!/usr/bin/env bash
set -Eeuo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "Dieses Skript muss als root ausgeführt werden."
    exit 1
fi

TARGET="/var/www/nextcloud/apps/reinhardterp"
NEXTCLOUD="/var/www/nextcloud"

BACKUP="${1:-}"

if [ -z "$BACKUP" ] || [ ! -d "$BACKUP" ]; then
    echo "Aufruf:"
    echo "  $0 /home/andrereinhardt/backups/reinhardterp-DATUM_UHRZEIT"
    exit 1
fi

cd "$NEXTCLOUD"
sudo -u www-data php occ app:disable reinhardterp || true

sudo rm -rf "$TARGET"
sudo cp -a "$BACKUP" "$TARGET"
sudo chown -R www-data:www-data "$TARGET"

sudo -u www-data php occ app:enable reinhardterp

echo "Rollback abgeschlossen: $BACKUP"
