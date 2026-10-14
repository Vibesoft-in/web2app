<?php

use App\Services\Catalog\CommonProduct;
use App\Services\Catalog\PaginatedProducts;
use App\Services\Connectors\ConnectorFactory;
use App\Services\Connectors\WooConnector;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->siteUrl = 'https://mystore.example.com';
    $this->key = 'ck_test1234567890';
    $this->secret = 'cs_test0987654321';
    $this->connector = new WooConnector($this->siteUrl, $this->key, $this->secret);
});

test('woo connector verifies active store using system_status endpoint', function () {
    Http::fake([
        'https://mystore.example.com/wp-json/wc/v3/system_status' => Http::response([
            'environment' => [
                'version' => '8.6.0',
            ],
            'settings' => [
                'store_name' => 'Fashion Boutique',
                'currency' => 'INR',
            ],
        ], 200, ['Content-Type' => 'application/json']),
        'https://mystore.example.com/wp-json/wc/v3/products?per_page=1' => Http::response([
            ['id' => 101, 'name' => 'Sample Shirt'],
        ], 200, [
            'Content-Type' => 'application/json',
            'X-WP-Total' => '142',
            'X-WP-TotalPages' => '15',
        ]),
    ]);

    $result = $this->connector->verify();

    expect($result->isSuccess)->toBeTrue()
        ->and($result->storeName)->toBe('Fashion Boutique')
        ->and($result->productsCount)->toBe(142)
        ->and($result->currency)->toBe('INR')
        ->and($result->wcVersion)->toBe('8.6.0');
});

test('woo connector maps product list into CommonProduct DTOs and sanitizes description', function () {
    Http::fake([
        'https://mystore.example.com/wp-json/wc/v3/products*' => Http::response([
            [
                'id' => 45,
                'name' => 'Luxury Silk Scarf',
                'description' => '<p>Soft pure silk scarf.</p><script>alert("xss")</script><img src="x" onerror="stealCookie()">',
                'regular_price' => '1999.00',
                'sale_price' => '1499.00',
                'price' => '1499.00',
                'stock_status' => 'instock',
                'images' => [
                    ['src' => 'https://mystore.example.com/wp-content/uploads/scarf.jpg'],
                ],
                'categories' => [
                    ['id' => 12, 'name' => 'Accessories'],
                ],
                'attributes' => [
                    ['name' => 'Color', 'options' => ['Blue', 'Red']],
                ],
                'permalink' => 'https://mystore.example.com/product/luxury-silk-scarf',
            ],
        ], 200, [
            'Content-Type' => 'application/json',
            'X-WP-Total' => '1',
            'X-WP-TotalPages' => '1',
        ]),
    ]);

    $paginated = $this->connector->getProducts(['page' => 1]);

    expect($paginated)->toBeInstanceOf(PaginatedProducts::class)
        ->and($paginated->total)->toBe(1)
        ->and($paginated->products)->toHaveCount(1);

    /** @var CommonProduct $product */
    $product = $paginated->products[0];

    expect($product->id)->toBe('45')
        ->and($product->title)->toBe('Luxury Silk Scarf')
        ->and($product->descriptionHtml)->toContain('<p>Soft pure silk scarf.</p>')
        ->and($product->descriptionHtml)->not->toContain('<script>')
        ->and($product->descriptionHtml)->not->toContain('onerror')
        ->and($product->price)->toBe(1999.0)
        ->and($product->salePrice)->toBe(1499.0)
        ->and($product->inStock)->toBeTrue()
        ->and($product->images)->toContain('https://mystore.example.com/wp-content/uploads/scarf.jpg')
        ->and($product->categoryIds)->toBe([12]);
});

test('woo connector can fetch single product by id', function () {
    Http::fake([
        'https://mystore.example.com/wp-json/wc/v3/products/45' => Http::response([
            'id' => 45,
            'name' => 'Luxury Silk Scarf',
            'regular_price' => '1999.00',
            'sale_price' => null,
            'stock_status' => 'instock',
            'images' => [],
            'permalink' => 'https://mystore.example.com/product/luxury-silk-scarf',
        ], 200, ['Content-Type' => 'application/json']),
    ]);

    $product = $this->connector->getProduct('45');

    expect($product->id)->toBe('45')
        ->and($product->title)->toBe('Luxury Silk Scarf')
        ->and($product->salePrice)->toBeNull();
});

test('connector factory creates WooConnector for woo platform', function () {
    $factory = new ConnectorFactory;
    $connector = $factory->make('woo', 'https://example.com', [
        'consumer_key' => 'ck_123',
        'consumer_secret' => 'cs_456',
    ]);

    expect($connector)->toBeInstanceOf(WooConnector::class);
});
