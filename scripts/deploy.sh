#!/usr/bin/env bash
set -Eeuo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "Dieses Skript muss als root ausgeführt werden."
    exit 1
fi

SOURCE="/home/andrereinhardt/NextERP"
TARGET="/var/www/nextcloud/apps/reinhardterp"
NEXTCLOUD="/var/www/nextcloud"
BACKUP="/home/andrereinhardt/backups/reinhardterp-$(date +%F_%H-%M-%S)"

echo "1/6: PHP-Syntax prüfen"
while IFS= read -r -d '' file; do
    php -l "$file" >/dev/null
done < <(find "$SOURCE" -type f -name '*.php' -print0)

echo "2/6: Sicherung anlegen"
sudo mkdir -p /home/andrereinhardt/backups
if [ -d "$TARGET" ]; then
    sudo cp -a "$TARGET" "$BACKUP"
fi

echo "3/6: App deaktivieren"
cd "$NEXTCLOUD"
sudo -u www-data php occ app:disable reinhardterp || true

echo "4/6: Dateien übertragen"
sudo rsync -a --delete \
    --exclude='.git/' \
    --exclude='scripts/' \
    "$SOURCE/" "$TARGET/"

sudo chown -R www-data:www-data "$TARGET"

echo "5/6: App aktivieren und Migrationen ausführen"
sudo -u www-data php occ app:enable reinhardterp
sudo -u www-data php occ maintenance:repair

echo "6/6: Status prüfen"
sudo -u www-data php occ app:list | grep reinhardterp

echo
echo "Deployment abgeschlossen."
echo "Sicherung: $BACKUP"
