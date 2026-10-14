<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Services\Connectors\ConnectorFactory;
use App\Services\Security\CredentialVault;
use Illuminate\Http\JsonResponse;

class PreviewController extends Controller
{
    public function __construct(
        protected ConnectorFactory $connectorFactory,
        protected CredentialVault $credentialVault,
    ) {}

    /**
     * Return live preview data including real store products from the connector and active config.
     */
    public function show(App $app): JsonResponse
    {
        $connection = $app->connection;

        if (! $connection || $connection->status !== 'verified' || empty($connection->credentials)) {
            return response()->json([
                'error' => [
                    'code' => 'CONNECTION_NOT_VERIFIED',
                    'message' => 'Store connection must be verified to fetch preview data.',
                ],
            ], 422);
        }

        /** @var array<string, mixed> $credentials */
        $credentials = (array) $connection->credentials;

        // Make connector for store platform
        $connector = $this->connectorFactory->make(
            $connection->platform,
            $connection->site_url,
            $credentials
        );

        // Get latest config (published prioritized, then draft)
        $configModel = $app->configs()->where('state', 'published')->latest('version')->first()
            ?? $app->configs()->where('state', 'draft')->latest('version')->first();

        $activeConfig = $configModel ? $configModel->config : null;

        try {
            // Fetch real catalog data from the store
            $productsResult = $connector->getProducts(['per_page' => 8]);
            $categories = $connector->getCategories();

            return response()->json([
                'data' => [
                    'preview_mode' => 'live_store',
                    'store_url' => $connection->site_url,
                    'config' => $activeConfig,
                    'products' => $productsResult->products,
                    'categories' => $categories,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => [
                    'code' => 'STORE_UNREACHABLE',
                    'message' => 'Store catalog is unreachable or credentials have been revoked: '.$e->getMessage(),
                ],
                'data' => [
                    'preview_mode' => 'fallback',
                    'store_url' => $connection->site_url,
                    'config' => $activeConfig,
                    'products' => [],
                    'categories' => [],
                ],
            ], 502);
        }
    }
}
