<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Register a new tenant and owner user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $data = DB::transaction(function () use ($validated): array {
            /** @var Tenant $tenant */
            $tenant = Tenant::create([
                'name' => $validated['company_name'],
                'status' => 'trial',
            ]);

            TenantContext::set($tenant);

            /** @var User $user */
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'role' => 'owner',
            ]);

            $tenant->update(['owner_user_id' => $user->id]);

            event(new Registered($user));

            $token = $user->createToken('dashboard_token', ['tenant:'.$tenant->id])->plainTextToken;

            return [
                'user' => $user,
                'tenant' => $tenant,
                'token' => $token,
            ];
        });

        return response()->json([
            'message' => 'Registration successful. Please verify your email.',
            'data' => $data,
        ], 201);
    }

    /**
     * Authenticate user with rate limiting.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->ensureIsNotRateLimited();

        $validated = $request->validated();

        /** @var User|null $user */
        $user = User::withoutGlobalScopes()
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($request->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        RateLimiter::clear($request->throttleKey());

        $tenant = $user->tenant;
        if (! $tenant || $tenant->status === 'suspended') {
            return response()->json([
                'error' => [
                    'code' => 'TENANT_INACTIVE',
                    'message' => 'Your tenant account is suspended or inactive.',
                ],
            ], 403);
        }

        $token = $user->createToken('dashboard_token', ['tenant:'.$tenant->id])->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'user' => $user,
                'tenant' => $tenant,
                'token' => $token,
            ],
        ]);
    }

    /**
     * Log out authenticated user.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            /** @var PersonalAccessToken|null $token */
            $token = $user->currentAccessToken();
            $token?->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                'user' => $user,
                'tenant' => $user->tenant,
            ],
        ]);
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->validated());

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent to your email address.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset successfully.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    /**
     * Verify email address.
     */
    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        /** @var User|null $user */
        $user = User::withoutGlobalScopes()->find($id);

        if (! $user) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'User not found.',
                ],
            ], 404);
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_HASH',
                    'message' => 'Invalid email verification link.',
                ],
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json([
            'message' => 'Email verified successfully.',
        ]);
    }

    /**
     * Resend verification email.
     */
    public function resendVerification(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email is already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent.',
        ]);
    }
}
