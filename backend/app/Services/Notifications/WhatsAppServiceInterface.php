<?php

namespace App\Services\Notifications;

use App\Models\App;
use App\Models\Tenant;
use App\Models\WhatsappMessage;

interface WhatsAppServiceInterface
{
    /**
     * Send a template-only message through WhatsApp Cloud API after debiting the wallet.
     *
     * @param  Tenant  $tenant
     * @param  string  $phone
     * @param  string  $templateName
     * @param  array<string, mixed>  $parameters
     * @param  App|null  $app
     * @return WhatsappMessage
     */
    public function sendTemplateMessage(
        Tenant $tenant,
        string $phone,
        string $templateName,
        array $parameters = [],
        ?App $app = null
    ): WhatsappMessage;

    /**
     * Send a verified test greeting message.
     */
    public function sendTestMessage(Tenant $tenant, string $phone, ?App $app = null): WhatsappMessage;

    /**
     * Handle incoming delivery receipt status webhook from WhatsApp.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleDeliveryWebhook(array $payload): void;
}
