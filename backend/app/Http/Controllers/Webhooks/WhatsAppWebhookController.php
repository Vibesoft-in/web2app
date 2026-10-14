<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Notifications\WhatsAppServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppServiceInterface $whatsAppService,
    ) {}

    /**
     * Handle Meta WhatsApp webhook verification challenge (GET).
     */
    public function verify(Request $request): Response
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode'));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge'));

        $expectedToken = config('services.whatsapp.webhook_verify_token', 'sample_whatsapp_verify_token');

        if ($mode === 'subscribe' && hash_equals($expectedToken, $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming WhatsApp delivery status notification webhook (POST).
     */
    public function handle(Request $request): JsonResponse
    {
        $appSecret = config('services.whatsapp.app_secret');
        if (! empty($appSecret)) {
            $signature = (string) $request->header('X-Hub-Signature-256', '');
            if (! str_starts_with($signature, 'sha256=')) {
                return response()->json(['error' => 'Missing or invalid signature header.'], 401);
            }

            $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);
            if (! hash_equals($expectedSignature, $signature)) {
                return response()->json(['error' => 'Signature verification failed.'], 401);
            }
        }

        $payload = $request->all();

        $this->whatsAppService->handleDeliveryWebhook($payload);

        return response()->json([
            'status' => 'received',
        ], 200);
    }
}
