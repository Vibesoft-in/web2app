#!/usr/bin/env bash
# ==============================================================================
# Isolated Flutter Android Release Compilation Runner
# ==============================================================================
set -euo pipefail

WORKSPACE_DIR="${1:-/builds/workspace}"
BUILD_NUMBER="${2:-1}"
BUILD_NAME="${3:-1.0.0}"
OUTPUT_DIR="${4:-/builds/artifacts}"
LOG_FILE="${OUTPUT_DIR}/build-${BUILD_NUMBER}.log"

mkdir -p "${OUTPUT_DIR}"

echo "[$(date -u +'%Y-%m-%dT%H:%M:%SZ')] Starting sandboxed Flutter build #${BUILD_NUMBER} (${BUILD_NAME})..." | tee "${LOG_FILE}"

# Verify non-root execution
if [ "$(id -u)" -eq 0 ]; then
    echo "ERROR: Build worker must NEVER run as root! Aborting." | tee -a "${LOG_FILE}"
    exit 101
fi

cd "${WORKSPACE_DIR}"

# Fetch flutter dependencies cleanly
echo "[$(date -u +'%Y-%m-%dT%H:%M:%SZ')] Running flutter pub get..." | tee -a "${LOG_FILE}"
flutter pub get >> "${LOG_FILE}" 2>&1

# Build release App Bundle
echo "[$(date -u +'%Y-%m-%dT%H:%M:%SZ')] Compiling release Android App Bundle (AAB)..." | tee -a "${LOG_FILE}"
flutter build appbundle \
    --release \
    --build-number="${BUILD_NUMBER}" \
    --build-name="${BUILD_NAME}" \
    >> "${LOG_FILE}" 2>&1

AAB_SOURCE="${WORKSPACE_DIR}/build/app/outputs/bundle/release/app-release.aab"

if [ -f "${AAB_SOURCE}" ]; then
    DEST_AAB="${OUTPUT_DIR}/app-release-${BUILD_NUMBER}.aab"
    cp "${AAB_SOURCE}" "${DEST_AAB}"
    SHA256=$(sha256sum "${DEST_AAB}" | awk '{print $1}')
    echo "${SHA256}  app-release-${BUILD_NUMBER}.aab" > "${DEST_AAB}.sha256"
    echo "[$(date -u +'%Y-%m-%dT%H:%M:%SZ')] Build SUCCESS! Artifact created: ${DEST_AAB} (SHA256: ${SHA256})" | tee -a "${LOG_FILE}"
    exit 0
else
    echo "[$(date -u +'%Y-%m-%dT%H:%M:%SZ')] Build FAILED: Output artifact ${AAB_SOURCE} was not generated." | tee -a "${LOG_FILE}"
    exit 1
fi
