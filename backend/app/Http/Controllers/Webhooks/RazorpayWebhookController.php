<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\SetupStep;
use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use App\Services\Billing\RazorpayService;
use App\Services\Billing\UsageEventService;
use App\Services\Setup\AppSetupStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    public function __construct(
        protected RazorpayService $razorpayService,
        protected UsageEventService $usageService,
        protected AppSetupStateMachine $stateMachine,
    ) {}

    /**
     * Handle incoming Razorpay webhook with signature verification and replay prevention.
     */
    public function handle(Request $request): JsonResponse
    {
        $rawPayload = (string) $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');

        // 1. Signature Verification
        if (empty($signature) || ! $this->razorpayService->verifyWebhookSignature($rawPayload, $signature)) {
            Log::warning('Forged or invalid Razorpay webhook signature.', [
                'ip' => $request->ip(),
                'signature' => $signature,
            ]);

            return response()->json([
                'error' => 'INVALID_SIGNATURE',
                'message' => 'Forged webhook signature rejected.',
            ], 400);
        }

        $data = json_decode($rawPayload, true);
        if (! is_array($data)) {
            return response()->json(['error' => 'MALFORMED_JSON'], 400);
        }

        $eventId = (string) ($request->header('X-Razorpay-Event-Id') ?? $data['event_id'] ?? $data['id'] ?? md5($rawPayload));
        $event = (string) ($data['event'] ?? '');

        // 2. Replay & Idempotency Check on webhook_events (unique event_id)
        if (WebhookEvent::where('source', 'razorpay')->where('event_id', $eventId)->exists()) {
            return response()->json([
                'status' => 'ignored',
                'message' => 'Event already processed',
                'event_id' => $eventId,
            ], 200);
        }

        // 3. Record raw webhook event
        $webhookRecord = WebhookEvent::create([
            'source' => 'razorpay',
            'event_id' => $eventId,
            'payload' => $data,
            'processed_at' => null,
        ]);

        // 4. Process event types idempotently
        match ($event) {
            'payment.captured', 'order.paid' => $this->handlePaymentCaptured($data),
            'subscription.charged' => $this->handleSubscriptionCharged($data),
            'subscription.cancelled', 'subscription.halted' => $this->handleSubscriptionCancelled($data),
            default => null,
        };

        $webhookRecord->update(['processed_at' => now()]);

        return response()->json([
            'status' => 'success',
            'event_id' => $eventId,
        ], 200);
    }

    /**
     * Process payment captured event.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handlePaymentCaptured(array $data): void
    {
        $paymentEntity = $data['payload']['payment']['entity'] ?? null;
        if (! is_array($paymentEntity)) {
            return;
        }

        $paymentId = (string) ($paymentEntity['id'] ?? '');
        if (empty($paymentId)) {
            return;
        }

        // Idempotency: Check if payment id already exists to prevent double payment / credit
        if (Payment::where('razorpay_payment_id', $paymentId)->exists()) {
            return;
        }

        $notes = $paymentEntity['notes'] ?? [];
        $tenantId = (int) ($notes['tenant_id'] ?? 0);
        $appId = ! empty($notes['app_id']) ? (int) $notes['app_id'] : null;
        $planId = (int) ($notes['plan_id'] ?? 1);

        /** @var Tenant|null $tenant */
        $tenant = Tenant::find($tenantId);
        if (! $tenant && $appId) {
            $app = App::find($appId);
            $tenant = $app?->tenant;
            $tenantId = $tenant?->id ?? 0;
        }

        if (! $tenant) {
            return;
        }

        $amountInRupees = round(((int) ($paymentEntity['amount'] ?? 0)) / 100, 2);
        $currency = (string) ($paymentEntity['currency'] ?? 'INR');

        DB::transaction(function () use (
            $tenant,
            $appId,
            $planId,
            $paymentId,
            $amountInRupees,
            $currency,
            $paymentEntity
        ) {
            // 1. Create Payment record with unique constraint protection
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'razorpay_payment_id' => $paymentId,
                'amount' => $amountInRupees,
                'currency' => $currency,
                'status' => 'captured',
                'raw' => $paymentEntity,
            ]);

            // 2. Activate or renew Subscription
            $sub = Subscription::where('tenant_id', $tenant->id)
                ->when($appId, fn ($q) => $q->where('app_id', $appId))
                ->latest('id')
                ->first();

            if (! $sub) {
                $sub = Subscription::create([
                    'tenant_id' => $tenant->id,
                    'app_id' => $appId,
                    'plan_id' => Plan::where('id', $planId)->exists() ? $planId : (Plan::first()?->id ?? 1),
                    'status' => 'active',
                    'razorpay_sub_id' => (string) ($paymentEntity['order_id'] ?? null),
                    'current_period_end' => now()->addDays(30),
                ]);
            } else {
                $sub->update([
                    'status' => 'active',
                    'current_period_end' => now()->addDays(30),
                ]);
            }

            // 3. Advance App setup step and update status to paid
            if ($appId) {
                /** @var App|null $app */
                $app = App::find($appId);
                if ($app) {
                    if (in_array($app->status, ['draft', 'configured', 'verified'], true)) {
                        $app->status = 'paid';
                        $app->save();
                    }

                    if ($app->setup_step === SetupStep::PAYMENT->value) {
                        $this->stateMachine->advanceTo($app, SetupStep::BUILD);
                    }
                }
            }

            // 4. Record UsageEvent for cost tracking
            $this->usageService->record(
                tenant: $tenant,
                type: 'subscription_payment',
                qty: 1,
                cost: $amountInRupees,
                app: $appId ? App::find($appId) : null
            );

            // 5. Audit Log
            AuditLog::create([
                'tenant_id' => $tenant->id,
                'actor_id' => null,
                'action' => 'payment_captured',
                'target' => "Payment #{$paymentId}",
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'meta' => [
                    'amount' => $amountInRupees,
                    'currency' => $currency,
                    'app_id' => $appId,
                ],
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Process subscription charged event.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleSubscriptionCharged(array $data): void
    {
        $subEntity = $data['payload']['subscription']['entity'] ?? null;
        if (! is_array($subEntity)) {
            return;
        }

        $subId = (string) ($subEntity['id'] ?? '');
        $sub = Subscription::where('razorpay_sub_id', $subId)->first();

        if ($sub) {
            $sub->update([
                'status' => 'active',
                'current_period_end' => now()->addDays(30),
            ]);
        }
    }

    /**
     * Process subscription cancellation event.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleSubscriptionCancelled(array $data): void
    {
        $subEntity = $data['payload']['subscription']['entity'] ?? null;
        if (! is_array($subEntity)) {
            return;
        }

        $subId = (string) ($subEntity['id'] ?? '');
        $sub = Subscription::where('razorpay_sub_id', $subId)->first();

        if ($sub) {
            $sub->update([
                'status' => 'cancelled',
            ]);

            AuditLog::create([
                'tenant_id' => $sub->tenant_id,
                'actor_id' => null,
                'action' => 'subscription_cancelled',
                'target' => "Subscription #{$sub->id}",
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'meta' => ['razorpay_sub_id' => $subId],
                'created_at' => now(),
            ]);
        }
    }
}
