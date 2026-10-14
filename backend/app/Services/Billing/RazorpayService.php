<?php

namespace App\Services\Billing;

use App\Models\App;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class RazorpayService
{
    protected string $keyId;
    protected string $keySecret;
    protected string $webhookSecret;
    protected PricingCalculator $calculator;

    public function __construct(PricingCalculator $calculator)
    {
        $this->calculator = $calculator;
        $this->keyId = (string) config('services.razorpay.key_id', 'rzp_test_sample_key');
        $this->keySecret = (string) config('services.razorpay.key_secret', 'rzp_test_sample_secret');
        $this->webhookSecret = (string) config('services.razorpay.webhook_secret', 'rzp_webhook_sample_secret');
    }

    /**
     * Create an order on Razorpay with server-calculated pricing.
     *
     * @param  Tenant  $tenant
     * @param  App  $app
     * @param  Plan  $plan
     * @param  string  $billingCycle
     * @param  array<string, int>  $addons
     * @return array{
     *     order_id: string,
     *     amount: int,
     *     currency: string,
     *     key_id: string,
     *     notes: array<string, string>,
     *     pricing: array<string, mixed>
     * }
     */
    public function createOrder(
        Tenant $tenant,
        App $app,
        Plan $plan,
        string $billingCycle = 'monthly',
        array $addons = []
    ): array {
        $pricing = $this->calculator->calculate($plan, $billingCycle, $addons);
        $receipt = 'rcpt_'.Str::lower(Str::random(12));

        $notes = [
            'tenant_id' => (string) $tenant->id,
            'app_id' => (string) $app->id,
            'app_ulid' => (string) $app->ulid,
            'plan_id' => (string) $plan->id,
            'billing_cycle' => $billingCycle,
        ];

        // In testing or when using test keys, generate deterministic/mock order or call API
        if (app()->environment('testing') || str_starts_with($this->keyId, 'rzp_test_')) {
            $orderId = 'order_'.Str::random(14);
        } else {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->timeout(10)
                ->post('https://api.razorpay.com/v1/orders', [
                    'amount' => $pricing['amount_paise'],
                    'currency' => 'INR',
                    'receipt' => $receipt,
                    'notes' => $notes,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Failed to create Razorpay order: '.$response->body());
            }

            $orderId = (string) ($response->json('id') ?? 'order_'.Str::random(14));
        }

        return [
            'order_id' => $orderId,
            'amount' => $pricing['amount_paise'],
            'currency' => 'INR',
            'key_id' => $this->keyId,
            'notes' => $notes,
            'pricing' => $pricing,
        ];
    }

    /**
     * Create a recurring subscription on Razorpay.
     *
     * @param  Tenant  $tenant
     * @param  App  $app
     * @param  Plan  $plan
     * @param  string  $razorpayPlanId
     * @return array<string, mixed>
     */
    public function createSubscription(
        Tenant $tenant,
        App $app,
        Plan $plan,
        string $razorpayPlanId = 'plan_monthly_default'
    ): array {
        $notes = [
            'tenant_id' => (string) $tenant->id,
            'app_id' => (string) $app->id,
            'app_ulid' => (string) $app->ulid,
            'plan_id' => (string) $plan->id,
        ];

        if (app()->environment('testing') || str_starts_with($this->keyId, 'rzp_test_')) {
            return [
                'subscription_id' => 'sub_'.Str::random(14),
                'status' => 'created',
                'plan_id' => $razorpayPlanId,
                'notes' => $notes,
            ];
        }

        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->timeout(10)
            ->post('https://api.razorpay.com/v1/subscriptions', [
                'plan_id' => $razorpayPlanId,
                'total_count' => 60,
                'customer_notify' => 1,
                'notes' => $notes,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to create Razorpay subscription: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Verify payment signature returned by Razorpay checkout form.
     */
    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', "{$orderId}|{$paymentId}", $this->keySecret);

        return hash_equals($expected, $signature);
    }

    /**
     * Verify webhook signature from Razorpay.
     */
    public function verifyWebhookSignature(string $rawBody, string $signature, ?string $secret = null): bool
    {
        $key = $secret ?? $this->webhookSecret;
        $expected = hash_hmac('sha256', $rawBody, $key);

        return hash_equals($expected, $signature);
    }

    /**
     * Helper to compute signature for testing webhook payloads.
     */
    public function computeWebhookSignature(string $rawBody, ?string $secret = null): string
    {
        return hash_hmac('sha256', $rawBody, $secret ?? $this->webhookSecret);
    }
}
