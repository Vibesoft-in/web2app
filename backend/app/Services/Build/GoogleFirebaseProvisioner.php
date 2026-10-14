<?php

namespace App\Services\Build;

use App\Models\App;
use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleFirebaseProvisioner implements FirebaseProvisionerInterface
{
    public function __construct(
        protected ?FakeFirebaseProvisioner $fallback = null,
    ) {
        $this->fallback = $fallback ?? new FakeFirebaseProvisioner();
    }

    /**
     * Register Android app via Firebase Management REST API.
     */
    public function provisionAndroidApp(App $app, string $sha1, ?string $sha256 = null): array
    {
        // 1. Fetch Firebase integration credentials for tenant/app
        /** @var Integration|null $integration */
        $integration = Integration::where('app_id', $app->id)
            ->where('type', 'firebase')
            ->first();

        $credentials = $integration?->credentials ? json_decode((string) $integration->credentials, true) : null;
        $accessToken = $credentials['access_token'] ?? config('services.firebase.access_token');
        $projectId = $credentials['project_id'] ?? config('services.firebase.project_id') ?? $app->firebase_project_id;

        // If credentials are absent or environment is testing/local, use fallback provisioner
        if (empty($accessToken) || empty($projectId) || app()->environment(['local', 'testing'])) {
            return $this->fallback->provisionAndroidApp($app, $sha1, $sha256);
        }

        try {
            // 2. Register Android app in Firebase Project
            $createResp = Http::withToken($accessToken)
                ->timeout(15)
                ->post("https://firebase.googleapis.com/v1beta1/projects/{$projectId}/androidApps", [
                    'packageName' => $app->package_name,
                    'displayName' => $app->name,
                ]);

            if (! $createResp->successful() && $createResp->status() !== 409) {
                throw new RuntimeException('Failed to register Firebase Android App: '.$createResp->body());
            }

            // Extract App ID or lookup existing app
            $appId = (string) ($createResp->json('appId') ?? $app->firebase_app_id);

            // 3. Add SHA-1 fingerprint
            if ($appId && $sha1) {
                Http::withToken($accessToken)
                    ->timeout(10)
                    ->post("https://firebase.googleapis.com/v1beta1/projects/{$projectId}/androidApps/{$appId}/sha", [
                        'shaHash' => str_replace(':', '', $sha1),
                        'certType' => 'SHA_1',
                    ]);
            }

            // 4. Add SHA-256 fingerprint if provided
            if ($appId && $sha256) {
                Http::withToken($accessToken)
                    ->timeout(10)
                    ->post("https://firebase.googleapis.com/v1beta1/projects/{$projectId}/androidApps/{$appId}/sha", [
                        'shaHash' => str_replace(':', '', $sha256),
                        'certType' => 'SHA_256',
                    ]);
            }

            // 5. Fetch google-services.json configuration
            $configResp = Http::withToken($accessToken)
                ->timeout(15)
                ->get("https://firebase.googleapis.com/v1beta1/projects/{$projectId}/androidApps/{$appId}/config");

            if (! $configResp->successful()) {
                throw new RuntimeException('Failed to fetch Firebase config: '.$configResp->body());
            }

            $configFileContents = (string) base64_decode((string) $configResp->json('configFileContents'));

            $app->firebase_project_id = $projectId;
            $app->firebase_app_id = $appId;
            $app->save();

            return [
                'project_id' => $projectId,
                'app_id' => $appId,
                'google_services_json' => $configFileContents,
                'registered' => true,
            ];
        } catch (\Exception $e) {
            // If live API fails and not in strict production, gracefully fallback
            if (! app()->environment('production')) {
                return $this->fallback->provisionAndroidApp($app, $sha1, $sha256);
            }
            throw new RuntimeException('Firebase provisioning failed: '.$e->getMessage(), 0, $e);
        }
    }
}
