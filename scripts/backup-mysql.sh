#!/usr/bin/env sh
set -eu

PROJECT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
COMPOSE_FILE="$PROJECT_DIR/compose.production.yaml"
ENV_FILE="$PROJECT_DIR/.env.production"
BACKUP_DIR="$PROJECT_DIR/backups/mysql"
TIMESTAMP=$(date '+%Y-%m-%d_%H-%M-%S')
BACKUP_FILE="$BACKUP_DIR/halinhtravel_${TIMESTAMP}.sql.gz"
TEMP_FILE="$BACKUP_FILE.tmp"

if [ ! -f "$ENV_FILE" ]; then
    echo "Missing $ENV_FILE" >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"
trap 'rm -f "$TEMP_FILE"' EXIT HUP INT TERM

docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" exec -T db sh -c \
    'exec mysqldump --single-transaction --routines --events -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
    > "$TEMP_FILE"

gzip -c "$TEMP_FILE" > "$BACKUP_FILE"
rm -f "$TEMP_FILE"
trap - EXIT HUP INT TERM

find "$BACKUP_DIR" -type f -name 'halinhtravel_*.sql.gz' -mtime +29 -delete
echo "Created $BACKUP_FILE"
