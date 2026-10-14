<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        protected ConnectorFactory $connectorFactory,
    ) {}

    /**
     * Get paginated catalog products.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');
        $connector = $this->connectorFactory->forApp($app);

        $query = [
            'page' => (int) $request->query('page', 1),
            'per_page' => (int) $request->query('per_page', 10),
            'search' => (string) $request->query('search', ''),
            'category' => (string) $request->query('category', ''),
        ];

        $paginated = $connector->getProducts($query);

        return response()->json([
            'data' => $paginated->items,
            'meta' => [
                'current_page' => $paginated->currentPage,
                'total_pages' => $paginated->totalPages,
                'total_products' => $paginated->totalProducts,
            ],
        ]);
    }

    /**
     * Get single product details.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');
        $connector = $this->connectorFactory->forApp($app);

        $product = $connector->getProduct($id);

        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * Get categories list.
     */
    public function categories(Request $request): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');
        $connector = $this->connectorFactory->forApp($app);

        $categories = $connector->getCategories();

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Search products by keyword.
     */
    public function search(Request $request): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');
        $connector = $this->connectorFactory->forApp($app);

        $term = (string) $request->query('q', '');
        $results = $connector->searchProducts($term);

        return response()->json([
            'data' => $results,
        ]);
    }
}
