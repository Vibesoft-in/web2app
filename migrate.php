<?php
/**
 * Root Bridge for Migration Runner
 * Directs execution to backend/public/migrate.php
 */

if (file_exists(__DIR__ . '/backend/public/migrate.php')) {
    require __DIR__ . '/backend/public/migrate.php';
} else {
    http_response_code(404);
    echo "Migration runner not found in backend/public/migrate.php";
}
