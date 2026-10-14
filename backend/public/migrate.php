<?php
/**
 * Standalone Production Database Migration & Seeding Runner
 * WebIntoApp - ask.aktuhub.in
 */

declare(strict_types=1);
use App\Http\Controllers\HealthController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;

$secret = 'Vibesoft123';
$providedKey = $_GET['key'] ?? '';

if ($providedKey !== $secret) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;text-align:center;padding:50px;">'
        .'<h1 style="color:#e11d48;">403 Forbidden</h1>'
        .'<p>Unauthorized access. Please pass the secret key:</p>'
        .'<code>?key=Vibesoft123</code>'
        .'</body></html>';
    exit;
}

header('Content-Type: text/html; charset=utf-8');

$autoloadPath = __DIR__.'/../vendor/autoload.php';
$bootstrapPath = __DIR__.'/../bootstrap/app.php';

if (! file_exists($autoloadPath)) {
    http_response_code(500);
    echo '<h2 style="color:red;">Error: vendor/autoload.php not found.</h2>';
    echo '<p>Please ensure vendor.zip was extracted into backend/.</p>';
    exit;
}

require $autoloadPath;

/** @var Application $app */
$app = require_once $bootstrapPath;

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration & Seeder</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 40px 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; background: #1e293b; border-radius: 12px; padding: 32px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { color: #6366f1; margin-top: 0; font-size: 24px; }
        pre { background: #090d16; border: 1px solid #334155; border-radius: 8px; padding: 16px; overflow-x: auto; color: #38bdf8; font-size: 13px; }
        .success { color: #4ade80; font-weight: bold; }
        .error { color: #f87171; font-weight: bold; }
        .btn { display: inline-block; background: #6366f1; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 20px; }
        .btn:hover { background: #4f46e5; }
    </style>
</head>
<body>
<div class="container">
    <h1>WebIntoApp Database Migration & Seeder</h1>
    <p>Running Laravel database migrations and seeders on <strong>u537268535_web2app</strong>...</p>

    <h3>1. Migration Output:</h3>
    <pre><?php
    try {
        Artisan::call('migrate', ['--force' => true]);
        echo htmlspecialchars(Artisan::output() ?: "All migrations are already up to date.\n");
    } catch (Throwable $e) {
        echo '<span class="error">Migration Error: '.htmlspecialchars($e->getMessage())."</span>\n";
    }
?></pre>

    <h3>2. Seeder Output:</h3>
    <pre><?php
try {
    Artisan::call('db:seed', ['--force' => true]);
    echo htmlspecialchars(Artisan::output() ?: "Themes and seeders applied successfully.\n");
} catch (Throwable $e) {
    echo '<span class="error">Seeder Error: '.htmlspecialchars($e->getMessage())."</span>\n";
}
?></pre>

    <p class="success">Migration process finished!</p>

    <h3>3. Environment & Storage Diagnostics:</h3>
    <pre><?php
    $envPath = base_path('.env');
$rootEnvPath = dirname(base_path()).'/.env';
echo 'Base Path: '.base_path()."\n";
echo 'backend/.env exists: '.(file_exists($envPath) ? 'YES' : 'NO')."\n";
echo 'root/.env exists: '.(file_exists($rootEnvPath) ? 'YES' : 'NO')."\n";

// Auto self-heal: if backend/.env is missing but root/.env exists, copy it!
if (! file_exists($envPath) && file_exists($rootEnvPath)) {
    copy($rootEnvPath, $envPath);
    echo "--> AUTO-FIX: Copied root/.env to backend/.env!\n";
}

echo 'APP_KEY: '.(config('app.key') ? substr(config('app.key'), 0, 15).'...' : 'MISSING / EMPTY!')."\n";
echo 'APP_ENV: '.config('app.env')."\n";
echo 'APP_DEBUG: '.(config('app.debug') ? 'true' : 'false')."\n";
echo 'DB Connection: '.config('database.default')."\n";
echo 'Session Driver: '.config('session.driver')."\n";
echo 'Cache Store: '.config('cache.default')."\n\n";

// Check storage writable permissions and auto-fix
$dirs = [
    storage_path(),
    storage_path('app'),
    storage_path('app/public'),
    storage_path('framework'),
    storage_path('framework/cache'),
    storage_path('framework/cache/data'),
    storage_path('framework/sessions'),
    storage_path('framework/views'),
    storage_path('logs'),
    base_path('bootstrap/cache'),
];

echo "Directory Writable Checks:\n";
foreach ($dirs as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @chmod($dir, 0775);
    $writable = is_writable($dir);
    echo '- '.str_replace(base_path(), '', $dir).': '.($writable ? 'OK (writable)' : 'ERROR (NOT writable!)')."\n";
}
?></pre>

    <h3>4. Direct Health Check Execution:</h3>
    <pre><?php
try {
    $controller = new HealthController;
    $healthResponse = $controller->check();
    echo 'Status: '.$healthResponse->getStatusCode()."\n";
    echo json_encode(json_decode($healthResponse->getContent()), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo '<span class="error">HealthController Exception: '.htmlspecialchars($e->getMessage())."\n".htmlspecialchars($e->getTraceAsString()).'</span>';
}
?></pre>

    <h3>5. Direct View Test:</h3>
    <pre><?php
try {
    $rendered = view('welcome')->render();
    echo 'welcome.blade.php rendered successfully! ('.strlen($rendered)." bytes)\n";
} catch (Throwable $e) {
    echo '<span class="error">View Error: '.htmlspecialchars($e->getMessage())."\n".htmlspecialchars($e->getTraceAsString()).'</span>';
}
?></pre>


    <a href="/healthz" class="btn">View System Health Status &rarr;</a>
    <a href="/" class="btn" style="background:#334155; margin-left: 10px;">Go to App Home &rarr;</a>
</div>
</body>
</html>

