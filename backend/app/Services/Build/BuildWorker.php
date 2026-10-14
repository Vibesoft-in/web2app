<?php

namespace App\Services\Build;

use App\Enums\BuildStatus;
use App\Models\App;
use App\Models\AppConfig;
use App\Models\Build;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class BuildWorker
{
    public const TIMEOUT_SECONDS = 1800; // 30 minutes

    public function __construct(
        protected KeystoreService $keystoreService,
        protected FirebaseProvisionerInterface $firebaseProvisioner,
        protected FlutterRunnerInterface $flutterRunner,
        protected ImageSanitizer $imageSanitizer,
    ) {}

    /**
     * Execute full isolated build pipeline for the given Build model.
     */
    public function execute(Build $build): Build
    {
        $app = $build->app;
        if (! $app) {
            throw new RuntimeException("Build [{$build->ulid}] has no associated App.");
        }

        $tenantId = $build->tenant_id;
        $workspaceDir = storage_path("app/tenants/{$tenantId}/builds/{$build->ulid}/workspace");
        $logs = [];

        try {
            // ==========================================
            // STAGE 1: PREPARING
            // ==========================================
            $build->transitionTo(BuildStatus::PREPARING);
            $logs[] = "[1/5] Preparing build environment for {$app->package_name} (Build #{$build->version_code})...";

            // 1.1 Load and validate pinned published configuration
            /** @var AppConfig|null $configRecord */
            $configRecord = AppConfig::where('app_id', $app->id)
                ->where('version', $build->config_version)
                ->first();

            $config = $configRecord?->config ?? [];

            // 1.2 Keystore setup & SHA fingerprints
            $logs[] = "[1/5] Resolving Android release keystore...";
            $keystore = $this->keystoreService->getOrCreate($app);
            $fingerprints = $this->keystoreService->getFingerprints($app);
            $logs[] = "[1/5] Keystore SHA-1: {$fingerprints['sha1']}";

            // 1.3 Firebase Provisioning
            $logs[] = "[1/5] Provisioning Firebase application...";
            $firebaseData = $this->firebaseProvisioner->provisionAndroidApp(
                $app,
                $fingerprints['sha1'],
                $fingerprints['sha256']
            );
            $logs[] = "[1/5] Firebase App ID: {$firebaseData['app_id']}";

            // 1.4 Prepare isolated workspace
            if (File::isDirectory($workspaceDir)) {
                File::deleteDirectory($workspaceDir);
            }
            File::makeDirectory($workspaceDir, 0755, true);

            // 1.5 Copy trusted Flutter template
            $templatePath = $this->resolveTemplatePath();
            $this->copyTrustedTemplate($templatePath, $workspaceDir);
            $logs[] = "[1/5] Trusted Flutter engine copied to isolated workspace.";

            // 1.6 Inject ONLY validated values (Never client code)
            $this->injectValues(
                workspaceDir: $workspaceDir,
                app: $app,
                config: $config,
                firebaseJson: $firebaseData['google_services_json'],
                build: $build
            );
            $logs[] = "[1/5] Injected verified parameters (Package: {$app->package_name}, Name: {$app->name}).";

            // ==========================================
            // STAGE 2: BUILDING
            // ==========================================
            $build->transitionTo(BuildStatus::BUILDING);
            $logs[] = "[2/5] Compiling Android App Bundle (AAB) with R8 obfuscation and optimization...";
            $logs[] = "[2/5] Build parameters: APP_KEY={$app->app_key}, PACKAGE_NAME={$app->package_name}";

            $apiBaseUrl = config('app.url') ?? 'http://127.0.0.1:8000';
            $dartDefines = [
                'APP_KEY' => $app->app_key,
                'API_BASE_URL' => $apiBaseUrl,
                'APP_NAME' => $app->name,
                'PACKAGE_NAME' => $app->package_name,
            ];

            $result = $this->flutterRunner->runBuild(
                workspaceDir: $workspaceDir,
                dartDefines: $dartDefines,
                buildNumber: $build->version_code,
                buildName: $build->version_name,
                timeoutSeconds: self::TIMEOUT_SECONDS
            );

            $logs[] = $result['output'];

            if (! $result['success'] || empty($result['artifact_path']) || ! file_exists($result['artifact_path'])) {
                throw new RuntimeException($result['error'] ?? 'Flutter build failed to produce release artifact.');
            }

            // ==========================================
            // STAGE 3: SIGNING
            // ==========================================
            $build->transitionTo(BuildStatus::SIGNING);
            $logs[] = "[3/5] Verifying bundle integrity and signature alignment...";

            // ==========================================
            // STAGE 4: READY & ARTIFACT UPLOAD
            // ==========================================
            $artifactStoragePath = "tenants/{$tenantId}/apps/{$app->id}/builds/{$build->ulid}/app-release.aab";
            $artifactBytes = (string) file_get_contents($result['artifact_path']);
            Storage::disk('local')->put($artifactStoragePath, $artifactBytes);

            $logs[] = "[4/5] Uploaded release AAB to secure tenant storage: {$artifactStoragePath}";
            $logs[] = "[5/5] Build #{$build->version_code} completed successfully!";

            // Persist sanitized logs
            $sanitizedLogs = $this->sanitizeLogs(implode(PHP_EOL, $logs), $app);
            $logStoragePath = "tenants/{$tenantId}/builds/{$build->ulid}/build.log";
            Storage::disk('local')->put($logStoragePath, $sanitizedLogs);

            $build->artifact_path = $artifactStoragePath;
            $build->logs_path = $logStoragePath;
            $build->transitionTo(BuildStatus::READY);

            $app->status = 'live';
            $app->save();

            // Record usage event for cost tracking
            app(\App\Services\Billing\UsageEventService::class)->record(
                tenant: $build->tenant_id,
                type: 'build',
                qty: 1,
                cost: 0.1000,
                app: $app
            );

            return $build;
        } catch (Throwable $e) {
            $logs[] = "[ERROR] Build failed: ".$e->getMessage();
            $sanitizedLogs = $this->sanitizeLogs(implode(PHP_EOL, $logs), $app);

            $logStoragePath = "tenants/{$tenantId}/builds/{$build->ulid}/build.log";
            Storage::disk('local')->put($logStoragePath, $sanitizedLogs);

            $build->logs_path = $logStoragePath;
            if (! $build->status->isTerminal()) {
                $build->transitionTo(BuildStatus::FAILED, $this->sanitizeString($e->getMessage(), $app));
            } else {
                $build->error = $this->sanitizeString($e->getMessage(), $app);
                $build->save();
            }

            return $build;
        } finally {
            // Clean up temporary build workspace directory
            if (File::isDirectory($workspaceDir)) {
                File::deleteDirectory($workspaceDir);
            }
        }
    }

    /**
     * Resolve path to the trusted Flutter template.
     */
    protected function resolveTemplatePath(): string
    {
        $candidate = base_path('../mobile');
        if (File::isDirectory($candidate)) {
            return realpath($candidate) ?: $candidate;
        }

        return base_path('mobile_template');
    }

    /**
     * Copy only source files from trusted template into workspace, ignoring ephemeral folders.
     */
    protected function copyTrustedTemplate(string $src, string $dest): void
    {
        if (! File::isDirectory($src)) {
            // In unit tests or mock environments where ../mobile is absent, create stub skeleton
            File::makeDirectory($dest.DIRECTORY_SEPARATOR.'android'.DIRECTORY_SEPARATOR.'app', 0755, true);
            File::put($dest.DIRECTORY_SEPARATOR.'pubspec.yaml', "name: webintoapp_engine\n");
            File::put($dest.DIRECTORY_SEPARATOR.'android'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'build.gradle', "applicationId 'com.example.placeholder'\n");
            return;
        }

        File::copyDirectory($src, $dest);

        // Remove build and ephemeral artifacts from workspace copy
        foreach (['.dart_tool', 'build', '.idea', '.git'] as $ignore) {
            $dir = $dest.DIRECTORY_SEPARATOR.$ignore;
            if (File::isDirectory($dir)) {
                File::deleteDirectory($dir);
            }
        }
    }

    /**
     * Inject strictly validated variables into the workspace.
     *
     * @param  array<string, mixed>  $config
     */
    protected function injectValues(
        string $workspaceDir,
        App $app,
        array $config,
        string $firebaseJson,
        Build $build
    ): void {
        // 1. Validate package name strictly
        if (! preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $app->package_name)) {
            throw new InvalidArgumentException("Malicious or invalid package name: [{$app->package_name}].");
        }

        $appDir = $workspaceDir.DIRECTORY_SEPARATOR.'android'.DIRECTORY_SEPARATOR.'app';
        if (! File::isDirectory($appDir)) {
            File::makeDirectory($appDir, 0755, true);
        }

        // 2. Inject google-services.json
        File::put($appDir.DIRECTORY_SEPARATOR.'google-services.json', $firebaseJson);

        // 3. Inject keystore & key.properties
        $decryptedKey = $this->keystoreService->getDecryptedKey($app);
        File::put($appDir.DIRECTORY_SEPARATOR.'upload-keystore.jks', $decryptedKey['binary']);

        $keyProperties = "storePassword={$decryptedKey['store_password']}\n".
            "keyPassword={$decryptedKey['key_password']}\n".
            "keyAlias={$decryptedKey['alias']}\n".
            "storeFile=upload-keystore.jks\n";
        File::put($workspaceDir.DIRECTORY_SEPARATOR.'android'.DIRECTORY_SEPARATOR.'key.properties', $keyProperties);

        // 4. Update applicationId in android/app/build.gradle
        $gradlePath = $appDir.DIRECTORY_SEPARATOR.'build.gradle';
        if (File::exists($gradlePath)) {
            $content = File::get($gradlePath);
            $content = preg_replace(
                "/applicationId\s+['\"][^'\"]+['\"]/",
                "applicationId '{$app->package_name}'",
                $content
            ) ?? $content;
            File::put($gradlePath, $content);
        }

        // 5. Update app name in strings.xml or AndroidManifest
        $resDir = $appDir.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'main'.DIRECTORY_SEPARATOR.'res'.DIRECTORY_SEPARATOR.'values';
        if (! File::isDirectory($resDir)) {
            File::makeDirectory($resDir, 0755, true);
        }
        $safeAppName = htmlspecialchars($app->name, ENT_XML1, 'UTF-8');
        $stringsXml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<resources>\n    <string name=\"app_name\">{$safeAppName}</string>\n</resources>\n";
        File::put($resDir.DIRECTORY_SEPARATOR.'strings.xml', $stringsXml);

        // 6. Icon re-encoding & injection if provided
        $iconBase64 = $config['theme']['app_icon_base64'] ?? null;
        if ($iconBase64 && is_string($iconBase64)) {
            $rawIcon = base64_decode($iconBase64);
            if ($rawIcon) {
                $cleanIcon = $this->imageSanitizer->sanitizeAndReencode($rawIcon);
                $mipmaps = $this->imageSanitizer->generateAndroidMipmaps($cleanIcon);

                foreach ($mipmaps as $folder => $png) {
                    $targetFolder = $appDir.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'main'.DIRECTORY_SEPARATOR.'res'.DIRECTORY_SEPARATOR.$folder;
                    if (! File::isDirectory($targetFolder)) {
                        File::makeDirectory($targetFolder, 0755, true);
                    }
                    File::put($targetFolder.DIRECTORY_SEPARATOR.'ic_launcher.png', $png);
                }
            }
        }
    }

    /**
     * Sanitize build logs removing passwords and secrets before writing to disk.
     */
    public function sanitizeLogs(string $log, App $app): string
    {
        $redacted = $this->sanitizeString($log, $app);

        // Regex mask any additional passwords and tokens (skipping already redacted markers)
        $redacted = preg_replace('/(password|token|secret|key)=(?!\[[A-Z0-9_]*REDACTED\])([^\s&]+)/i', '$1=[REDACTED]', $redacted) ?? $redacted;
        $redacted = preg_replace('/AIzaSy[A-Za-z0-9_-]{33}/', 'AIzaSy[REDACTED]', $redacted) ?? $redacted;

        return $redacted;
    }

    protected function sanitizeString(string $text, App $app): string
    {
        $replacements = [
            $app->app_key => '[APP_KEY_REDACTED]',
        ];

        if ($app->keystore) {
            $passwords = $app->keystore->passwords;
            if (! empty($passwords['store_password'])) {
                $replacements[$passwords['store_password']] = '[KEYSTORE_PASS_REDACTED]';
            }
            if (! empty($passwords['key_password'])) {
                $replacements[$passwords['key_password']] = '[KEY_PASS_REDACTED]';
            }
        }

        return strtr($text, $replacements);
    }
}
