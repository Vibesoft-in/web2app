<?php
/**
 * Forward root requests to Laravel backend/public
 */
$publicIndex = __DIR__ . '/backend/public/index.php';

if (file_exists($publicIndex)) {
    require_once $publicIndex;
} else {
    header('HTTP/1.1 503 Service Unavailable');
    echo 'Application backend is being deployed. Please check back shortly.';
}
