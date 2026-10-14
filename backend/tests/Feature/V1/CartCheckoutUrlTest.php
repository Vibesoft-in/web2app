<?php

use App\Models\App;
use App\Models\Connection;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;

test('checkout-url revalidates cart items and returns checkout URL with allowed hosts', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create([
        'tenant_id' => $tenant->id,
        'app_key' => 'wia_app_key_checkout',
    ]);

    Connection::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'platform' => 'woo',
        'site_url' => 'https://checkout-shop.example.com',
        'consumer_key' => 'ck_test',
        'consumer_secret' => 'cs_test',
        'is_verified' => true,
    ]);

    // Mock WooCommerce get product
    Http::fake([
        'https://checkout-shop.example.com/wp-json/wc/v3/products/10*' => Http::response([
            'id' => 10,
            'name' => 'Running Shoes',
            'stock_status' => 'instock',
            'manage_stock' => false,
            'regular_price' => '120.00',
            'price' => '120.00',
            'images' => [],
        ], 200, ['Content-Type' => 'application/json']),
    ]);

    $response = $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/cart/checkout-url', [
            'items' => [
                [
                    'product_id' => 10,
                    'quantity' => 2,
                ],
            ],
        ]);

    $response->assertStatus(200);

    $checkoutUrl = $response->json('data.checkout_url');
    $allowedHosts = $response->json('data.allowed_hosts');

    expect($checkoutUrl)->toContain('https://checkout-shop.example.com/checkout/')
        ->and($checkoutUrl)->toContain('add-to-cart=10')
        ->and($allowedHosts)->toContain('checkout-shop.example.com');
});

test('checkout-url rejects request when product is out of stock', function () {
    $tenant = Tenant::factory()->create();
    $app = App::factory()->create([
        'tenant_id' => $tenant->id,
        'app_key' => 'wia_app_key_checkout_oos',
    ]);

    Connection::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'platform' => 'woo',
        'site_url' => 'https://checkout-shop.example.com',
        'consumer_key' => 'ck_test',
        'consumer_secret' => 'cs_test',
        'is_verified' => true,
    ]);

    Http::fake([
        'https://checkout-shop.example.com/wp-json/wc/v3/products/99*' => Http::response([
            'id' => 99,
            'name' => 'Sold Out Hat',
            'stock_status' => 'outofstock',
            'regular_price' => '50.00',
            'price' => '50.00',
            'images' => [],
        ], 200, ['Content-Type' => 'application/json']),
    ]);

    $response = $this->withHeaders(['X-App-Key' => $app->app_key])
        ->postJson('/v1/cart/checkout-url', [
            'items' => [
                [
                    'product_id' => 99,
                    'quantity' => 1,
                ],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'OUT_OF_STOCK');
    expect($response->json('error.message'))->toContain('out of stock');
});
