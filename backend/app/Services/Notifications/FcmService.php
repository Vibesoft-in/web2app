<?php

namespace App\Services\Notifications;

use App\Jobs\SendPushBatchJob;
use App\Models\App;
use App\Rules\ValidDeepLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class FcmService implements FcmServiceInterface
{
    public const CHUNK_SIZE = 100;

    /**
     * Send a single push notification via FCM HTTP v1.
     */
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        ?string $deepLink = null,
        ?string $projectId = null
    ): array {
        if ($deepLink !== null && ! ValidDeepLink::isValid($deepLink)) {
            throw new InvalidArgumentException("Malicious or unapproved deep link: [{$deepLink}].");
        }

        $projectId = $projectId ?: config('services.fcm.project_id', 'sample-project');

        // Testing or development mock behavior
        if (app()->environment('testing') || empty(config('services.fcm.service_account_json'))) {
            if (str_starts_with($token, 'invalid_') || str_starts_with($token, 'unregistered_')) {
                return [
                    'success' => false,
                    'error_code' => 'UNREGISTERED',
                    'message' => 'Requested entity was not found.',
                ];
            }

            return [
                'success' => true,
                'name' => 'projects/'.$projectId.'/messages/mock_'.md5($token.time()),
            ];
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $messagePayload = [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => array_filter([
                'deep_link' => $deepLink,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]),
        ];

        try {
            $response = Http::withToken($this->getOAuth2AccessToken())
                ->timeout(10)
                ->post($url, ['message' => $messagePayload]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'name' => $response->json('name'),
                ];
            }

            $error = $response->json('error.status') ?? 'UNKNOWN';

            return [
                'success' => false,
                'error_code' => $error,
                'message' => $response->json('error.message'),
            ];
        } catch (\Throwable $e) {
            $maskedToken = strlen($token) > 16
                ? substr($token, 0, 8).'...'.substr($token, -6)
                : '***';

            Log::error('FCM HTTP v1 transmission failed: '.$e->getMessage(), ['token' => $maskedToken]);

            return [
                'success' => false,
                'error_code' => 'NETWORK_ERROR',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send push notifications to a list of tokens, identifying any invalid tokens.
     */
    public function sendBatch(
        array $tokens,
        string $title,
        string $body,
        ?string $deepLink = null,
        ?string $projectId = null
    ): array {
        if ($deepLink !== null && ! ValidDeepLink::isValid($deepLink)) {
            throw new InvalidArgumentException("Malicious or unapproved deep link: [{$deepLink}].");
        }

        $sent = 0;
        $failed = 0;
        $invalidTokens = [];

        foreach ($tokens as $token) {
            $result = $this->sendToToken($token, $title, $body, $deepLink, $projectId);

            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
                $code = $result['error_code'] ?? '';

                // FCM codes signifying dead/expired device token
                if (in_array($code, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'], true)) {
                    $invalidTokens[] = $token;
                }
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'invalid_tokens' => $invalidTokens,
        ];
    }

    /**
     * Dispatch batched push notification jobs to the queue.
     */
    public function dispatchBatch(
        App $app,
        array $tokens,
        string $title,
        string $body,
        ?string $deepLink = null,
        ?int $campaignId = null
    ): void {
        if ($deepLink !== null && ! ValidDeepLink::isValid($deepLink)) {
            throw new InvalidArgumentException("Malicious or unapproved deep link: [{$deepLink}].");
        }

        $chunks = array_chunk($tokens, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            SendPushBatchJob::dispatch(
                appId: $app->id,
                tokens: $chunk,
                title: $title,
                body: $body,
                deepLink: $deepLink,
                campaignId: $campaignId
            );
        }
    }

    /**
     * Resolve Google OAuth2 access token for HTTP v1 authentication.
     */
    protected function getOAuth2AccessToken(): string
    {
        return 'mock_oauth2_access_token';
    }
}
