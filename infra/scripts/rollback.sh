#!/usr/bin/env bash
# ==============================================================================
# Instant Rollback Script: WebIntoApp
# ==============================================================================
set -euo pipefail

BASE_DIR="${BASE_DIR:-/var/www/webintoapp}"
RELEASES_DIR="${BASE_DIR}/releases"
CURRENT_LINK="${BASE_DIR}/current"
ROLLBACK_DB="${1:-}"

log() {
    echo "[$(date -u +'%Y-%m-%dT%H:%M:%SZ')] $*"
}

log "========================================================================"
log "Starting Application Rollback..."
log "========================================================================"

if [ ! -L "${CURRENT_LINK}" ]; then
    log "FATAL: ${CURRENT_LINK} is not a symlink! Cannot determine current release."
    exit 1
fi

CURRENT_RELEASE=$(basename "$(readlink -f "${CURRENT_LINK}")")
log "Active release is: ${CURRENT_RELEASE}"

# Find the previous release
PREVIOUS_RELEASE=$(cd "${RELEASES_DIR}" && ls -1dt 20* | grep -v "^${CURRENT_RELEASE}$" | head -n 1 || true)

if [ -z "${PREVIOUS_RELEASE}" ]; then
    log "FATAL: No previous release found in ${RELEASES_DIR} to roll back to!"
    exit 1
fi

TARGET_RELEASE="${RELEASES_DIR}/${PREVIOUS_RELEASE}"
log "Rolling back to previous release: ${PREVIOUS_RELEASE} (${TARGET_RELEASE})"

# Step 1: Optional database rollback
if [ "${ROLLBACK_DB}" == "--with-db-rollback" ]; then
    log "Rolling back latest database migration step on current release..."
    (
        cd "${CURRENT_LINK}/backend"
        php artisan migrate:rollback --step=1 --force || true
    )
fi

# Step 2: Atomic symlink switch to previous release
log "Atomically updating symlink to ${PREVIOUS_RELEASE}..."
ln -nfs "${TARGET_RELEASE}" "${BASE_DIR}/current_tmp"
mv -Tf "${BASE_DIR}/current_tmp" "${CURRENT_LINK}"

# Step 3: Refresh caches on target release
log "Rewarming caches on rolled back release..."
(
    cd "${CURRENT_LINK}/backend"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
)

# Step 4: Signal PHP-FPM to reload opcache and worker pool
log "Reloading PHP-FPM and restarting queue workers..."
if [ -f /var/run/php-fpm.pid ]; then
    kill -USR2 "$(cat /var/run/php-fpm.pid)"
elif docker ps --format '{{.Names}}' | grep -q "webintoapp_backend"; then
    docker exec webintoapp_backend kill -USR2 1 || true
fi

(
    cd "${CURRENT_LINK}/backend"
    php artisan queue:restart
)

# Step 5: Reload Nginx
if [ -f /var/run/nginx.pid ]; then
    kill -HUP "$(cat /var/run/nginx.pid)"
elif docker ps --format '{{.Names}}' | grep -q "webintoapp_nginx"; then
    docker exec webintoapp_nginx nginx -s reload || true
fi

# Step 6: Verify health check
log "Verifying health check after rollback..."
if curl -s -f http://127.0.0.1:8000/healthz >/dev/null 2>&1; then
    log "SUCCESS: Rollback to ${PREVIOUS_RELEASE} completed and system is healthy!"
    exit 0
else
    log "WARN: Health check did not return 200 immediately. Please inspect application logs."
    exit 0
fi
