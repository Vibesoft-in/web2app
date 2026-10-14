<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connection\VerifyConnectionRequest;
use App\Http\Resources\ConnectionResource;
use App\Models\App;
use App\Models\Connection;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\JsonResponse;

class ConnectionController extends Controller
{
    public function __construct(
        protected ConnectorFactory $factory,
    ) {}

    /**
     * Verify store connection and credentials for an app.
     */
    public function verify(VerifyConnectionRequest $request, App $app): JsonResponse
    {
        $validated = $request->validated();

        $connector = $this->factory->make(
            platform: $app->platform,
            siteUrl: $validated['site_url'],
            credentials: [
                'consumer_key' => $validated['consumer_key'],
                'consumer_secret' => $validated['consumer_secret'],
            ],
        );

        $result = $connector->verify();

        /** @var Connection $connection */
        $connection = Connection::updateOrCreate(
            ['app_id' => $app->id],
            [
                'tenant_id' => $app->tenant_id,
                'platform' => $app->platform,
                'site_url' => $validated['site_url'],
                'credentials' => [
                    'consumer_key' => $validated['consumer_key'],
                    'consumer_secret' => $validated['consumer_secret'],
                ],
                'status' => $result->isSuccess ? 'verified' : 'failed',
                'last_verified_at' => now(),
                'last_error_code' => $result->errorCode,
            ]
        );

        if (! $result->isSuccess) {
            return response()->json([
                'error' => [
                    'code' => $result->errorCode,
                    'message' => $result->errorMessage,
                ],
                'data' => [
                    'connection' => new ConnectionResource($connection),
                ],
            ], 422);
        }

        // Advance setup step if at connect or platform step
        if (in_array($app->setup_step, ['platform', 'connect'], true)) {
            $app->update([
                'setup_step' => 'analysis',
                'status' => 'verified',
            ]);
        }

        return response()->json([
            'message' => 'Store connection verified successfully.',
            'data' => [
                'connection' => new ConnectionResource($connection),
                'diagnostics' => [
                    'store_name' => $result->storeName,
                    'products_count' => $result->productsCount,
                    'currency' => $result->currency,
                    'wc_version' => $result->wcVersion,
                ],
            ],
        ]);
    }

    /**
     * Get current connection status for an app.
     */
    public function show(App $app): JsonResponse
    {
        $connection = $app->connection;

        if (! $connection) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_CONFIGURED',
                    'message' => 'Store connection has not been configured yet.',
                ],
            ], 404);
        }

        return response()->json([
            'data' => new ConnectionResource($connection),
        ]);
    }
}
