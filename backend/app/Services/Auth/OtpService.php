<?php

namespace App\Services\Auth;

use App\Models\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const EXPIRY_SECONDS = 300;     // 5 minutes

    public const COOLDOWN_SECONDS = 30;    // 30 seconds

    public const MAX_ATTEMPTS = 5;

    public const MAX_REQUESTS_PER_10_MIN = 3;

    /**
     * Request a 6-digit OTP for customer login.
     *
     * @return array{message: string, expires_in: int, resend_after: int, debug_otp?: string}
     */
    public function requestOtp(App $app, string $phone, string $ip): array
    {
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        $phoneHash = md5($cleanPhone);
        $ipHash = md5($ip);

        $cooldownKey = "tenant:{$app->tenant_id}:app:{$app->id}:otp:cooldown:{$phoneHash}";
        $reqLimitKey = "tenant:{$app->tenant_id}:app:{$app->id}:otp:limit:{$phoneHash}:{$ipHash}";
        $dataKey = "tenant:{$app->tenant_id}:app:{$app->id}:otp:data:{$phoneHash}";

        // 1. Check 30-second resend cooldown
        if (Cache::has($cooldownKey)) {
            $ttl = (int) (Cache::get($cooldownKey) - time());
            throw ValidationException::withMessages([
                'phone' => ["Please wait {$ttl} seconds before requesting a new OTP."],
            ]);
        }

        // 2. Check 3 requests per 10 minutes limit
        $requestCount = (int) Cache::get($reqLimitKey, 0);
        if ($requestCount >= self::MAX_REQUESTS_PER_10_MIN) {
            throw ValidationException::withMessages([
                'phone' => ['Too many OTP requests. Please wait 10 minutes before trying again.'],
            ]);
        }

        // 3. Generate 6-digit numeric OTP
        $otp = sprintf('%06d', random_int(100000, 999999));
        $hashed = Hash::make($otp);

        // 4. Store in Cache with 5-minute expiry
        Cache::put($dataKey, [
            'hash' => $hashed,
            'attempts' => 0,
        ], self::EXPIRY_SECONDS);

        // 5. Set 30-second cooldown
        Cache::put($cooldownKey, time() + self::COOLDOWN_SECONDS, self::COOLDOWN_SECONDS);

        // 6. Increment request counter (10 minutes window)
        Cache::put($reqLimitKey, $requestCount + 1, 600);

        $response = [
            'message' => 'OTP sent successfully.',
            'expires_in' => self::EXPIRY_SECONDS,
            'resend_after' => self::COOLDOWN_SECONDS,
        ];

        // For unit/feature testing or local demo, expose debug_otp
        if (app()->environment(['local', 'testing'])) {
            $response['debug_otp'] = $otp;
        }

        return $response;
    }

    /**
     * Verify submitted OTP against stored hash with max 5 attempts.
     */
    public function verifyOtp(App $app, string $phone, string $otp): bool
    {
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        $phoneHash = md5($cleanPhone);
        $dataKey = "tenant:{$app->tenant_id}:app:{$app->id}:otp:data:{$phoneHash}";

        /** @var array{hash: string, attempts: int}|null $data */
        $data = Cache::get($dataKey);

        if (! $data) {
            throw ValidationException::withMessages([
                'otp' => ['OTP has expired or does not exist. Please request a new code.'],
            ]);
        }

        // Check max attempts
        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($dataKey);
            throw ValidationException::withMessages([
                'otp' => ['Too many failed attempts. This OTP has been invalidated.'],
            ]);
        }

        if (! Hash::check($otp, $data['hash'])) {
            $attempts = $data['attempts'] + 1;
            $data['attempts'] = $attempts;

            if ($attempts >= self::MAX_ATTEMPTS) {
                Cache::forget($dataKey);
                throw ValidationException::withMessages([
                    'otp' => ['Maximum verification attempts exceeded. Please request a new OTP.'],
                ]);
            }

            Cache::put($dataKey, $data, self::EXPIRY_SECONDS);
            $remaining = self::MAX_ATTEMPTS - $attempts;

            throw ValidationException::withMessages([
                'otp' => ["Invalid OTP code. {$remaining} attempts remaining."],
            ]);
        }

        // Verification successful: consume and clear OTP
        Cache::forget($dataKey);

        return true;
    }
}
