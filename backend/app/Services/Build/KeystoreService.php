<?php

namespace App\Services\Build;

use App\Models\App;
use App\Models\Keystore;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class KeystoreService
{
    /**
     * Get existing keystore or generate a new one for the app.
     */
    public function getOrCreate(App $app): Keystore
    {
        /** @var Keystore|null $existing */
        $existing = Keystore::where('app_id', $app->id)->first();
        if ($existing) {
            return $existing;
        }

        return $this->generate($app);
    }

    /**
     * Generate an Android release keystore (PKCS#12) and store it securely.
     */
    public function generate(App $app): Keystore
    {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '_', $app->package_name) ?: 'upload_key';
        $storePassword = Str::random(32);
        $keyPassword = Str::random(32);

        $cnfPath = $this->resolveOpenSslConfig();
        $configArgs = $cnfPath ? ['config' => $cnfPath] : [];

        // Generate 2048-bit RSA private key
        $privKey = openssl_pkey_new(array_merge([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ], $configArgs));

        if (! $privKey) {
            throw new RuntimeException('Failed to generate OpenSSL private key: '.openssl_error_string());
        }

        $dn = [
            'countryName' => 'IN',
            'stateOrProvinceName' => 'Karnataka',
            'localityName' => 'Bengaluru',
            'organizationName' => $app->name ?: 'Store App',
            'organizationalUnitName' => 'Mobile Engineering',
            'commonName' => $app->package_name,
        ];

        $csr = openssl_csr_new($dn, $privKey, array_merge(['digest_alg' => 'sha256'], $configArgs));
        if (! $csr) {
            throw new RuntimeException('Failed to generate CSR for keystore: '.openssl_error_string());
        }

        // Validity: 25 years (9125 days) required for Android release app signing
        $x509 = openssl_csr_sign($csr, null, $privKey, 9125, array_merge(['digest_alg' => 'sha256'], $configArgs));
        if (! $x509) {
            throw new RuntimeException('Failed to sign X.509 certificate for keystore: '.openssl_error_string());
        }

        $p12Data = '';
        $exported = openssl_pkcs12_export($x509, $p12Data, $privKey, $storePassword, [
            'friendly_name' => $alias,
        ]);

        if (! $exported || empty($p12Data)) {
            throw new RuntimeException('Failed to export PKCS#12 keystore: '.openssl_error_string());
        }

        // Export PEM certificate to compute DER SHA-1 and SHA-256 fingerprints
        $certPem = '';
        openssl_x509_export($x509, $certPem);
        $certDer = base64_decode(
            preg_replace('/-----(?:BEGIN|END) CERTIFICATE-----|\s+/', '', $certPem) ?? ''
        );

        $sha1 = strtoupper(implode(':', str_split(sha1($certDer), 2)));
        $sha256 = strtoupper(implode(':', str_split(hash('sha256', $certDer), 2)));

        // Store encrypted keystore binary in private storage disk
        $storagePath = "tenants/{$app->tenant_id}/apps/{$app->id}/keystore.jks";
        Storage::disk('local')->put($storagePath, $p12Data);

        return Keystore::create([
            'tenant_id' => $app->tenant_id,
            'app_id' => $app->id,
            'path' => $storagePath,
            'alias' => $alias,
            'passwords' => [
                'store_password' => $storePassword,
                'key_password' => $keyPassword,
            ],
            'sha1' => $sha1,
            'sha256' => $sha256,
        ]);
    }

    /**
     * Get SHA-1 and SHA-256 fingerprints for Firebase and Google Play App Signing.
     *
     * @return array{sha1: string, sha256: string}
     */
    public function getFingerprints(App $app): array
    {
        $keystore = $this->getOrCreate($app);

        return [
            'sha1' => (string) $keystore->sha1,
            'sha256' => (string) $keystore->sha256,
        ];
    }

    /**
     * Retrieve decrypted keystore binary and credentials for signing.
     *
     * @return array{binary: string, alias: string, store_password: string, key_password: string}
     */
    public function getDecryptedKey(App $app): array
    {
        $keystore = $this->getOrCreate($app);
        $binary = Storage::disk('local')->get($keystore->path);

        if (! $binary) {
            throw new RuntimeException("Keystore file not found at [{$keystore->path}].");
        }

        $passwords = $keystore->passwords;

        return [
            'binary' => $binary,
            'alias' => $keystore->alias,
            'store_password' => (string) ($passwords['store_password'] ?? ''),
            'key_password' => (string) ($passwords['key_password'] ?? ''),
        ];
    }

    /**
     * Locate or create an openssl.cnf file for Windows/Linux environments.
     */
    protected function resolveOpenSslConfig(): ?string
    {
        $envPath = getenv('OPENSSL_CONF');
        if ($envPath && file_exists($envPath)) {
            return $envPath;
        }

        $candidates = [
            dirname(PHP_BINARY).'/extras/ssl/openssl.cnf',
            'C:/Users/msans/AppData/Local/Microsoft/WinGet/Packages/PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe/extras/ssl/openssl.cnf',
            'C:/xampp/apache/bin/openssl.cnf',
            'C:/xampp/php/extras/ssl/openssl.cnf',
            '/etc/ssl/openssl.cnf',
            '/etc/pki/tls/openssl.cnf',
        ];

        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                putenv("OPENSSL_CONF={$cand}");

                return $cand;
            }
        }

        // Fallback: write minimal openssl configuration file
        $tempPath = storage_path('app/openssl.cnf');
        if (! file_exists($tempPath)) {
            $minimal = "[ req ]\ndefault_bits = 2048\ndistinguished_name = req_distinguished_name\n[ req_distinguished_name ]\n";
            @file_put_contents($tempPath, $minimal);
        }

        putenv("OPENSSL_CONF={$tempPath}");

        return $tempPath;
    }
}
