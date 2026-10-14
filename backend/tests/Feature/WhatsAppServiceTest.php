<?php

use App\Exceptions\EntitlementException;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Models\App;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Models\WhatsappWallet;
use App\Services\Notifications\WhatsAppServiceInterface;

beforeEach(function () {
    $this->tenantA = Tenant::factory()->create(['status' => 'active']);
    $this->userA = User::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'role' => 'owner',
    ]);
    $this->appA = App::factory()->create([
        'tenant_id' => $this->tenantA->id,
    ]);
    $this->planWithWhatsApp = Plan::factory()->create([
        'features' => ['whatsapp' => true],
    ]);
    Subscription::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'plan_id' => $this->planWithWhatsApp->id,
        'status' => 'active',
        'current_period_end' => now()->addDays(30),
    ]);

    // Initial wallet balance for Tenant A
    $this->walletA = WhatsappWallet::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'balance' => 10.00,
    ]);

    // Tenant B (without WhatsApp entitlement initially)
    $this->tenantB = Tenant::factory()->create(['status' => 'active']);
    $this->userB = User::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'role' => 'owner',
    ]);
    $this->appB = App::factory()->create([
        'tenant_id' => $this->tenantB->id,
    ]);
    $this->planNoWhatsApp = Plan::factory()->create([
        'features' => ['whatsapp' => false],
    ]);
    Subscription::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'app_id' => $this->appB->id,
        'plan_id' => $this->planNoWhatsApp->id,
        'status' => 'active',
        'current_period_end' => now()->addDays(30),
    ]);
    $this->walletB = WhatsappWallet::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'balance' => 15.00,
    ]);
});

test('whatsapp service is gated by entitlement', function () {
    /** @var WhatsAppServiceInterface $service */
    $service = app(WhatsAppServiceInterface::class);

    // Tenant B lacks WhatsApp entitlement
    expect(fn () => $service->sendTestMessage($this->tenantB, '919876543210', $this->appB))
        ->toThrow(EntitlementException::class);

    // Dashboard test endpoint returns 403
    $response = $this->actingAs($this->userB)
        ->postJson("/api/apps/{$this->appB->ulid}/integrations/whatsapp/test", [
            'phone' => '919876543210',
        ]);

    $response->assertStatus(403);
});

test('whatsapp service rejects non-approved templates', function () {
    /** @var WhatsAppServiceInterface $service */
    $service = app(WhatsAppServiceInterface::class);

    expect(fn () => $service->sendTemplateMessage(
        tenant: $this->tenantA,
        phone: '919876543210',
        templateName: 'unapproved_promotional_spam',
        app: $this->appA
    ))->toThrow(InvalidArgumentException::class, 'pre-approved templates');
});

test('whatsapp service debits wallet before send and records usage', function () {
    /** @var WhatsAppServiceInterface $service */
    $service = app(WhatsAppServiceInterface::class);

    $initialBalance = (float) $this->walletA->fresh()->balance;

    $message = $service->sendTemplateMessage(
        tenant: $this->tenantA,
        phone: '919876543210',
        templateName: 'order_update',
        parameters: ['order_id' => '1001'],
        app: $this->appA
    );

    expect($message)->toBeInstanceOf(WhatsappMessage::class)
        ->and($message->status)->toBe('sent')
        ->and($message->whatsapp_message_id)->not->toBeEmpty();

    $newBalance = (float) $this->walletA->fresh()->balance;
    expect($newBalance)->toEqualWithDelta($initialBalance - 0.80, 0.001);

    // UsageEvent was logged
    $this->assertDatabaseHas('usage_events', [
        'tenant_id' => $this->tenantA->id,
        'type' => 'whatsapp_message',
    ]);
});

test('insufficient wallet balance is rejected before sending', function () {
    $this->walletA->update(['balance' => 0.50]); // less than 0.80

    /** @var WhatsAppServiceInterface $service */
    $service = app(WhatsAppServiceInterface::class);

    expect(fn () => $service->sendTemplateMessage(
        tenant: $this->tenantA,
        phone: '919876543210',
        templateName: 'order_update',
        app: $this->appA
    ))->toThrow(InsufficientWalletBalanceException::class);

    // Balance untouched
    expect((float) $this->walletA->fresh()->balance)->toEqualWithDelta(0.50, 0.001);
});

test('wallet is refunded if cloud api dispatch fails', function () {
    /** @var WhatsAppServiceInterface $service */
    $service = app(WhatsAppServiceInterface::class);

    $initialBalance = (float) $this->walletA->fresh()->balance;

    // Phone starting with 9999999999 triggers simulated dispatch failure
    try {
        $service->sendTemplateMessage(
            tenant: $this->tenantA,
            phone: '9999999999',
            templateName: 'order_update',
            app: $this->appA
        );
        $this->fail('Expected RuntimeException was not thrown.');
    } catch (Throwable $e) {
        expect($e->getMessage())->toContain('transmission failure');
    }

    // Wallet is refunded back to initial balance
    $refundedBalance = (float) $this->walletA->fresh()->balance;
    expect($refundedBalance)->toEqualWithDelta($initialBalance, 0.001);

    // Failed message record persists for audit logs
    $failedMessage = WhatsappMessage::where('tenant_id', $this->tenantA->id)
        ->where('phone', '9999999999')
        ->first();

    expect($failedMessage)->not->toBeNull()
        ->and($failedMessage->status)->toBe('failed');
});

