#!/usr/bin/env bash
# ==============================================================================
# Zero-Downtime Atomic Deployment Script: WebIntoApp
# ==============================================================================
set -euo pipefail

BASE_DIR="${BASE_DIR:-/var/www/webintoapp}"
RELEASES_DIR="${BASE_DIR}/releases"
SHARED_DIR="${BASE_DIR}/shared"
CURRENT_LINK="${BASE_DIR}/current"
KEEP_RELEASES=5

RELEASE_ID=$(date -u +"%Y%m%d_%H%M%S")
TARGET_RELEASE="${RELEASES_DIR}/${RELEASE_ID}"
LOG_FILE="${SHARED_DIR}/deploy.log"

mkdir -p "${RELEASES_DIR}" "${SHARED_DIR}/storage" "${SHARED_DIR}/logs"

log() {
    echo "[$(date -u +'%Y-%m-%dT%H:%M:%SZ')] $*" | tee -a "${LOG_FILE}"
}

log "========================================================================"
log "Starting Zero-Downtime Deployment: Release ${RELEASE_ID}"
log "========================================================================"

# Step 1: Clone / Copy repository to new release directory
log "Step 1/8: Cloning code into new release directory ${TARGET_RELEASE}..."
mkdir -p "${TARGET_RELEASE}"
git clone --depth 1 --branch "${DEPLOY_BRANCH:-main}" "${GIT_REPO:-.}" "${TARGET_RELEASE}"

# Step 2: Link shared persistent storage & environment files
log "Step 2/8: Linking shared environment and storage directories..."
ln -sfn "${SHARED_DIR}/.env" "${TARGET_RELEASE}/backend/.env"
rm -rf "${TARGET_RELEASE}/backend/storage"
ln -sfn "${SHARED_DIR}/storage" "${TARGET_RELEASE}/backend/storage"

# Step 3: Install production dependencies
log "Step 3/8: Installing PHP dependencies without dev packages..."
(
    cd "${TARGET_RELEASE}/backend"
    composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-progress
)

# Step 4: Execute database migrations
log "Step 4/8: Running database migrations (--force)..."
(
    cd "${TARGET_RELEASE}/backend"
    php artisan migrate --force
)

# Step 5: Warm application optimization caches
log "Step 5/8: Compiling config, route, view, and event caches..."
(
    cd "${TARGET_RELEASE}/backend"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
)

# Step 6: Atomic symlink switch
log "Step 6/8: Atomically switching 'current' symlink to release ${RELEASE_ID}..."
ln -nfs "${TARGET_RELEASE}" "${BASE_DIR}/current_tmp"
mv -Tf "${BASE_DIR}/current_tmp" "${CURRENT_LINK}"

# Step 7: Zero-downtime service reloads
log "Step 7/8: Reloading PHP-FPM OPcache and restarting queue workers..."
# Gracefully signal PHP-FPM to reload opcache without dropping open connections
if [ -f /var/run/php-fpm.pid ]; then
    kill -USR2 "$(cat /var/run/php-fpm.pid)"
elif docker ps --format '{{.Names}}' | grep -q "webintoapp_backend"; then
    docker exec webintoapp_backend kill -USR2 1 || true
fi

# Instruct Supervisor queue workers to restart after completing current jobs
(
    cd "${CURRENT_LINK}/backend"
    php artisan queue:restart
)

# Reload Nginx web server configuration
if [ -f /var/run/nginx.pid ]; then
    kill -HUP "$(cat /var/run/nginx.pid)"
elif docker ps --format '{{.Names}}' | grep -q "webintoapp_nginx"; then
    docker exec webintoapp_nginx nginx -s reload || true
fi

# Step 8: Post-deployment health check validation
log "Step 8/8: Verifying post-deployment health endpoints..."
ATTEMPTS=0
MAX_ATTEMPTS=6
HEALTH_OK=0

while [ "${ATTEMPTS}" -lt "${MAX_ATTEMPTS}" ]; do
    ATTEMPTS=$((ATTEMPTS + 1))
    log "Health check probe attempt ${ATTEMPTS}/${MAX_ATTEMPTS}..."
    if curl -s -f http://127.0.0.1:8000/healthz >/dev/null 2>&1; then
        HEALTH_OK=1
        break
    fi
    sleep 5
done

if [ "${HEALTH_OK}" -eq 1 ]; then
    log "SUCCESS: Deployment of ${RELEASE_ID} succeeded and verified healthy!"
    
    # Prune older releases (keeping the last $KEEP_RELEASES)
    log "Pruning old releases; keeping latest ${KEEP_RELEASES}..."
    cd "${RELEASES_DIR}"
    # shellcheck disable=SC2012
    ls -1dt 20* | tail -n +$((KEEP_RELEASES + 1)) | xargs -r rm -rf
    log "Deployment completed cleanly."
    exit 0
else
    log "FATAL: Post-deploy health probe failed! Triggering automatic rollback..."
    "${BASE_DIR}/infra/scripts/rollback.sh"
    exit 1
fi
