<?php

use App\Models\App;
use App\Models\AppUser;
use App\Models\Connection;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;

test('user A cannot read user B orders in listing or via direct ID lookup', function () {
    $tenant = Tenant::factory()->create();

    $app = App::factory()->create([
        'tenant_id' => $tenant->id,
        'app_key' => 'wia_app_key_orders',
    ]);

    Connection::create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'platform' => 'woo',
        'site_url' => 'https://mock-shop.example.com',
        'consumer_key' => 'ck_test_123',
        'consumer_secret' => 'cs_test_123',
        'is_verified' => true,
    ]);

    // Create User A with store customer ID 101
    $userA = AppUser::factory()->create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'store_customer_id' => '101',
    ]);
    $tokenA = $userA->createToken('session-a', ["app:{$app->id}"])->plainTextToken;

    // Create User B with store customer ID 102
    $userB = AppUser::factory()->create([
        'tenant_id' => $tenant->id,
        'app_id' => $app->id,
        'store_customer_id' => '102',
    ]);
    $tokenB = $userB->createToken('session-b', ["app:{$app->id}"])->plainTextToken;

    // Mock WooCommerce HTTP endpoints
    Http::fake([
        'https://mock-shop.example.com/wp-json/wc/v3/orders?*' => function ($request) {
            return Http::response([
                [
                    'id' => 501,
                    'number' => '501',
                    'customer_id' => 101,
                    'status' => 'processing',
                    'total' => '150.00',
                    'currency' => 'INR',
                    'line_items' => [
                        ['id' => 1, 'product_id' => 10, 'name' => 'Shirt', 'quantity' => 1, 'total' => '150.00'],
                    ],
                ],
                [
                    'id' => 502,
                    'number' => '502',
                    'customer_id' => 102,
                    'status' => 'completed',
                    'total' => '320.00',
                    'currency' => 'INR',
                    'line_items' => [
                        ['id' => 2, 'product_id' => 20, 'name' => 'Shoes', 'quantity' => 1, 'total' => '320.00'],
                    ],
                ],
            ], 200, ['Content-Type' => 'application/json']);
        },
        'https://mock-shop.example.com/wp-json/wc/v3/orders/501*' => Http::response([
            'id' => 501,
            'number' => '501',
            'customer_id' => 101,
            'status' => 'processing',
            'total' => '150.00',
            'currency' => 'INR',
            'line_items' => [
                ['id' => 1, 'product_id' => 10, 'name' => 'Shirt', 'quantity' => 1, 'total' => '150.00'],
            ],
        ], 200, ['Content-Type' => 'application/json']),
        'https://mock-shop.example.com/wp-json/wc/v3/orders/502*' => Http::response([
            'id' => 502,
            'number' => '502',
            'customer_id' => 102,
            'status' => 'completed',
            'total' => '320.00',
            'currency' => 'INR',
            'line_items' => [
                ['id' => 2, 'product_id' => 20, 'name' => 'Shoes', 'quantity' => 1, 'total' => '320.00'],
            ],
        ], 200, ['Content-Type' => 'application/json']),
    ]);

    // 1. User A lists orders: only receives order 501
    auth('sanctum')->forgetUser();
    $responseA = $this->withHeaders([
        'X-App-Key' => $app->app_key,
        'Authorization' => "Bearer {$tokenA}",
    ])->getJson('/v1/orders');

    $responseA->assertStatus(200);
    $ordersA = $responseA->json('data');
    expect($ordersA)->toHaveCount(1)
        ->and($ordersA[0]['id'])->toBe('501');

    // 2. User B lists orders: only receives order 502
    auth('sanctum')->forgetUser();
    $responseB = $this->withHeaders([
        'X-App-Key' => $app->app_key,
        'Authorization' => "Bearer {$tokenB}",
    ])->getJson('/v1/orders');

    $responseB->assertStatus(200);
    $ordersB = $responseB->json('data');
    expect($ordersB)->toHaveCount(1)
        ->and($ordersB[0]['id'])->toBe('502');

    // 3. User A directly requests User A's order 501: succeeds
    auth('sanctum')->forgetUser();
    $viewOwn = $this->withHeaders([
        'X-App-Key' => $app->app_key,
        'Authorization' => "Bearer {$tokenA}",
    ])->getJson('/v1/orders/501');
    $viewOwn->assertStatus(200)
        ->assertJsonPath('data.id', '501');

    // 4. User A attempts IDOR attack on User B's order 502: strictly 404 ORDER_NOT_FOUND
    auth('sanctum')->forgetUser();
    $idorAttempt = $this->withHeaders([
        'X-App-Key' => $app->app_key,
        'Authorization' => "Bearer {$tokenA}",
    ])->getJson('/v1/orders/502');

    $idorAttempt->assertStatus(404)
        ->assertJsonPath('error.code', 'ORDER_NOT_FOUND');
});
