<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppConfig;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected ConnectorFactory $connectorFactory,
    ) {}

    /**
     * Revalidate cart items and return webview checkout URL restricted to allowed hosts.
     */
    public function checkoutUrl(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.variant_id' => ['nullable'],
        ]);

        /** @var App $app */
        $app = $request->attributes->get('app');
        $connector = $this->connectorFactory->forApp($app);

        /** @var list<array{product_id: string|int, quantity: int, variant_id?: string|int|null}> $items */
        $items = $request->input('items');

        /** @var \App\Models\AppUser|null $user */
        $user = $request->user();

        try {
            $checkoutUrl = $connector->createCheckoutUrl($items, $user);

        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'OUT_OF_STOCK',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }

        // Collect allowed hosts for in-app webview whitelist
        $allowedHosts = [];
        if ($app->connection && ! empty($app->connection->site_url)) {
            $host = parse_url($app->connection->site_url, PHP_URL_HOST);
            if ($host) {
                $allowedHosts[] = $host;
            }
        }

        /** @var AppConfig|null $config */
        $config = AppConfig::where('app_id', $app->id)->where('state', 'published')->first();
        if ($config && ! empty($config->config['webview_allowed_hosts'])) {
            foreach ($config->config['webview_allowed_hosts'] as $h) {
                if (! in_array($h, $allowedHosts, true)) {
                    $allowedHosts[] = $h;
                }
            }
        }

        return response()->json([
            'data' => [
                'checkout_url' => $checkoutUrl,
                'allowed_hosts' => $allowedHosts,
            ],
        ]);
    }
}
