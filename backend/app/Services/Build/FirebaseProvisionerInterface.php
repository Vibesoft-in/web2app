<?php

namespace App\Services\Build;

use App\Models\App;

interface FirebaseProvisionerInterface
{
    /**
     * Register Android app in Firebase, add SHA fingerprints, and fetch google-services.json content.
     *
     * @return array{
     *     project_id: string,
     *     app_id: string,
     *     google_services_json: string,
     *     registered: bool
     * }
     */
    public function provisionAndroidApp(App $app, string $sha1, ?string $sha256 = null): array;
}
