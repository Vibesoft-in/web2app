#!/usr/bin/env bash
# ==============================================================================
# Hardened Production Database Backup Script: WebIntoApp
# ==============================================================================
set -euo pipefail

TIMESTAMP=$(date -u +"%Y%m%d_%H%M%S")
BACKUP_DIR="${BACKUP_DIR:-/backups}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-webintoapp}"
DB_USERNAME="${DB_USERNAME:-webintoapp}"
DB_PASSWORD="${DB_PASSWORD:-secret}"
S3_BUCKET="${BACKUP_S3_BUCKET:-}"

BACKUP_FILENAME="webintoapp_${DB_DATABASE}_${TIMESTAMP}.sql.gz"
BACKUP_PATH="${BACKUP_DIR}/${BACKUP_FILENAME}"
CHECKSUM_PATH="${BACKUP_PATH}.sha256"
LOG_FILE="${BACKUP_DIR}/backup.log"

mkdir -p "${BACKUP_DIR}"
chmod 700 "${BACKUP_DIR}"

log() {
    echo "[$(date -u +'%Y-%m-%dT%H:%M:%SZ')] $*" | tee -a "${LOG_FILE}"
}

log "INFO: Starting database backup for database '${DB_DATABASE}' on ${DB_HOST}:${DB_PORT}..."

# Export password safely to MySQL client
export MYSQL_PWD="${DB_PASSWORD}"

# Execute mysqldump with atomic transaction, routines, triggers, and gzip compression
if mysqldump \
    --host="${DB_HOST}" \
    --port="${DB_PORT}" \
    --user="${DB_USERNAME}" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --events \
    --hex-blob \
    --set-gtid-purged=OFF \
    --no-autocommit \
    "${DB_DATABASE}" | gzip -9 > "${BACKUP_PATH}"; then

    chmod 600 "${BACKUP_PATH}"
    log "INFO: mysqldump completed successfully. Verifying archive integrity..."
    
    # Verify gzip integrity
    if ! gunzip -t "${BACKUP_PATH}"; then
        log "FATAL: Backup archive verification failed for ${BACKUP_PATH}!"
        rm -f "${BACKUP_PATH}"
        exit 1
    fi

    # Generate SHA-256 checksum
    (cd "${BACKUP_DIR}" && sha256sum "${BACKUP_FILENAME}" > "${CHECKSUM_PATH}")
    BACKUP_SIZE=$(du -h "${BACKUP_PATH}" | awk '{print $1}')
    log "SUCCESS: Backup verified (${BACKUP_SIZE}). Saved at ${BACKUP_PATH}"

    # Optional S3 offsite synchronization
    if [ -n "${S3_BUCKET}" ]; then
        log "INFO: Syncing backup to remote S3 bucket ${S3_BUCKET}..."
        if command -v aws >/dev/null 2>&1; then
            aws s3 cp "${BACKUP_PATH}" "s3://${S3_BUCKET}/backups/${BACKUP_FILENAME}" --sse aws:kms
            aws s3 cp "${CHECKSUM_PATH}" "s3://${S3_BUCKET}/backups/${BACKUP_FILENAME}.sha256" --sse aws:kms
            log "SUCCESS: Offsite S3 sync completed."
        else
            log "WARN: AWS CLI not found; skipping remote S3 upload."
        fi
    fi

    # Prune local backups older than RETENTION_DAYS
    log "INFO: Pruning local backups older than ${RETENTION_DAYS} days..."
    find "${BACKUP_DIR}" -type f -name "webintoapp_${DB_DATABASE}_*.sql.gz*" -mtime +"${RETENTION_DAYS}" -delete
    log "INFO: Pruning complete."
    exit 0
else
    log "FATAL: mysqldump failed!"
    rm -f "${BACKUP_PATH}"
    exit 2
fi
