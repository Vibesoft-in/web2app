<?php

namespace App\Services\Notifications;

use App\Models\App;

interface FcmServiceInterface
{
    /**
     * Send a single push notification via FCM HTTP v1.
     *
     * @param  string  $token
     * @param  string  $title
     * @param  string  $body
     * @param  string|null  $deepLink
     * @param  string|null  $projectId
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
     * @param  string  $title
     * @param  string  $body
     * @param  string|null  $deepLink
     * @param  string|null  $projectId
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
     * @param  App  $app
     * @param  list<string>  $tokens
     * @param  string  $title
     * @param  string  $body
     * @param  string|null  $deepLink
     * @param  int|null  $campaignId
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