test('test message button sends verified greeting and debits wallet', function () {
    $initialBalance = (float) $this->walletA->fresh()->balance;

    $response = $this->actingAs($this->userA)
        ->postJson("/api/apps/{$this->appA->ulid}/integrations/whatsapp/test", [
            'phone' => '919876543210',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Test WhatsApp message sent successfully.')
        ->assertJsonStructure(['wallet_balance', 'data']);

    $newBalance = (float) $this->walletA->fresh()->balance;
    expect($newBalance)->toEqualWithDelta($initialBalance - 0.80, 0.001);

    $this->assertDatabaseHas('whatsapp_messages', [
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'template_name' => 'test_greeting',
        'status' => 'sent',
    ]);
});

test('whatsapp delivery webhook updates message status', function () {
    $message = WhatsappMessage::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'whatsapp_message_id' => 'wamid.HBgM_TEST_MSG_001',
        'status' => 'sent',
    ]);

    // Webhook verification challenge
    config(['services.whatsapp.webhook_verify_token' => 'secret_verify_token_123']);
    $challengeResponse = $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=secret_verify_token_123&hub_challenge=valid_challenge_str');
    $challengeResponse->assertStatus(200)
        ->assertSee('valid_challenge_str');

    // Delivery receipt webhook
    $deliveryPayload = [
        'entry' => [
            [
                'changes' => [
                    [
                        'value' => [
                            'statuses' => [
                                [
                                    'id' => 'wamid.HBgM_TEST_MSG_001',
                                    'status' => 'delivered',
                                    'timestamp' => '1710000000',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/webhooks/whatsapp', $deliveryPayload);
    $response->assertStatus(200);

    expect($message->fresh()->status)->toBe('delivered');

    // Read receipt webhook
    $readPayload = [
        'entry' => [
            [
                'changes' => [
                    [
                        'value' => [
                            'statuses' => [
                                [
                                    'id' => 'wamid.HBgM_TEST_MSG_001',
                                    'status' => 'read',
                                    'timestamp' => '1710000100',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $this->postJson('/webhooks/whatsapp', $readPayload)->assertStatus(200);
    expect($message->fresh()->status)->toBe('read');
});

test('tenant isolation: tenant A cannot access tenant B whatsapp integration', function () {
    // Tenant A attempts to send test message on Tenant B's app
    $response = $this->actingAs($this->userA)
        ->postJson("/api/apps/{$this->appB->ulid}/integrations/whatsapp/test", [
            'phone' => '919876543210',
        ]);

    $response->assertStatus(404);

    // Tenant B's wallet is not affected
    expect((float) $this->walletB->fresh()->balance)->toEqualWithDelta(15.00, 0.001);
});

test('viewer role cannot send test whatsapp messages', function () {
    $viewerUser = User::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'role' => 'viewer',
    ]);

    $response = $this->actingAs($viewerUser)
        ->postJson("/api/apps/{$this->appA->ulid}/integrations/whatsapp/test", [
            'phone' => '919876543210',
        ]);

    $response->assertStatus(403)
        ->assertJsonPath('error_code', 'FORBIDDEN');
});

test('whatsapp delivery webhook verifies HMAC signature when app_secret is set', function () {
    config(['services.whatsapp.app_secret' => 'meta_webhook_secret_999']);

    $message = WhatsappMessage::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'whatsapp_message_id' => 'wamid.HBgM_HMAC_TEST_01',
        'status' => 'sent',
    ]);

    $payload = json_encode([
        'entry' => [
            [
                'changes' => [
                    [
                        'value' => [
                            'statuses' => [
                                [
                                    'id' => 'wamid.HBgM_HMAC_TEST_01',
                                    'status' => 'delivered',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    // 1. Missing signature -> 401
    $this->call('POST', '/webhooks/whatsapp', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $payload)->assertStatus(401);

    // 2. Tampered signature -> 401
    $this->call('POST', '/webhooks/whatsapp', [], [], [], [
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid_forged_hash',
        'CONTENT_TYPE' => 'application/json',
    ], $payload)->assertStatus(401);

    // 3. Valid HMAC signature -> 200 and status updated
    $validSignature = 'sha256='.hash_hmac('sha256', $payload, 'meta_webhook_secret_999');
    $response = $this->call('POST', '/webhooks/whatsapp', [], [], [], [
        'HTTP_X_HUB_SIGNATURE_256' => $validSignature,
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200);
    expect($message->fresh()->status)->toBe('delivered');
});

test('whatsapp delivery webhook handles multi-entry batch receipts', function () {
    $msg1 = WhatsappMessage::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'whatsapp_message_id' => 'wamid.HBgM_BATCH_1',
        'status' => 'sent',
    ]);
    $msg2 = WhatsappMessage::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'app_id' => $this->appA->id,
        'whatsapp_message_id' => 'wamid.HBgM_BATCH_2',
        'status' => 'sent',
    ]);

    $batchPayload = [
        'entry' => [
            [
                'changes' => [
                    [
                        'value' => [
                            'statuses' => [
                                ['id' => 'wamid.HBgM_BATCH_1', 'status' => 'delivered'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'changes' => [
                    [
                        'value' => [
                            'statuses' => [
                                ['id' => 'wamid.HBgM_BATCH_2', 'status' => 'read'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/webhooks/whatsapp', $batchPayload);
    $response->assertStatus(200);

    expect($msg1->fresh()->status)->toBe('delivered')
        ->and($msg2->fresh()->status)->toBe('read');
});
