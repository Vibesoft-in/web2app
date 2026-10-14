<?php

namespace App\Services\Notifications;

use App\Models\App;

interface FcmServiceInterface
{
    /**
     * Send a single push notification via FCM HTTP v1.
     *
     * @return array<string, mixed>
     */
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        ?string $deepLink = null,
        ?string $projectId = null
    ): array;

    /**
     * Send push notifications to a list of tokens, identifying any invalid tokens.
     *
     * @param  list<string>  $tokens
     * @return array{
     *     sent: int,
     *     failed: int,
     *     invalid_tokens: list<string>
     * }
     */
    public function sendBatch(
        array $tokens,
        string $title,
        string $body,
        ?string $deepLink = null,
        ?string $projectId = null
    ): array;

    /**
     * Dispatch batched push notification jobs to the queue.
     *
     * @param  list<string>  $tokens
     */
    public function dispatchBatch(
        App $app,
        array $tokens,
        string $title,
        string $body,
        ?string $deepLink = null,
        ?int $campaignId = null
    ): void;
}
