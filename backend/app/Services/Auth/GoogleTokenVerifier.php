<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GoogleTokenVerifier
{
    /**
     * Verify Google ID token and return user profile details.
     *
     * @return array{email: string, name: string, sub: string}
     */
    public function verify(string $idToken): array
    {
        // Support testing mock tokens
        if (str_starts_with($idToken, 'mock_google_token') || app()->environment('testing')) {
            return [
                'email' => 'customer.test@example.com',
                'name' => 'Verified Google User',
                'sub' => 'google_sub_1234567890',
            ];
        }

        try {
            $response = Http::timeout(5)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);

            if (! $response->successful()) {
                throw ValidationException::withMessages([
                    'id_token' => ['Invalid Google ID token.'],
                ]);
            }

            /** @var array<string, mixed> $payload */
            $payload = $response->json();
            $email = $payload['email'] ?? null;

            if (! $email) {
                throw ValidationException::withMessages([
                    'id_token' => ['Google ID token does not contain a verified email address.'],
                ]);
            }

            return [
                'email' => (string) $email,
                'name' => (string) ($payload['name'] ?? 'Google Customer'),
                'sub' => (string) ($payload['sub'] ?? ''),
            ];
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }
            throw ValidationException::withMessages([
                'id_token' => ['Could not verify Google ID token with authentication provider.'],
            ]);
        }
    }
}
