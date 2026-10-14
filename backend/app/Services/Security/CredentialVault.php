<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Crypt;

class CredentialVault
{
    /**
     * Encrypt sensitive credentials array into a secure ciphertext string.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function encrypt(array $credentials): string
    {
        return Crypt::encryptString(json_encode($credentials, JSON_THROW_ON_ERROR));
    }

    /**
     * Alias for encrypt.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function encryptCredentials(array $credentials): string
    {
        return $this->encrypt($credentials);
    }

    /**
     * Decrypt ciphertext into credentials array.
     *
     * @return array<string, mixed>
     */
    public function decrypt(string $ciphertext): array
    {
        $json = Crypt::decryptString($ciphertext);

        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * Alias for decrypt.
     *
     * @return array<string, mixed>
     */
    public function decryptCredentials(string $ciphertext): array
    {
        return $this->decrypt($ciphertext);
    }

    /**
     * Mask consumer key (e.g. ck_****1234).
     */
    public function maskConsumerKey(?string $key): string
    {
        if (empty($key)) {
            return '';
        }

        if (strlen($key) <= 8) {
            return 'ck_****';
        }

        $prefix = substr($key, 0, 3);
        $suffix = substr($key, -4);

        return "{$prefix}****{$suffix}";
    }

    /**
     * Always mask consumer secret completely.
     */
    public function maskConsumerSecret(): string
    {
        return 'cs_****masked';
    }

    /**
     * Prepare a masked view of connection credentials suitable for UI/API.
     *
     * @param  array<string, mixed>  $credentials
     * @return array{consumer_key_masked: string, consumer_secret_masked: string}
     */
    public function getMaskedView(array $credentials): array
    {
        $key = isset($credentials['consumer_key']) && is_string($credentials['consumer_key'])
            ? $credentials['consumer_key']
            : null;

        return [
            'consumer_key_masked' => $this->maskConsumerKey($key),
            'consumer_secret_masked' => $this->maskConsumerSecret(),
        ];
    }
}
