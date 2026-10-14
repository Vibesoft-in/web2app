<?php

namespace App\Jobs;

use App\Models\App;
use App\Models\NotificationCampaign;
use App\Models\PushDevice;
use App\Services\Billing\UsageEventService;
use App\Services\Notifications\FcmServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 2;

    /**
     * @param  list<string>  $tokens
     */
    public function __construct(
        public int $appId,
        public array $tokens,
        public string $title,
        public string $body,
        public ?string $deepLink = null,
        public ?int $campaignId = null
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the push batch dispatch and perform invalid token cleanup.
     */
    public function handle(FcmServiceInterface $fcmService, UsageEventService $usageService): void
    {
        /** @var App|null $app */
        $app = App::find($this->appId);
        if (! $app) {
            return;
        }

        $projectId = $app->firebase_project_id ?? config('services.fcm.project_id', 'sample-project');

        $result = $fcmService->sendBatch(
            tokens: $this->tokens,
            title: $this->title,
            body: $this->body,
            deepLink: $this->deepLink,
            projectId: $projectId
        );

        // 1. Invalid Token Cleanup: Remove dead/unregistered device tokens immediately
        if (! empty($result['invalid_tokens'])) {
            PushDevice::where('app_id', $this->appId)
                ->whereIn('fcm_token', $result['invalid_tokens'])
                ->delete();

            Log::info("Cleaned up {count} invalid FCM tokens for app #{$this->appId}.", [
                'count' => count($result['invalid_tokens']),
                'app_id' => $this->appId,
            ]);
        }

        // 2. Update campaign statistics if linked
        if ($this->campaignId) {
            /** @var NotificationCampaign|null $campaign */
            $campaign = NotificationCampaign::find($this->campaignId);
            if ($campaign) {
                $campaign->increment('sent_count', $result['sent']);
                $campaign->increment('failed_count', $result['failed']);

                if ($campaign->status === 'scheduled' || $campaign->status === 'draft') {
                    $campaign->update(['status' => 'sent']);
                }
            }
        }

        // 3. Record cost & usage tracking
        if ($result['sent'] > 0) {
            $costPerPush = 0.0005; // ₹0.0005 per push
            $usageService->record(
                tenant: $app->tenant_id,
                type: 'push_notification',
                qty: $result['sent'],
                cost: round($result['sent'] * $costPerPush, 4),
                app: $app
            );
        }
    }
}
