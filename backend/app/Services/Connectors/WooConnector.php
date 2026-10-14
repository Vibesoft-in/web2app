<?php

namespace App\Services\Connectors;

use App\Exceptions\UrlGuardException;
use App\Models\AppUser;
use App\Services\Catalog\CommonProduct;
use App\Services\Catalog\PaginatedProducts;
use App\Services\Catalog\WooProductMapper;
use App\Services\Security\UrlGuard;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WooConnector implements ConnectorInterface
{
    protected string $baseUrl;

    protected UrlGuard $urlGuard;

    protected WooProductMapper $mapper;

    public function __construct(
        protected string $siteUrl,
        protected string $consumerKey,
        protected string $consumerSecret,
        ?UrlGuard $urlGuard = null,
        ?WooProductMapper $mapper = null,
    ) {
        $this->urlGuard = $urlGuard ?? new UrlGuard;
        $this->mapper = $mapper ?? new WooProductMapper;
        $this->baseUrl = rtrim($this->siteUrl, '/').'/wp-json/wc/v3/';
    }

    /**
     * Verify store connection and permissions.
     */
    public function verify(): VerifyResult
    {
        // 1. SSRF and HTTPS validation
        try {
            $this->urlGuard->validateUrl($this->siteUrl);
        } catch (UrlGuardException $e) {
            return new VerifyResult(
                isSuccess: false,
                errorCode: $e->getErrorCode(),
                errorMessage: $e->getMessage(),
            );
        }

        // 2. Perform test request to system_status or products
        try {
            $response = $this->request('get', 'system_status');

            if ($response->status() === 401) {
                return new VerifyResult(
                    isSuccess: false,
                    errorCode: 'INVALID_KEYS',
                    errorMessage: 'The provided Consumer Key or Consumer Secret is invalid.',
                );
            }

            if ($response->status() === 403) {
                return new VerifyResult(
                    isSuccess: false,
                    errorCode: 'INSUFFICIENT_PERMISSION',
                    errorMessage: 'API keys do not have sufficient permissions. Ensure Read/Write permission is granted.',
                );
            }

            if ($response->status() === 404 || ! $this->isJsonResponse($response)) {
                // Try fallback to products?per_page=1 to check if system_status is restricted
                $prodResponse = $this->request('get', 'products', ['per_page' => 1]);
                if ($prodResponse->successful() && $this->isJsonResponse($prodResponse)) {
                    return $this->buildSuccessResultFromProducts($prodResponse);
                }

                return new VerifyResult(
                    isSuccess: false,
                    errorCode: 'REST_BLOCKED',
                    errorMessage: 'The WooCommerce REST API endpoint is blocked or permalinks are disabled on the store.',
                );
            }

            if (! $response->successful()) {
                return new VerifyResult(
                    isSuccess: false,
                    errorCode: 'URL_UNREACHABLE',
                    errorMessage: "Store returned unexpected HTTP {$response->status()}.",
                );
            }

            /** @var array<string, mixed> $data */
            $data = $response->json();

            $environment = $data['environment'] ?? [];
            $settings = $data['settings'] ?? [];

            $storeName = $settings['store_name'] ?? parse_url($this->siteUrl, PHP_URL_HOST);
            $currency = $settings['currency'] ?? 'INR';
            $wcVersion = $environment['version'] ?? null;

            // Fetch product count from header
            $countResponse = $this->request('get', 'products', ['per_page' => 1]);
            $countHeader = $countResponse->header('X-WP-Total');
            $productsCount = $countHeader !== '' ? (int) $countHeader : 0;

            return new VerifyResult(
                isSuccess: true,
                storeName: (string) $storeName,
                productsCount: $productsCount,
                currency: (string) $currency,
                wcVersion: $wcVersion ? (string) $wcVersion : null,
            );
        } catch (\Exception $e) {
            return new VerifyResult(
                isSuccess: false,
                errorCode: 'URL_UNREACHABLE',
                errorMessage: 'Failed to reach store: '.$e->getMessage(),
            );
        }
    }

    /**
     * Fetch paginated products.
     *
     * @param  array<string, mixed>  $query
     */
    public function getProducts(array $query = []): PaginatedProducts
    {
        $params = array_merge([
            'status' => 'publish',
            'per_page' => 10,
            'page' => 1,
        ], $query);

        $response = $this->request('get', 'products', $params);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to fetch products from WooCommerce: '.$response->body());
        }

        /** @var list<array<string, mixed>> $rawItems */
        $rawItems = $response->json() ?? [];

        $totalHeader = $response->header('X-WP-Total');
        $total = $totalHeader !== '' ? (int) $totalHeader : count($rawItems);
        $pagesHeader = $response->header('X-WP-TotalPages');
        $totalPages = $pagesHeader !== '' ? (int) $pagesHeader : 1;
        $currentPage = (int) ($params['page'] ?? 1);

        $products = [];
        foreach ($rawItems as $raw) {
            $products[] = $this->mapper->map($raw);
        }

        return new PaginatedProducts(
            products: $products,
            total: $total,
            totalPages: $totalPages,
            currentPage: $currentPage,
        );
    }

    /**
     * Fetch a single product by ID.
     */
    public function getProduct(string $id): CommonProduct
    {
        $response = $this->request('get', "products/{$id}");

        if (! $response->successful()) {
            throw new \RuntimeException("Product {$id} not found on WooCommerce: ".$response->body());
        }

        /** @var array<string, mixed> $raw */
        $raw = $response->json();

        return $this->mapper->map($raw);
    }

    /**
     * Fetch store categories.
     *
     * @return list<array<string, mixed>>
     */
    public function getCategories(): array
    {
        $response = $this->request('get', 'products/categories', ['per_page' => 100]);

        if (! $response->successful()) {
            return [];
        }

        /** @var list<array<string, mixed>> $rawCategories */
        $rawCategories = $response->json() ?? [];

        $categories = [];
        foreach ($rawCategories as $cat) {
            $categories[] = [
                'id' => $cat['id'] ?? 0,
                'name' => $cat['name'] ?? '',
                'slug' => $cat['slug'] ?? '',
                'count' => $cat['count'] ?? 0,
                'image' => $cat['image']['src'] ?? null,
            ];
        }

        return $categories;
    }

    /**
     * Search products matching query term.
     *
     * @return list<CommonProduct>
     */
    public function searchProducts(string $term): array
    {
        $response = $this->request('get', 'products', [
            'search' => $term,
            'per_page' => 20,
            'status' => 'publish',
        ]);

        if (! $response->successful()) {
            return [];
        }

        /** @var list<array<string, mixed>> $rawItems */
        $rawItems = $response->json() ?? [];

        $products = [];
        foreach ($rawItems as $raw) {
            $products[] = $this->mapper->map($raw);
        }

        return $products;
    }

    /**
     * Build verify result from products endpoint when system_status is restricted.
     */
    protected function buildSuccessResultFromProducts(Response $response): VerifyResult
    {
        $totalHeader = $response->header('X-WP-Total');
        $productsCount = $totalHeader !== '' ? (int) $totalHeader : 0;
        $storeName = (string) parse_url($this->siteUrl, PHP_URL_HOST);

        return new VerifyResult(
            isSuccess: true,
            storeName: $storeName,
            productsCount: $productsCount,
            currency: 'INR',
            wcVersion: null,
        );
    }

    /**
     * Perform an authenticated HTTP request to WooCommerce REST API.
     *
     * @param  array<string, mixed>  $params
     */
    protected function request(string $method, string $endpoint, array $params = []): Response
    {
        $url = $this->baseUrl.ltrim($endpoint, '/');

        // Re-validate target URL with UrlGuard
        $this->urlGuard->validateUrl($url);

        $http = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->timeout(UrlGuard::TIMEOUT_SECONDS)
            ->withoutRedirecting()
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'WebIntoApp/1.0',
            ]);

        if (strtolower($method) === 'get') {
            return $http->get($url, $params);
        }

        return $http->post($url, $params);
    }

    /**
     * Find or create customer by phone or email.
     */
    public function findOrCreateCustomer(string $identifier, ?string $name = null): string
    {
        $isEmail = str_contains($identifier, '@');
        $params = $isEmail ? ['email' => $identifier] : ['search' => $identifier];

        $searchResponse = $this->request('get', 'customers', $params);
        if ($searchResponse->successful()) {
            /** @var list<array<string, mixed>> $customers */
            $customers = $searchResponse->json() ?? [];
            if (! empty($customers) && isset($customers[0]['id'])) {
                return (string) $customers[0]['id'];
            }
        }

        // Create new customer
        $payload = [
            'email' => $isEmail ? $identifier : "cust_{$identifier}@app.store.internal",
            'first_name' => $name ?? 'App Customer',
            'billing' => [
                'phone' => ! $isEmail ? $identifier : '',
            ],
        ];

        $createResponse = $this->request('post', 'customers', $payload);
        if ($createResponse->successful()) {
            return (string) ($createResponse->json('id') ?? '1');
        }

        // Fallback customer ID if store does not allow customer creation
        return '1';
    }

    /**
     * Revalidate cart items/prices and construct a webview checkout URL.
     *
     * @param  list<array{product_id: string|int, quantity: int, variant_id?: string|int|null}>  $cartItems
     */
    public function createCheckoutUrl(array $cartItems, ?AppUser $user = null): string
    {
        // Revalidate each product availability
        foreach ($cartItems as $item) {
            $productId = (string) $item['product_id'];
            $product = $this->getProduct($productId);
            if (! $product->inStock) {
                throw new \RuntimeException("Product '{$product->title}' is out of stock.");
            }
        }

        $checkoutBase = rtrim($this->siteUrl, '/').'/checkout/';
        if (! empty($cartItems)) {
            $first = $cartItems[0];
            $targetId = $first['variant_id'] ?? $first['product_id'];
            $qty = $first['quantity'] ?? 1;

            return $checkoutBase.'?add-to-cart='.$targetId.'&quantity='.$qty;
        }

        return $checkoutBase;
    }

    /**
     * Fetch orders strictly belonging to the specified customer.
     *
     * @param  array<string, mixed>  $query
     * @return list<array<string, mixed>>
     */
    public function getOrders(AppUser $user, array $query = []): array
    {
        if (empty($user->store_customer_id)) {
            return [];
        }

        $params = array_merge([
            'customer' => $user->store_customer_id,
            'per_page' => 20,
        ], $query);

        $response = $this->request('get', 'orders', $params);
        if (! $response->successful()) {
            return [];
        }

        /** @var list<array<string, mixed>> $rawOrders */
        $rawOrders = $response->json() ?? [];
        $orders = [];

        foreach ($rawOrders as $raw) {
            // Strict security check: reject any order that does not match this customer's store_customer_id
            if ((string) ($raw['customer_id'] ?? '') !== (string) $user->store_customer_id) {
                continue;
            }

            $orders[] = [
                'id' => (string) ($raw['id'] ?? ''),
                'number' => (string) ($raw['number'] ?? $raw['id'] ?? ''),
                'status' => $raw['status'] ?? 'processing',
                'total' => (string) ($raw['total'] ?? '0.00'),
                'currency' => $raw['currency'] ?? 'INR',
                'created_at' => $raw['date_created'] ?? null,
                'items' => array_map(function (array $item): array {
                    return [
                        'id' => (string) ($item['id'] ?? ''),
                        'product_id' => (string) ($item['product_id'] ?? ''),
                        'name' => (string) ($item['name'] ?? ''),
                        'quantity' => (int) ($item['quantity'] ?? 1),
                        'total' => (string) ($item['total'] ?? '0.00'),
                    ];
                }, $raw['line_items'] ?? []),
            ];
        }

        return $orders;
    }

    /**
     * Fetch single order strictly verifying customer ownership.
     *
     * @return array<string, mixed>|null
     */
    public function getOrder(AppUser $user, string $orderId): ?array
    {
        if (empty($user->store_customer_id)) {
            return null;
        }

        $response = $this->request('get', "orders/{$orderId}");
        if (! $response->successful()) {
            return null;
        }

        /** @var array<string, mixed> $raw */
        $raw = $response->json() ?? [];

        // Strict customer ownership check
        if ((string) ($raw['customer_id'] ?? '') !== (string) $user->store_customer_id) {
            return null;
        }

        return [
            'id' => (string) ($raw['id'] ?? ''),
            'number' => (string) ($raw['number'] ?? $raw['id'] ?? ''),
            'status' => $raw['status'] ?? 'processing',
            'total' => (string) ($raw['total'] ?? '0.00'),
            'currency' => $raw['currency'] ?? 'INR',
            'created_at' => $raw['date_created'] ?? null,
            'items' => array_map(function (array $item): array {
                return [
                    'id' => (string) ($item['id'] ?? ''),
                    'product_id' => (string) ($item['product_id'] ?? ''),
                    'name' => (string) ($item['name'] ?? ''),
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'total' => (string) ($item['total'] ?? '0.00'),
                ];
            }, $raw['line_items'] ?? []),
        ];
    }

    protected function isJsonResponse(Response $response): bool
    {
        $contentType = $response->header('Content-Type');

        return str_contains($contentType, 'application/json');
    }
}
