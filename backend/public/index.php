<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Auto-provision .env on first run if missing
if (! file_exists(__DIR__.'/../.env')) {
    $defaultEnv = <<<'ENV'
APP_NAME="WebIntoApp"
APP_ENV=production
APP_KEY=base64:s+DmVzDI6VBz+UeVwCs7MIz4qd9Nl3EmgdYMeb0/y54=
APP_DEBUG=true
APP_URL=https://ask.aktuhub.in
APP_TIMEZONE=UTC
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=daily
LOG_LEVEL=error
LOG_DAILY_DAYS=14

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u537268535_web2app
DB_USERNAME=u537268535_web2app
DB_PASSWORD=Vibesoft123

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

CACHE_STORE=file
CACHE_PREFIX=""

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@aktuhub.in"
MAIL_FROM_NAME="${APP_NAME}"

RAZORPAY_KEY=rzp_live_placeholder
RAZORPAY_SECRET=secret_placeholder
RAZORPAY_WEBHOOK_SECRET=webhook_placeholder

WHATSAPP_TOKEN=token_placeholder
WHATSAPP_PHONE_NUMBER_ID=id_placeholder
WHATSAPP_BUSINESS_ACCOUNT_ID=account_placeholder
WHATSAPP_WEBHOOK_VERIFY_TOKEN=verify_token_placeholder

FIREBASE_CREDENTIALS=null
ENV;
    @file_put_contents(__DIR__.'/../.env', $defaultEnv);
}

// Ensure necessary storage directories exist
$storageDirs = [
    __DIR__.'/../storage/app',
    __DIR__.'/../storage/app/public',
    __DIR__.'/../storage/framework/cache/data',
    __DIR__.'/../storage/framework/sessions',
    __DIR__.'/../storage/framework/views',
    __DIR__.'/../storage/logs',
    __DIR__.'/../bootstrap/cache',
];
foreach ($storageDirs as $sDir) {
    if (! is_dir($sDir)) {
        @mkdir($sDir, 0775, true);
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
