<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\WhatsappWallet;
use App\Services\Billing\EntitlementService;
use App\Services\Notifications\WhatsAppServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function __construct(
        protected WhatsAppServiceInterface $whatsAppService,
        protected EntitlementService $entitlements,
    ) {}

    /**
     * Send a verified test WhatsApp template message.
     */
    public function test(Request $request, App $app): JsonResponse
    {
        // 1. RBAC check: viewers are read-only and cannot trigger billable actions
        if ($request->user()?->role === 'viewer') {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Viewers are not authorized to send test WhatsApp messages.',
                ],
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // 2. Entitlement gate
        $this->entitlements->assertCan($app->tenant, 'whatsapp', $app);

        // 2. Validate phone number
        $validated = $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:20'],
        ]);

        $message = $this->whatsAppService->sendTestMessage(
            tenant: $app->tenant,
            phone: $validated['phone'],
            app: $app
        );

        $wallet = WhatsappWallet::where('tenant_id', $app->tenant_id)->first();

        return response()->json([
            'message' => 'Test WhatsApp message sent successfully.',
            'wallet_balance' => (float) ($wallet?->balance ?? 0.00),
            'data' => $message,
        ]);
    }

    /**
     * Get tenant WhatsApp wallet balance.
     */
    public function wallet(App $app): JsonResponse
    {
        $wallet = WhatsappWallet::firstOrCreate(
            ['tenant_id' => $app->tenant_id],
            ['balance' => 0.00]
        );

        return response()->json([
            'balance' => (float) $wallet->balance,
            'cost_per_message' => 0.80,
        ]);
    }
}
