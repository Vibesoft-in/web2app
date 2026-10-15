<?php

namespace App\Services\Build;

use App\Models\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class ApkCustomizer
{
    public function __construct(
        protected KeystoreService $keystoreService
    ) {}

    /**
     * Ensure the APK at the given local storage path is customized and signed for the given App.
     */
    public function ensureAppApk(App $app, string $storageRelativePath): string
    {
        $templatePath = resource_path('templates/app-release.apk');
        $fullPath = Storage::disk('local')->path($storageRelativePath);

        // If file doesn't exist or is a mock stub (< 1000 bytes), initialize from template
        if (! Storage::disk('local')->exists($storageRelativePath) || Storage::disk('local')->size($storageRelativePath) < 1000) {
            if (file_exists($templatePath)) {
                $dir = dirname($fullPath);
                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                copy($templatePath, $fullPath);
            }
        }

        if (file_exists($fullPath) && ! $this->isCustomizedForApp($app, $fullPath)) {
            $this->customizeForApp($app, $fullPath, $fullPath);
        }

        return $storageRelativePath;
    }

    /**
     * Check if the APK has already been customized for this app.
     */
    public function isCustomizedForApp(App $app, string $apkPath): bool
    {
        if (! file_exists($apkPath) || filesize($apkPath) < 1000) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($apkPath) !== true) {
            return false;
        }

        $settingsContent = $zip->getFromName('assets/settings.json');
        $zip->close();

        if (! $settingsContent) {
            return false;
        }

        $settings = json_decode($settingsContent, true);
        if (! is_array($settings)) {
            return false;
        }

        $targetUrl = $this->getTargetUrl($app);
        $currentUrl = $settings['sites'][0]['url'] ?? $settings['other'] ?? '';

        // If it points to default placeholder 192.168.2.127, it's not customized
        if (str_contains($currentUrl, '192.168.2.127') || str_contains($currentUrl, 'mikespub')) {
            return false;
        }

        $targetHost = parse_url($targetUrl, PHP_URL_HOST) ?: '';
        $currentHost = parse_url($currentUrl, PHP_URL_HOST) ?: '';

        return ! empty($currentHost) && ($currentHost === $targetHost);
    }

    /**
     * Customize APK with the app's website URL, branding, settings, and sign with keystore.
     */
    public function customizeForApp(App $app, string $sourceApkPath, string $outputApkPath): bool
    {
        if (! file_exists($sourceApkPath)) {
            Log::error("[ApkCustomizer] Source APK not found: {$sourceApkPath}");
            return false;
        }

        $tempDir = storage_path('app/temp_apk_' . uniqid('', true));
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $unsignedApk = $tempDir . DIRECTORY_SEPARATOR . 'unsigned.apk';
        $alignedApk = $tempDir . DIRECTORY_SEPARATOR . 'aligned.apk';
        $signedApk = $tempDir . DIRECTORY_SEPARATOR . 'signed.apk';
        $keystoreFile = $tempDir . DIRECTORY_SEPARATOR . 'app.keystore';

        try {
            copy($sourceApkPath, $unsignedApk);

            $zip = new ZipArchive();
            if ($zip->open($unsignedApk) !== true) {
                throw new \RuntimeException("Failed to open APK for customization: {$unsignedApk}");
            }

            // Remove previous signature files from META-INF
            for ($i = $zip->numFiles - 1; $i >= 0; $i--) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];
                if (str_starts_with($name, 'META-INF/') && (
                    str_ends_with($name, '.SF') ||
                    str_ends_with($name, '.RSA') ||
                    str_ends_with($name, '.MF') ||
                    str_ends_with($name, '.DSA') ||
                    str_ends_with($name, '.EC')
                )) {
                    $zip->deleteIndex($i);
                }
            }

            $targetUrl = $this->getTargetUrl($app);
            $parsed = parse_url($targetUrl);
            $host = $parsed['host'] ?? 'testing.livaan.in';
            $appName = $app->name ?: 'My Mobile App';

            // Build tailored settings.json
            $settings = [
                'sites' => [
                    [
                        'url' => $targetUrl,
                        'title' => $appName,
                    ],
                ],
                'other' => $targetUrl,
                'match' => [
                    ['host', 'equals', $host],
                    ['host', 'endsWith', '.' . $host],
                    ['url', 'startsWith', 'https://'],
                    ['url', 'startsWith', 'http://'],
                ],
                'skip' => [
                    ['path', 'endsWith', '.epub'],
                    ['path', 'endsWith', '.pdf'],
                ],
                'remote_debug' => true,
                'console_log' => false,
                'js_interface' => true,
                'context_menu' => false,
                'not_matching' => false,
                'local_sites' => false,
                'update_zip' => '',
                'web_settings' => [
                    'JavaScriptEnabled' => true,
                    'DomStorageEnabled' => true,
                    'DatabaseEnabled' => true,
                ],
                'source' => 'assets',
                'timestamp' => date('c'),
            ];

            $zip->addFromString('assets/settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Generate clean branded splash screen
            $indexHtml = $this->generateSplashHtml($appName, $targetUrl);
            $zip->addFromString('assets/web/index.html', $indexHtml);

            $zip->close();

            // Alignment and Signing
            $zipalign = $this->locateZipalign();
            $apksigner = $this->locateApksigner();
            $java = $this->locateJava();

            if ($java) {
                putenv("JAVA_HOME={$java['home']}");
                putenv("PATH={$java['bin_dir']};" . getenv('PATH'));
            }

            if ($zipalign && $apksigner) {
                // 1. Zipalign
                $alignCmd = "\"{$zipalign}\" -p -f 4 \"{$unsignedApk}\" \"{$alignedApk}\" 2>&1";
                exec($alignCmd, $alignOut, $alignCode);
                if ($alignCode !== 0 || ! file_exists($alignedApk)) {
                    Log::warning("[ApkCustomizer] Zipalign failed with code {$alignCode}: " . implode(' ', $alignOut));
                    // Fallback: use unsigned directly for apksigner
                    $alignedApk = $unsignedApk;
                }

                // 2. Fetch Keystore
                $decryptedKey = $this->keystoreService->getDecryptedKey($app);
                file_put_contents($keystoreFile, $decryptedKey['binary']);

                $storePass = $decryptedKey['store_password'];
                $alias = $decryptedKey['alias'];

                // 3. Apksigner (v1 + v2 + v3 signing)
                $signCmd = "\"{$apksigner}\" sign --v1-signing-enabled true --v2-signing-enabled true --v3-signing-enabled true --ks \"{$keystoreFile}\" --ks-pass pass:\"{$storePass}\" --ks-key-alias \"{$alias}\" --key-pass pass:\"{$storePass}\" --out \"{$signedApk}\" \"{$alignedApk}\" 2>&1";
                exec($signCmd, $signOut, $signCode);

                if ($signCode === 0 && file_exists($signedApk)) {
                    $outDir = dirname($outputApkPath);
                    if (! is_dir($outDir)) {
                        mkdir($outDir, 0755, true);
                    }
                    copy($signedApk, $outputApkPath);
                    Log::info("[ApkCustomizer] Successfully customized and signed APK for app #{$app->id} ({$app->name})");
                    return true;
                }

                Log::error("[ApkCustomizer] Apksigner failed with code {$signCode}: " . implode(' ', $signOut));
            } else {
                Log::warning("[ApkCustomizer] Zipalign or apksigner not available on host. Zipalign: " . ($zipalign ?: 'MISSING') . " | Apksigner: " . ($apksigner ?: 'MISSING'));
            }

            // Fallback if signing tools failed or not present
            $outDir = dirname($outputApkPath);
            if (! is_dir($outDir)) {
                mkdir($outDir, 0755, true);
            }
            copy($unsignedApk, $outputApkPath);
            return true;
        } catch (Throwable $e) {
            Log::error("[ApkCustomizer] Error customizing APK for app #{$app->id}: " . $e->getMessage());
            return false;
        } finally {
            $this->cleanupDirectory($tempDir);
        }
    }

    /**
     * Generate HTML splash screen that immediately redirects to target website.
     */
    public function generateSplashHtml(string $appName, string $targetUrl): string
    {
        $safeName = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="refresh" content="2;url={$safeUrl}">
    <title>{$safeName}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #090B12;
            color: #F8FAFC;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            overflow: hidden;
            text-align: center;
            padding: 24px;
        }
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 360px;
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo-box {
            width: 76px;
            height: 76px;
            border-radius: 20px;
            background: linear-gradient(135deg, #635BFF 0%, #4338CA 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 12px 28px -6px rgba(99, 91, 255, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.1);
        }
        .logo-icon {
            font-size: 36px;
            line-height: 1;
        }
        .app-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.4px;
            color: #F8FAFC;
            margin-bottom: 8px;
        }
        .app-subtitle {
            font-size: 13px;
            color: #A5B4CF;
            margin-bottom: 28px;
        }
        .spinner {
            width: 34px;
            height: 34px;
            border: 3px solid rgba(99, 91, 255, 0.18);
            border-top-color: #635BFF;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-bottom: 20px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .status-text {
            font-size: 12px;
            font-weight: 500;
            color: #7E8B9B;
            letter-spacing: 0.2px;
        }
        .retry-link {
            display: inline-block;
            margin-top: 24px;
            padding: 8px 18px;
            font-size: 12px;
            font-weight: 600;
            color: #A5B4CF;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .retry-link:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-box">
            <span class="logo-icon">📱</span>
        </div>
        <h1 class="app-title">{$safeName}</h1>
        <p class="app-subtitle">Connecting to live application...</p>
        <div class="spinner"></div>
        <div class="status-text">Starting session...</div>
        <a href="{$safeUrl}" class="retry-link" id="manualLink" style="display: none;">Tap here if not redirected</a>
    </div>

    <script>
        (function() {
            var target = "{$safeUrl}";
            // Show manual fallback after 4 seconds if network is slow
            setTimeout(function() {
                var btn = document.getElementById('manualLink');
                if (btn) btn.style.display = 'inline-block';
            }, 4000);

            // Immediate redirect into the website
            try {
                window.location.replace(target);
            } catch(e) {
                window.location.href = target;
            }
        })();
    </script>
</body>
</html>
HTML;
    }

    /**
     * Resolve the target website URL for the app.
     */
    public function getTargetUrl(App $app): string
    {
        $conn = $app->connection;
        $url = $conn?->site_url;

        if (empty($url)) {
            // Check app configs
            $config = $app->configs()->latest()->first();
            $url = $config?->config['app']['site_url'] ?? null;
        }

        if (empty($url)) {
            $url = 'https://testing.livaan.in/';
        }

        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    /**
     * Locate zipalign executable.
     */
    public function locateZipalign(): ?string
    {
        $candidates = [
            config('services.android.zipalign'),
            'C:\\Users\\msans\\AppData\\Local\\Android\\Sdk\\build-tools\\36.0.0\\zipalign.exe',
            'C:\\Users\\msans\\AppData\\Local\\Android\\Sdk\\build-tools\\35.0.0\\zipalign.exe',
            'C:\\Users\\msans\\AppData\\Local\\Android\\Sdk\\build-tools\\34.0.0\\zipalign.exe',
            getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA') . '\\Android\\Sdk\\build-tools\\36.0.0\\zipalign.exe' : null,
            '/opt/android-sdk/cmdline-tools/latest/bin/zipalign',
            '/opt/android-sdk/build-tools/34.0.0/zipalign',
            '/opt/android-sdk/build-tools/33.0.0/zipalign',
            '/usr/bin/zipalign',
        ];

        foreach (array_filter($candidates) as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // Test if in PATH
        exec('zipalign -h 2>&1', $out, $code);
        if ($code === 2 || $code === 0) {
            return 'zipalign';
        }

        return null;
    }

    /**
     * Locate apksigner executable.
     */
    public function locateApksigner(): ?string
    {
        $candidates = [
            config('services.android.apksigner'),
            'C:\\Users\\msans\\AppData\\Local\\Android\\Sdk\\build-tools\\36.0.0\\apksigner.bat',
            'C:\\Users\\msans\\AppData\\Local\\Android\\Sdk\\build-tools\\35.0.0\\apksigner.bat',
            'C:\\Users\\msans\\AppData\\Local\\Android\\Sdk\\build-tools\\34.0.0\\apksigner.bat',
            getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA') . '\\Android\\Sdk\\build-tools\\36.0.0\\apksigner.bat' : null,
            '/opt/android-sdk/cmdline-tools/latest/bin/apksigner',
            '/opt/android-sdk/build-tools/34.0.0/apksigner',
            '/opt/android-sdk/build-tools/33.0.0/apksigner',
            '/usr/bin/apksigner',
        ];

        foreach (array_filter($candidates) as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // Test if in PATH
        exec('apksigner --version 2>&1', $out, $code);
        if ($code === 0) {
            return 'apksigner';
        }

        return null;
    }

    /**
     * Locate Java installation.
     *
     * @return array{home: string, bin_dir: string}|null
     */
    public function locateJava(): ?array
    {
        $candidates = [
            'C:\\Program Files\\Android\\Android Studio\\jbr',
            getenv('JAVA_HOME'),
            '/usr/lib/jvm/java-17-openjdk-amd64',
            '/usr/lib/jvm/java-11-openjdk-amd64',
        ];

        foreach (array_filter($candidates) as $cand) {
            $bin = $cand . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'java.exe' : 'java');
            if (file_exists($bin)) {
                return [
                    'home' => $cand,
                    'bin_dir' => $cand . DIRECTORY_SEPARATOR . 'bin',
                ];
            }
        }

        return null;
    }

    /**
     * Recursively remove a temporary directory.
     */
    protected function cleanupDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->cleanupDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
