<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppUser;
use App\Models\Integration;
use App\Models\PushDevice;
use App\Models\WebhookEvent;
use App\Services\Billing\EntitlementService;
use App\Services\Notifications\FcmServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WooWebhookController extends Controller
{
    /**
     * Handle incoming WooCommerce webhook notification.
     */
    public function handle(Request $request, string $ulid): JsonResponse
    {
        /** @var App|null $app */
        $app = App::withoutGlobalScopes()->where('ulid', $ulid)->first();

        if (! $app) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'App not found.',
                ],
            ], 404);
        }

        $signature = $request->header('X-WC-Webhook-Signature');
        if (empty($signature)) {
            return response()->json([
                'error' => [
                    'code' => 'MISSING_SIGNATURE',
                    'message' => 'Missing X-WC-Webhook-Signature header.',
                ],
            ], 400);
        }

        // Retrieve webhook secret from integration or connection credentials
        $webhookSecret = $this->resolveWebhookSecret($app);
        if (empty($webhookSecret)) {
            return response()->json([
                'error' => [
                    'code' => 'WEBHOOK_NOT_CONFIGURED',
                    'message' => 'Webhook secret is not configured for this app.',
                ],
            ], 400);
        }

        // Verify HMAC-SHA256 signature
        $rawPayload = $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $rawPayload, $webhookSecret, true));

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_SIGNATURE',
                    'message' => 'Webhook signature verification failed.',
                ],
            ], 401);
        }

        // Replay and idempotency protection
        $deliveryId = $request->header('X-WC-Webhook-Delivery-ID')
            ?? $request->header('X-WC-Webhook-ID')
            ?? hash('sha256', $signature.$rawPayload);

        $exists = WebhookEvent::where('source', 'woo')
            ->where('event_id', (string) $deliveryId)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Event already processed.',
            ], 200);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        WebhookEvent::create([
            'source' => 'woo',
            'event_id' => (string) $deliveryId,
            'payload' => $payload,
            'processed_at' => now(),
        ]);

        // Trigger order status push notification if webhook relates to order lifecycle
        $topic = (string) ($request->header('X-WC-Webhook-Topic') ?? '');
        $this->handleOrderStatusPush($app, $topic, $payload);

        return response()->json([
            'status' => 'received',
            'event_id' => $deliveryId,
        ]);
    }

    /**
     * Dispatch order status push notification to the customer's push devices.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleOrderStatusPush(App $app, string $topic, array $payload): void
    {
        if (! str_starts_with($topic, 'order.')) {
            return;
        }

        $orderId = $payload['id'] ?? null;
        $orderStatus = $payload['status'] ?? 'updated';
        $customerId = (string) ($payload['customer_id'] ?? '');

        if (! $orderId) {
            return;
        }

        // Check push entitlement
        if (! app(EntitlementService::class)->can($app->tenant, 'push_notifications', $app)) {
            return;
        }

        $phone = $payload['billing']['phone'] ?? null;
        $hasCustomerId = ! empty($customerId) && $customerId !== '0';
        $hasPhone = ! empty($phone);

        // Do not query or send if neither customer ID nor billing phone is present (e.g. anonymous guest order)
        if (! $hasCustomerId && ! $hasPhone) {
            return;
        }

        $appUser = AppUser::withoutGlobalScopes()
            ->where('app_id', $app->id)
            ->where(function ($q) use ($hasCustomerId, $customerId, $hasPhone, $phone) {
                if ($hasCustomerId) {
                    $q->where('store_customer_id', $customerId);
                }
                if ($hasPhone) {
                    if ($hasCustomerId) {
                        $q->orWhere('phone', $phone);
                    } else {
                        $q->where('phone', $phone);
                    }
                }
            })
            ->first();

        if (! $appUser) {
            return;
        }

        $tokens = PushDevice::withoutGlobalScopes()
            ->where('app_id', $app->id)
            ->where('app_user_id', $appUser->id)
            ->pluck('fcm_token')
            ->all();

        if (empty($tokens)) {
            return;
        }

        $statusTitle = "Order #{$orderId} Update";
        $statusBody = "Your order #{$orderId} is now ".ucfirst((string) $orderStatus).'.';
        $deepLink = "order:{$orderId}";

        app(FcmServiceInterface::class)->dispatchBatch(
            app: $app,
            tokens: $tokens,
            title: $statusTitle,
            body: $statusBody,
            deepLink: $deepLink
        );
    }

    /**
     * Resolve webhook secret for app.
     */
    protected function resolveWebhookSecret(App $app): ?string
    {
        /** @var Integration|null $integration */
        $integration = Integration::withoutGlobalScopes()
            ->where('app_id', $app->id)
            ->where('type', 'webhook')
            ->first();

        if ($integration && ! empty($integration->credentials['secret'])) {
            return (string) $integration->credentials['secret'];
        }

        // Fallback to app's unique secret derived from app_key
        return hash('sha256', $app->app_key.'_webhook_secret');
    }
}
