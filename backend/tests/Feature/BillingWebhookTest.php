<?php

use App\Enums\SetupStep;
use App\Models\App;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\UsageEvent;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Billing\RazorpayService;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['status' => 'active']);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role' => 'owner',
    ]);
    $this->plan = Plan::factory()->create([
        'price_monthly' => 1999.00,
    ]);
    $this->mobileApp = App::factory()->create([
        'tenant_id' => $this->tenant->id,
        'setup_step' => SetupStep::PAYMENT->value,
        'status' => 'configured',
    ]);

    /** @var RazorpayService $razorpay */
    $this->razorpay = app(RazorpayService::class);
    $this->webhookSecret = config('services.razorpay.webhook_secret', 'rzp_webhook_sample_secret');
});

test('forged webhook with invalid signature is strictly rejected with 400', function () {
    $payload = json_encode([
        'event' => 'payment.captured',
        'event_id' => 'evt_forged_123',
    ]);

    $response = $this->withHeaders([
        'X-Razorpay-Signature' => 'forged_fake_signature_hash',
    ])->postJson('/webhooks/razorpay', json_decode($payload, true));

    $response->assertStatus(400)
        ->assertJsonPath('error', 'INVALID_SIGNATURE');

    expect(WebhookEvent::count())->toBe(0)
        ->and(Payment::count())->toBe(0);
});

test('valid webhook captures payment, activates subscription, advances setup state, and records audit log', function () {
    $paymentId = 'pay_'.Str::random(12);
    $eventId = 'evt_valid_'.Str::random(10);

    $payloadArray = [
        'entity' => 'event',
        'event' => 'payment.captured',
        'event_id' => $eventId,
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => $paymentId,
                    'amount' => 235882, // in paise
                    'currency' => 'INR',
                    'status' => 'captured',
                    'order_id' => 'order_123456',
                    'notes' => [
                        'tenant_id' => (string) $this->tenant->id,
                        'app_id' => (string) $this->mobileApp->id,
                        'plan_id' => (string) $this->plan->id,
                    ],
                ],
            ],
        ],
    ];

    $rawPayload = json_encode($payloadArray);
    $signature = hash_hmac('sha256', $rawPayload, $this->webhookSecret);

    $response = $this->call(
        'POST',
        '/webhooks/razorpay',
        [],
        [],
        [],
        [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ],
        $rawPayload
    );

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('event_id', $eventId);

    // 1. WebhookEvent record exists and is marked processed
    $webhookEvent = WebhookEvent::where('event_id', $eventId)->first();
    expect($webhookEvent)->not->toBeNull()
        ->and($webhookEvent->processed_at)->not->toBeNull();

    // 2. Payment record created with correct converted amount
    $payment = Payment::where('razorpay_payment_id', $paymentId)->first();
    expect($payment)->not->toBeNull()
        ->and((float) $payment->amount)->toBe(2358.82)
        ->and($payment->currency)->toBe('INR')
        ->and($payment->tenant_id)->toBe($this->tenant->id);

    // 3. Subscription activated and extended by 30 days
    $subscription = Subscription::where('tenant_id', $this->tenant->id)->first();
    expect($subscription)->not->toBeNull()
        ->and($subscription->status)->toBe('active')
        ->and($subscription->current_period_end->isFuture())->toBeTrue();

    // 4. App status set to paid and setup step advanced to build
    $freshApp = $this->mobileApp->fresh();
    expect($freshApp->status)->toBe('paid')
        ->and($freshApp->setup_step)->toBe(SetupStep::BUILD->value);

    // 5. UsageEvent recorded
    expect(UsageEvent::where('tenant_id', $this->tenant->id)->where('type', 'subscription_payment')->exists())->toBeTrue();

    // 6. AuditLog recorded
    expect(AuditLog::where('tenant_id', $this->tenant->id)->where('action', 'payment_captured')->exists())->toBeTrue();
});

test('replayed webhook with identical event_id is idempotently ignored without duplicate records', function () {
    $eventId = 'evt_replay_test_999';
    $paymentId = 'pay_replay_test_111';

    $payloadArray = [
        'event' => 'payment.captured',
        'event_id' => $eventId,
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => $paymentId,
                    'amount' => 199900,
                    'currency' => 'INR',
                    'notes' => [
                        'tenant_id' => (string) $this->tenant->id,
                        'app_id' => (string) $this->mobileApp->id,
                    ],
                ],
            ],
        ],
    ];

    $rawPayload = json_encode($payloadArray);
    $signature = hash_hmac('sha256', $rawPayload, $this->webhookSecret);

    // First arrival
    $response1 = $this->call(
        'POST',
        '/webhooks/razorpay',
        [],
        [],
        [],
        ['HTTP_X-Razorpay-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
        $rawPayload
    );
    $response1->assertStatus(200)->assertJsonPath('status', 'success');

    expect(Payment::count())->toBe(1);

    // Replay arrival with identical payload and event_id
    $response2 = $this->call(
        'POST',
        '/webhooks/razorpay',
        [],
        [],
        [],
        ['HTTP_X-Razorpay-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
        $rawPayload
    );

    $response2->assertStatus(200)
        ->assertJsonPath('status', 'ignored')
        ->assertJsonPath('message', 'Event already processed');

    // Counts remain 1 (no double credit)
    expect(Payment::count())->toBe(1)
        ->and(WebhookEvent::where('event_id', $eventId)->count())->toBe(1);
});

test('double payment with same payment id is blocked from duplicate crediting', function () {
    $paymentId = 'pay_duplicate_check_222';

    // Existing payment in database
    Payment::create([
        'tenant_id' => $this->tenant->id,
        'razorpay_payment_id' => $paymentId,
        'amount' => 1999.00,
        'currency' => 'INR',
        'status' => 'captured',
    ]);

    // Second webhook arrives with different event_id but identical payment_id
    $payloadArray = [
        'event' => 'payment.captured',
        'event_id' => 'evt_second_different_id_333',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => $paymentId,
                    'amount' => 199900,
                    'currency' => 'INR',
                    'notes' => [
                        'tenant_id' => (string) $this->tenant->id,
                        'app_id' => (string) $this->mobileApp->id,
                    ],
                ],
            ],
        ],
    ];

    $rawPayload = json_encode($payloadArray);
    $signature = hash_hmac('sha256', $rawPayload, $this->webhookSecret);

    $response = $this->call(
        'POST',
        '/webhooks/razorpay',
        [],
        [],
        [],
        ['HTTP_X-Razorpay-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
        $rawPayload
    );

    $response->assertStatus(200);

    // Ensure payment was not duplicated
    expect(Payment::where('razorpay_payment_id', $paymentId)->count())->toBe(1);
});

test('checkout endpoint creates Razorpay order with server-side calculated pricing', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/apps/{$this->mobileApp->ulid}/checkout", [
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'yearly',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.currency', 'INR')
        ->assertJsonPath('data.pricing.billing_cycle', 'yearly')
        ->assertJsonStructure([
            'data' => [
                'order_id',
                'amount',
                'currency',
                'key_id',
                'pricing' => [
                    'plan_id',
                    'total_amount',
                    'amount_paise',
                ],
            ],
        ]);
});
