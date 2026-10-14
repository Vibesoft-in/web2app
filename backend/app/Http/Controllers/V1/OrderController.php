<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppUser;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected ConnectorFactory $connectorFactory,
    ) {}

    /**
     * Get orders strictly belonging to the currently authenticated AppUser.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var AppUser $user */
        $user = $request->user();

        /** @var App $app */
        $app = $request->attributes->get('app');

        // Customer has no linked store_customer_id -> zero orders
        if (empty($user->store_customer_id)) {
            return response()->json([
                'data' => [],
            ]);
        }

        $connector = $this->connectorFactory->forApp($app);
        $orders = $connector->getOrders($user, [
            'page' => (int) $request->query('page', 1),
            'per_page' => (int) $request->query('per_page', 20),
        ]);

        return response()->json([
            'data' => $orders,
        ]);
    }

    /**
     * Get single order details strictly verifying customer ownership.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        /** @var AppUser $user */
        $user = $request->user();

        /** @var App $app */
        $app = $request->attributes->get('app');

        if (empty($user->store_customer_id)) {
            return response()->json([
                'error' => [
                    'code' => 'ORDER_NOT_FOUND',
                    'message' => 'Order not found.',
                ],
            ], 404);
        }

        $connector = $this->connectorFactory->forApp($app);
        $order = $connector->getOrder($user, $id);

        if (! $order) {
            return response()->json([
                'error' => [
                    'code' => 'ORDER_NOT_FOUND',
                    'message' => 'Order not found.',
                ],
            ], 404);
        }

        return response()->json([
            'data' => $order,
        ]);
    }
}
