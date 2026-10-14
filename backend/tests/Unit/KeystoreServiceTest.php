<?php

use App\Models\App;
use App\Models\Keystore;
use App\Models\Tenant;
use App\Services\Build\KeystoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('keystore service generates valid PKCS12 keystore with SHA-1 and SHA-256 fingerprints', function () {
    Storage::fake('local');

    $tenant = Tenant::factory()->create();
    $app = App::factory()->create([
        'tenant_id' => $tenant->id,
        'package_name' => 'com.acme.shop',
        'name' => 'Acme Shop',
    ]);

    $service = new KeystoreService();
    $keystore = $service->generate($app);

    expect($keystore)->toBeInstanceOf(Keystore::class)
        ->and($keystore->alias)->toBe('com_acme_shop')
        ->and($keystore->sha1)->toMatch('/^([0-9A-F]{2}:){19}[0-9A-F]{2}$/')
        ->and($keystore->sha256)->toMatch('/^([0-9A-F]{2}:){31}[0-9A-F]{2}$/')
        ->and(Storage::disk('local')->exists($keystore->path))->toBeTrue();

    // Fingerprints getter
    $fps = $service->getFingerprints($app);
    expect($fps['sha1'])->toBe($keystore->sha1)
        ->and($fps['sha256'])->toBe($keystore->sha256);

    // Decrypted key retrieval
    $decrypted = $service->getDecryptedKey($app);
    expect($decrypted['alias'])->toBe('com_acme_shop')
        ->and(strlen($decrypted['store_password']))->toBe(32)
        ->and(strlen($decrypted['key_password']))->toBe(32)
        ->and(strlen($decrypted['binary']))->toBeGreaterThan(100);
});

test('getOrCreate reuses existing keystore instead of re-generating', function () {
    Storage::fake('local');

    $tenant = Tenant::factory()->create();
    $app = App::factory()->create(['tenant_id' => $tenant->id]);

    $service = new KeystoreService();
    $first = $service->getOrCreate($app);
    $second = $service->getOrCreate($app);

    expect($first->id)->toBe($second->id)
        ->and(Keystore::where('app_id', $app->id)->count())->toBe(1);
});
