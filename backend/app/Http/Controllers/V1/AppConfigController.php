<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    /**
     * Return white-label app configuration for the mobile client.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');

        // Retrieve published config or fall back to latest draft
        /** @var AppConfig|null $appConfig */
        $appConfig = AppConfig::where('app_id', $app->id)
            ->where('state', 'published')
            ->orderByDesc('version')
            ->first();

        if (! $appConfig) {
            $appConfig = AppConfig::where('app_id', $app->id)
                ->orderByDesc('version')
                ->first();
        }

        /** @var array<string, mixed> $configData */
        $configData = (array) ($appConfig?->config ?? []);

        // Ensure store site url domain is added to webview allowed hosts
        $allowedHosts = is_array($configData['webview_allowed_hosts'] ?? null) ? $configData['webview_allowed_hosts'] : [];
        if ($app->connection && ! empty($app->connection->site_url)) {
            $host = parse_url($app->connection->site_url, PHP_URL_HOST);
            if ($host && ! in_array($host, $allowedHosts, true)) {
                $allowedHosts[] = $host;
            }
        }
        $configData['webview_allowed_hosts'] = $allowedHosts;

        // Ensure basic metadata
        /** @var array<string, mixed> $appData */
        $appData = isset($configData['app']) && is_array($configData['app']) ? $configData['app'] : [];
        $appData['name'] = $appData['name'] ?? $app->name;
        $appData['currency'] = $appData['currency'] ?? 'INR';
        $appData['min_version'] = $appData['min_version'] ?? '1.0.0';
        $appData['maintenance'] = $appData['maintenance'] ?? false;
        $configData['app'] = $appData;

        return response()->json([
            'data' => $configData,
        ]);

    }
}
