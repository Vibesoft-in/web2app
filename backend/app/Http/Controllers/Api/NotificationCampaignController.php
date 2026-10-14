<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\NotificationCampaign;
use App\Models\PushDevice;
use App\Rules\ValidDeepLink;
use App\Services\Billing\EntitlementService;
use App\Services\Notifications\FcmServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationCampaignController extends Controller
{
    public function __construct(
        protected FcmServiceInterface $fcmService,
        protected EntitlementService $entitlements,
    ) {}

    /**
     * List push notification campaigns for the given app.
     */
    public function index(App $app): JsonResponse
    {
        $campaigns = NotificationCampaign::where('app_id', $app->id)
            ->latest()
            ->paginate(15);

        return response()->json($campaigns);
    }

    /**
     * Create or schedule a new push notification campaign.
     */
    public function store(Request $request, App $app): JsonResponse
    {
        // 1. RBAC check: viewers are read-only
        if ($request->user()?->role === 'viewer') {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Viewers are not authorized to create or dispatch notification campaigns.',
                ],
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // 2. Entitlement gate
        $this->entitlements->assertCan($app->tenant, 'push_notifications', $app);

        // 3. Validate input including strict deep link allowlist and future scheduled date
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:255'],
            'deep_link' => ['nullable', 'string', new ValidDeepLink],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'audience' => ['nullable', 'array'],
        ]);

        $isScheduled = ! empty($validated['scheduled_at']);

        /** @var NotificationCampaign $campaign */
        $campaign = NotificationCampaign::create([
            'tenant_id' => $app->tenant_id,
            'app_id' => $app->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'deep_link' => $validated['deep_link'] ?? null,
            'scheduled_at' => $isScheduled ? $validated['scheduled_at'] : null,
            'audience' => $validated['audience'] ?? ['target' => 'all'],
            'status' => $isScheduled ? 'scheduled' : 'sent',
        ]);

        // 4. If immediate dispatch, stream push batches in chunks to avoid memory spikes
        if (! $isScheduled) {
            PushDevice::where('app_id', $app->id)
                ->select('fcm_token')
                ->chunk(500, function ($devices) use ($app, $campaign) {
                    $tokens = $devices->pluck('fcm_token')->all();
                    if (! empty($tokens)) {
                        $this->fcmService->dispatchBatch(
                            app: $app,
                            tokens: $tokens,
                            title: $campaign->title,
                            body: $campaign->body,
                            deepLink: $campaign->deep_link,
                            campaignId: $campaign->id
                        );
                    }
                });
        }

        return response()->json([
            'message' => $isScheduled ? 'Campaign scheduled successfully.' : 'Campaign queued for dispatch.',
            'data' => $campaign,
        ], 201);
    }
}
