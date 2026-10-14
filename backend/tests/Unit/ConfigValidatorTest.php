<?php

use App\Services\Config\ConfigValidator;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->validator = new ConfigValidator;
});

test('config validator accepts valid theme, features, and home sections', function () {
    $validConfig = [
        'schema_version' => 1,
        'app' => [
            'name' => 'Fashion Boutique',
            'currency' => 'INR',
            'min_version' => '1.0.0',
            'maintenance' => false,
        ],
        'theme' => [
            'layout' => 'theme_a',
            'colors' => [
                'primary' => '#111827',
                'secondary' => '#4B5563',
                'background' => '#FFFFFF',
                'surface' => '#F9FAFB',
                'text' => '#111827',
            ],
            'radius' => 12,
            'font' => 'Poppins',
            'nav' => 'bottom',
        ],
        'features' => [
            'wishlist' => true,
            'otp_login' => true,
            'google_login' => true,
            'notifications' => true,
            'reviews' => false,
        ],
        'home' => [
            [
                'type' => 'banner',
                'items' => [
                    ['image' => 'https://cdn.example.com/banner1.jpg', 'link' => 'category:12'],
                ],
            ],
            ['type' => 'categories'],
            ['type' => 'product_rail', 'title' => 'Top Trends', 'source' => 'recent'],
        ],
        'links' => [
            'privacy' => 'https://mystore.example.com/privacy',
            'terms' => 'https://mystore.example.com/terms',
            'support_phone' => '+919876543210',
        ],
        'webview_allowed_hosts' => ['mystore.example.com'],
    ];

    $validated = $this->validator->validate($validConfig);

    expect($validated)->toBeArray()
        ->and($validated['theme']['colors']['primary'])->toBe('#111827');
});

test('config validator rejects non-hex color values', function () {
    $config = [
        'theme' => [
            'layout' => 'theme_a',
            'colors' => [
                'primary' => 'red', // Named colors disallowed
                'secondary' => '#12345GG', // Invalid hex character
            ],
        ],
    ];

    expect(fn () => $this->validator->validate($config))
        ->toThrow(ValidationException::class);
});

test('config validator rejects plain unencrypted http URLs', function () {
    $config = [
        'theme' => [
            'layout' => 'theme_a',
            'colors' => ['primary' => '#000000'],
        ],
        'links' => [
            'privacy' => 'http://insecure-store.com/privacy', // Insecure HTTP disallowed
        ],
    ];

    expect(fn () => $this->validator->validate($config))
        ->toThrow(ValidationException::class);
});

test('config validator rejects javascript script injection in URLs', function () {
    $config = [
        'theme' => [
            'layout' => 'theme_a',
            'colors' => ['primary' => '#000000'],
        ],
        'home' => [
            [
                'type' => 'banner',
                'items' => [
                    ['image' => 'javascript:alert(document.cookie)'],
                ],
            ],
        ],
    ];

    expect(fn () => $this->validator->validate($config))
        ->toThrow(ValidationException::class);
});

test('config validator enforces string length limits', function () {
    $config = [
        'app' => [
            'name' => str_repeat('A', 51), // Max 50
        ],
        'theme' => [
            'layout' => 'theme_a',
            'colors' => ['primary' => '#000000'],
        ],
    ];

    expect(fn () => $this->validator->validate($config))
        ->toThrow(ValidationException::class);
});

test('config validator rejects unknown home section types', function () {
    $config = [
        'theme' => [
            'layout' => 'theme_a',
            'colors' => ['primary' => '#000000'],
        ],
        'home' => [
            ['type' => 'malicious_iframe_type'],
        ],
    ];

    expect(fn () => $this->validator->validate($config))
        ->toThrow(ValidationException::class);
});
