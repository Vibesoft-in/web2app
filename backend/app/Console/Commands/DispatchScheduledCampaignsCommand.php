<?php

namespace App\Console\Commands;

use App\Models\NotificationCampaign;
use App\Models\PushDevice;
use App\Services\Billing\EntitlementService;
use App\Services\Notifications\FcmServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchScheduledCampaignsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:dispatch-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch due scheduled push notification campaigns to target mobile devices';

    /**
     * Execute the console command.
     */
    public function handle(FcmServiceInterface $fcmService, EntitlementService $entitlements): int
    {
        $dueCampaigns = NotificationCampaign::withoutGlobalScopes()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->with(['app.tenant'])
            ->get();

        $count = $dueCampaigns->count();
        if ($count === 0) {
            $this->info('No due scheduled notification campaigns found.');

            return self::SUCCESS;
        }

        $this->info("Processing {$count} due notification campaign(s)...");

        foreach ($dueCampaigns as $campaign) {
            $app = $campaign->app;
            if (! $app || ! $app->tenant) {
                $campaign->update(['status' => 'failed']);
                continue;
            }

            // Verify tenant push notification entitlement at dispatch time
            if (! $entitlements->can($app->tenant, 'push_notifications', $app)) {
                $campaign->update(['status' => 'failed']);
                Log::warning("Scheduled campaign #{$campaign->id} failed: Tenant lacks push notification entitlement at dispatch time.");
                continue;
            }

            $campaign->update(['status' => 'sent']);

            PushDevice::withoutGlobalScopes()
                ->where('app_id', $app->id)
                ->select('fcm_token')
                ->chunk(500, function ($devices) use ($app, $campaign, $fcmService) {
                    $tokens = $devices->pluck('fcm_token')->all();
                    if (! empty($tokens)) {
                        $fcmService->dispatchBatch(
                            app: $app,
                            tokens: $tokens,
                            title: $campaign->title,
                            body: $campaign->body,
                            deepLink: $campaign->deep_link,
                            campaignId: $campaign->id
                        );
                    }
                });

            Log::info("Dispatched scheduled campaign #{$campaign->id} for app #{$app->id}.");
        }

        $this->info("Successfully processed {$count} scheduled campaign(s).");

        return self::SUCCESS;
    }
}
