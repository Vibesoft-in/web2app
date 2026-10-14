#!/usr/bin/env bash
# ==============================================================================
# Production Health Probe CLI Script: WebIntoApp
# ==============================================================================
set -euo pipefail

ENDPOINT="${1:-http://127.0.0.1:8000/healthz}"
TIMEOUT_SECS="${2:-5}"

HTTP_RESPONSE=$(curl -s -o /tmp/health_response.json -w "%{http_code}" --max-time "${TIMEOUT_SECS}" "${ENDPOINT}" || echo "000")

if [ "${HTTP_RESPONSE}" -eq 200 ]; then
    echo "[OK] System is Healthy (HTTP 200) - Endpoint: ${ENDPOINT}"
    cat /tmp/health_response.json
    echo ""
    rm -f /tmp/health_response.json
    exit 0
else
    echo "[CRITICAL] Health check FAILED! HTTP Status: ${HTTP_RESPONSE} - Endpoint: ${ENDPOINT}"
    if [ -f /tmp/health_response.json ]; then
        cat /tmp/health_response.json
        echo ""
        rm -f /tmp/health_response.json
    fi
    exit 1
fi
