<?php

use App\Services\Security\CredentialVault;

beforeEach(function () {
    $this->vault = new CredentialVault;
});

test('credential vault can encrypt and decrypt sensitive store credentials', function () {
    $credentials = [
        'consumer_key' => 'ck_9876543210abcdef9876543210abcdef',
        'consumer_secret' => 'cs_1234567890abcdef1234567890abcdef',
    ];

    $ciphertext = $this->vault->encrypt($credentials);

    expect($ciphertext)->not->toBeEmpty()
        ->and($ciphertext)->not->toContain('ck_9876543210')
        ->and($ciphertext)->not->toContain('cs_1234567890');

    $decrypted = $this->vault->decrypt($ciphertext);

    expect($decrypted)->toBe($credentials);
});

test('credential vault masks consumer key and consumer secret properly', function () {
    $key = 'ck_1234567890abcdef';
    $maskedKey = $this->vault->maskConsumerKey($key);

    expect($maskedKey)->toBe('ck_****cdef')
        ->and($maskedKey)->not->toContain('1234567890');

    $maskedSecret = $this->vault->maskConsumerSecret();
    expect($maskedSecret)->toBe('cs_****masked');
});

test('credential vault masked view never leaks raw consumer key or consumer secret', function () {
    $credentials = [
        'consumer_key' => 'ck_testkey12345678',
        'consumer_secret' => 'cs_verysecretstring9876',
    ];

    $view = $this->vault->getMaskedView($credentials);

    expect($view['consumer_key_masked'])->toBe('ck_****5678')
        ->and($view['consumer_secret_masked'])->toBe('cs_****masked')
        ->and($view)->not->toHaveKey('consumer_key')
        ->and($view)->not->toHaveKey('consumer_secret');
});
