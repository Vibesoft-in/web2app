#!/usr/bin/env bash
# ==============================================================================
# Hardened Production Database Restore Script: WebIntoApp
# ==============================================================================
set -euo pipefail

BACKUP_FILE="${1:-}"
BACKUP_DIR="${BACKUP_DIR:-/backups}"
APP_DIR="${APP_DIR:-/var/www/backend}"

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-webintoapp}"
DB_USERNAME="${DB_USERNAME:-webintoapp}"
DB_PASSWORD="${DB_PASSWORD:-secret}"

log() {
    echo "[$(date -u +'%Y-%m-%dT%H:%M:%SZ')] $*"
}

# If no backup file specified, list recent backups and prompt
if [ -z "${BACKUP_FILE}" ]; then
    log "ERROR: No backup file specified."
    echo "Available backups in ${BACKUP_DIR}:"
    ls -lht "${BACKUP_DIR}"/webintoapp_*.sql.gz 2>/dev/null || true
    echo ""
    echo "Usage: $0 /path/to/backup.sql.gz"
    exit 1
fi

if [ ! -f "${BACKUP_FILE}" ]; then
    log "FATAL: Backup file '${BACKUP_FILE}' does not exist!"
    exit 1
fi

# Verify SHA256 checksum if available
CHECKSUM_FILE="${BACKUP_FILE}.sha256"
if [ -f "${CHECKSUM_FILE}" ]; then
    log "INFO: Verifying SHA-256 checksum..."
    (cd "$(dirname "${BACKUP_FILE}")" && sha256sum -c "$(basename "${CHECKSUM_FILE}")")
    log "SUCCESS: Checksum verified."
else
    log "WARN: No checksum file found for ${BACKUP_FILE}. Integrity check skipped."
fi

# Critical confirmation prompt
echo "=============================================================================="
echo " [!] CRITICAL WARNING: YOU ARE ABOUT TO OVERWRITE DATABASE '${DB_DATABASE}'"
echo " Target Host: ${DB_HOST}:${DB_PORT}"
echo " Backup File: ${BACKUP_FILE}"
echo "=============================================================================="
echo "To proceed, type exactly: RESTORE_${DB_DATABASE}"
read -r -p "Confirmation: " CONFIRMATION

if [ "${CONFIRMATION}" != "RESTORE_${DB_DATABASE}" ]; then
    log "ABORTED: Confirmation mismatch. Database was NOT modified."
    exit 1
fi

export MYSQL_PWD="${DB_PASSWORD}"

# Step 1: Put application in maintenance mode
if [ -d "${APP_DIR}" ]; then
    log "INFO: Putting application into maintenance mode..."
    (cd "${APP_DIR}" && php artisan down --render="errors.503" --secret="emergency-restore" || true)
fi

# Step 2: Create a safety pre-restore snapshot
PRE_RESTORE_FILE="${BACKUP_DIR}/pre_restore_snapshot_$(date -u +'%Y%m%d_%H%M%S').sql.gz"
log "INFO: Creating pre-restore snapshot at ${PRE_RESTORE_FILE}..."
mysqldump \
    --host="${DB_HOST}" \
    --port="${DB_PORT}" \
    --user="${DB_USERNAME}" \
    --single-transaction \
    --quick \
    "${DB_DATABASE}" | gzip -9 > "${PRE_RESTORE_FILE}" || true
log "INFO: Pre-restore snapshot created."

# Step 3: Restore Database from gzipped backup
log "INFO: Restoring database '${DB_DATABASE}' from ${BACKUP_FILE}..."
if gunzip -c "${BACKUP_FILE}" | mysql \
    --host="${DB_HOST}" \
    --port="${DB_PORT}" \
    --user="${DB_USERNAME}" \
    "${DB_DATABASE}"; then

    log "SUCCESS: Database restored successfully."

    # Step 4: Run pending migrations & refresh application caches
    if [ -d "${APP_DIR}" ]; then
        log "INFO: Running post-restore database migrations..."
        (cd "${APP_DIR}" && php artisan migrate --force)
        
        log "INFO: Refreshing application config, route, and view caches..."
        (cd "${APP_DIR}" && php artisan cache:clear)
        (cd "${APP_DIR}" && php artisan config:cache)
        (cd "${APP_DIR}" && php artisan route:cache)
        (cd "${APP_DIR}" && php artisan view:cache)

        log "INFO: Bringing application back online..."
        (cd "${APP_DIR}" && php artisan up)
    fi

    log "SUCCESS: Restore process finished completely and cleanly."
    exit 0
else
    log "FATAL: Database restore failed during MySQL import!"
    echo "You can rollback using the safety snapshot: ${PRE_RESTORE_FILE}"
    exit 2
fi
