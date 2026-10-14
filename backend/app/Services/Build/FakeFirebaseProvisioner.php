<?php

namespace App\Services\Build;

use App\Models\App;
use Illuminate\Support\Str;

class FakeFirebaseProvisioner implements FirebaseProvisionerInterface
{
    /**
     * Provision fake Firebase Android app and return valid google-services.json fixture.
     */
    public function provisionAndroidApp(App $app, string $sha1, ?string $sha256 = null): array
    {
        $projectId = $app->firebase_project_id ?: 'wia-app-'.Str::slug($app->name, '-');
        $cleanSha1 = str_replace(':', '', strtolower($sha1));
        $appId = $app->firebase_app_id ?: "1:987654321098:android:{$app->id}".substr($cleanSha1, 0, 10);
        $projectNumber = '987654321098';

        $config = [
            'project_info' => [
                'project_number' => $projectNumber,
                'project_id' => $projectId,
                'storage_bucket' => "{$projectId}.appspot.com",
            ],
            'client' => [
                [
                    'client_info' => [
                        'mobilesdk_app_id' => $appId,
                        'android_client_info' => [
                            'package_name' => $app->package_name,
                        ],
                    ],
                    'oauth_client' => [
                        [
                            'client_id' => "{$projectNumber}-mock.apps.googleusercontent.com",
                            'client_type' => 1,
                            'android_info' => [
                                'package_name' => $app->package_name,
                                'certificate_hash' => $cleanSha1,
                            ],
                        ],
                    ],
                    'api_key' => [
                        [
                            'current_key' => 'AIzaSyMockKeyForAppBuildDevelopment'.Str::random(8),
                        ],
                    ],
                    'services' => [
                        'appinvite_service' => [
                            'other_platform_oauth_client' => [],
                        ],
                    ],
                ],
            ],
            'configuration_version' => '1',
        ];

        $googleServicesJson = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';

        // Persist project and app ID on App model if not present
        if (empty($app->firebase_project_id) || empty($app->firebase_app_id)) {
            $app->firebase_project_id = $projectId;
            $app->firebase_app_id = $appId;
            $app->save();
        }

        return [
            'project_id' => $projectId,
            'app_id' => $appId,
            'google_services_json' => $googleServicesJson,
            'registered' => true,
        ];
    }
}
