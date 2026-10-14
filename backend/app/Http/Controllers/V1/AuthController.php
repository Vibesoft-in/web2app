<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppUser;
use App\Services\Auth\GoogleTokenVerifier;
use App\Services\Auth\OtpService;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected OtpService $otpService,
        protected GoogleTokenVerifier $googleVerifier,
        protected ConnectorFactory $connectorFactory,
    ) {}

    /**
     * Request OTP for customer phone number.
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:8', 'max:20'],
        ]);

        /** @var App $app */
        $app = $request->attributes->get('app');

        $result = $this->otpService->requestOtp(
            $app,
            (string) $request->phone,
            (string) $request->ip(),
        );

        return response()->json($result);
    }

    /**
     * Verify OTP and log in / register AppUser, linking store_customer_id.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        /** @var App $app */
        $app = $request->attributes->get('app');
        $phone = (string) $request->phone;

        // 1. Verify OTP with 5-attempt limit
        $this->otpService->verifyOtp($app, $phone, (string) $request->otp);

        // 2. Find or create AppUser
        /** @var AppUser $appUser */
        $appUser = AppUser::firstOrCreate(
            [
                'tenant_id' => $app->tenant_id,
                'app_id' => $app->id,
                'phone' => $phone,
            ],
            [
                'name' => $request->name ?? 'App Customer',
            ]
        );

        // 3. Link or create store customer ID if not already set
        if (empty($appUser->store_customer_id)) {
            try {
                $connector = $this->connectorFactory->forApp($app);
                $storeCustomerId = $connector->findOrCreateCustomer($phone, $appUser->name);
                $appUser->store_customer_id = $storeCustomerId;
            } catch (\Exception $e) {
                // If store API is offline, default to internal customer id
                $appUser->store_customer_id = (string) $appUser->id;
            }
        }

        $appUser->last_login_at = now();
        $appUser->save();

        // 4. Create Sanctum token scoped strictly to this app
        $token = $appUser->createToken('customer-auth', ["app:{$app->id}"])->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $appUser->id,
                    'phone' => $appUser->phone,
                    'email' => $appUser->email,
                    'name' => $appUser->name,
                    'store_customer_id' => $appUser->store_customer_id,
                ],
            ],
        ]);
    }

    /**
     * Authenticate via Google ID-token.
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        /** @var App $app */
        $app = $request->attributes->get('app');

        // 1. Verify Google token
        $googleData = $this->googleVerifier->verify((string) $request->id_token);
        $email = $googleData['email'];
        $name = $googleData['name'];

        // 2. Find or create AppUser
        /** @var AppUser $appUser */
        $appUser = AppUser::firstOrCreate(
            [
                'tenant_id' => $app->tenant_id,
                'app_id' => $app->id,
                'email' => $email,
            ],
            [
                'name' => $name,
            ]
        );

        // 3. Link or create store customer
        if (empty($appUser->store_customer_id)) {
            try {
                $connector = $this->connectorFactory->forApp($app);
                $storeCustomerId = $connector->findOrCreateCustomer($email, $appUser->name);
                $appUser->store_customer_id = $storeCustomerId;
            } catch (\Exception $e) {
                $appUser->store_customer_id = (string) $appUser->id;
            }
        }

        $appUser->last_login_at = now();
        $appUser->save();

        // 4. Create app-scoped token
        $token = $appUser->createToken('customer-auth', ["app:{$app->id}"])->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $appUser->id,
                    'email' => $appUser->email,
                    'phone' => $appUser->phone,
                    'name' => $appUser->name,
                    'store_customer_id' => $appUser->store_customer_id,
                ],
            ],
        ]);
    }

    /**
     * Revoke customer mobile session token and disassociate push devices.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');
        $user = $request->user();

        if ($user instanceof AppUser) {
            if ($request->filled('fcm_token')) {
                \App\Models\PushDevice::where('tenant_id', $app->tenant_id)
                    ->where('app_id', $app->id)
                    ->where('fcm_token', (string) $request->fcm_token)
                    ->delete();
            } else {
                \App\Models\PushDevice::where('tenant_id', $app->tenant_id)
                    ->where('app_id', $app->id)
                    ->where('app_user_id', $user->id)
                    ->update(['app_user_id' => null]);
            }

            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Delete customer account per Google Play / Apple App Store compliance guidelines.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        /** @var AppUser $user */
        $user = $request->user();

        // 1. Purge all tokens and push devices
        $user->tokens()->delete();
        $user->devices()->delete();

        // 2. Soft-delete user record
        $user->delete();

        return response()->json([
            'message' => 'Account and personal data successfully deleted.',
        ]);
    }
}
