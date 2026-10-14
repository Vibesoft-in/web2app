<?php

namespace App\Services\Notifications;

use App\Exceptions\InsufficientWalletBalanceException;
use App\Models\App;
use App\Models\Tenant;
use App\Models\WhatsappMessage;
use App\Models\WhatsappWallet;
use App\Services\Billing\EntitlementService;
use App\Services\Billing\UsageEventService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class WhatsAppService implements WhatsAppServiceInterface
{
    public const COST_PER_MESSAGE = 0.8000; // ₹0.80 per template message

    public const APPROVED_TEMPLATES = [
        'order_update',
        'shipping_notification',
        'otp_verification',
        'test_greeting',
    ];

    public function __construct(
        protected EntitlementService $entitlements,
        protected UsageEventService $usageService,
    ) {}

    /**
     * Send a template-only message through WhatsApp Cloud API after debiting the wallet.
     */
    public function sendTemplateMessage(
        Tenant $tenant,
        string $phone,
        string $templateName,
        array $parameters = [],
        ?App $app = null
    ): WhatsappMessage {
        // 1. Entitlement check
        $this->entitlements->assertCan($tenant, 'whatsapp', $app);

        // 2. Enforce template-only allowlist
        if (! in_array($templateName, self::APPROVED_TEMPLATES, true)) {
            throw new InvalidArgumentException(
                "WhatsApp Cloud API requires pre-approved templates. [{$templateName}] is not in the approved template list."
            );
        }

        // Clean phone number (digits only, e.g. 919876543210, E.164 10-15 digits)
        $cleanPhone = preg_replace('/\D+/', '', $phone);
        if (strlen((string) $cleanPhone) < 10 || strlen((string) $cleanPhone) > 15) {
            throw new InvalidArgumentException("Invalid destination phone number: [{$phone}]. Must be 10 to 15 digits.");
        }

        // 3. Wallet Debit Before Send inside atomic transaction
        /** @var WhatsappMessage $message */
        $message = DB::transaction(function () use ($tenant, $cleanPhone, $templateName, $parameters, $app) {
            /** @var WhatsappWallet|null $wallet */
            $wallet = WhatsappWallet::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = WhatsappWallet::create([
                    'tenant_id' => $tenant->id,
                    'balance' => 0.00,
                ]);
                $wallet = WhatsappWallet::withoutGlobalScopes()
                    ->where('id', $wallet->id)
                    ->lockForUpdate()
                    ->first();
            }

            if ((float) $wallet->balance < self::COST_PER_MESSAGE) {
                throw new InsufficientWalletBalanceException(
                    'Insufficient WhatsApp wallet balance (required: ₹'.number_format(self::COST_PER_MESSAGE, 2).', available: ₹'.number_format((float) $wallet->balance, 2).'). Please recharge your wallet.'
                );
            }

            // Debit wallet before sending
            $wallet->decrement('balance', self::COST_PER_MESSAGE);

            return WhatsappMessage::create([
                'tenant_id' => $tenant->id,
                'app_id' => $app?->id,
                'phone' => $cleanPhone,
                'template_name' => $templateName,
                'status' => 'queued',
                'cost' => self::COST_PER_MESSAGE,
                'meta' => ['parameters' => $parameters],
            ]);
        });

        // 4. Dispatch to WhatsApp Cloud API
        try {
            $wamid = $this->callCloudApi($cleanPhone, $templateName, $parameters, $app);

            $message->update([
                'status' => 'sent',
                'whatsapp_message_id' => $wamid,
            ]);

            // Record usage event
            $this->usageService->record(
                tenant: $tenant,
                type: 'whatsapp_message',
                qty: 1,
                cost: self::COST_PER_MESSAGE,
                app: $app
            );

            return $message;
        } catch (\Throwable $e) {
            // Refund wallet upon dispatch failure (safely bypassing global scopes)
            WhatsappWallet::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->increment('balance', self::COST_PER_MESSAGE);

            $message->update([
                'status' => 'failed',
                'meta' => array_merge($message->meta ?? [], ['error' => $e->getMessage()]),
            ]);

            Log::error('WhatsApp message transmission failed. Wallet refunded.', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send a verified test greeting message.
     */
    public function sendTestMessage(Tenant $tenant, string $phone, ?App $app = null): WhatsappMessage
    {
        return $this->sendTemplateMessage(
            tenant: $tenant,
            phone: $phone,
            templateName: 'test_greeting',
            parameters: [
                'recipient_name' => 'Store Owner',
                'timestamp' => now()->toFormattedDateString(),
            ],
            app: $app
        );
    }

    /**
     * Handle incoming delivery receipt status webhook from WhatsApp.
     */
    public function handleDeliveryWebhook(array $payload): void
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $statuses = $change['value']['statuses'] ?? [];
                foreach ($statuses as $statusUpdate) {
                    $wamid = $statusUpdate['id'] ?? null;
                    $status = $statusUpdate['status'] ?? null; // delivered, read, failed

                    if ($wamid && in_array($status, ['sent', 'delivered', 'read', 'failed'], true)) {
                        WhatsappMessage::withoutGlobalScopes()
                            ->where('whatsapp_message_id', $wamid)
                            ->update([
                                'status' => $status,
                            ]);
                    }
                }
            }
        }
    }

    /**
     * Execute transmission to Meta WhatsApp Cloud API.
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function callCloudApi(string $phone, string $templateName, array $parameters, ?App $app): string
    {
        // Testing or development mock behavior unless simulated failure or explicit access_token
        if (str_contains($phone, '0000000000') || str_starts_with($phone, '9999999999') || config('services.whatsapp.simulate_failure')) {
            throw new RuntimeException('WhatsApp Cloud API simulated transmission failure.');
        }

        if (app()->environment('testing') && empty(config('services.whatsapp.access_token'))) {
            return 'wamid.HBg'.Str::random(24);
        }

        $phoneNumberId = config('services.whatsapp.phone_number_id', 'sample_phone_number_id');
        $accessToken = config('services.whatsapp.access_token');
        $url = "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages";

        $response = Http::withToken($accessToken)
            ->timeout(10)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => 'en_US'],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('WhatsApp Cloud API call failed: '.$response->body());
        }

        return (string) ($response->json('messages.0.id') ?? 'wamid.'.Str::random(24));
    }
}
