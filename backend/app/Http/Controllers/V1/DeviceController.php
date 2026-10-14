<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\PushDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * Register or update FCM push device token.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'in:android,ios'],
        ]);

        /** @var App $app */
        $app = $request->attributes->get('app');
        /** @var \App\Models\AppUser|\App\Models\User|null $user */
        $user = $request->user() ?? auth('sanctum')->user();

        PushDevice::updateOrCreate(
            [
                'tenant_id' => $app->tenant_id,
                'app_id' => $app->id,
                'fcm_token' => (string) $request->fcm_token,
            ],
            [
                'app_user_id' => $user?->getKey(),
                'platform' => (string) ($request->platform ?? 'android'),
            ]
        );


        return response()->json([
            'message' => 'Push notification device registered successfully.',
        ]);
    }

    /**
     * Unregister FCM push device token on customer logout.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        /** @var App $app */
        $app = $request->attributes->get('app');

        PushDevice::where('app_id', $app->id)
            ->where('fcm_token', (string) $request->fcm_token)
            ->delete();

        return response()->json([
            'message' => 'Device unhooked successfully.',
        ]);
    }
}
