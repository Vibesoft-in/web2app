<?php

use App\Rules\ValidDeepLink;

test('valid deep link formats strictly pass validation', function (string $link) {
    expect(ValidDeepLink::isValid($link))->toBeTrue();
})->with([
    'product:123',
    'product:45678',
    'product:shoes_sneakers_01',
    'order:1001',
    'order:ord_9988',
    'category:15',
    'category:electronics',
]);

test('malicious, external, or arbitrary URI schemes are rejected by deep link validator', function (string $link) {
    expect(ValidDeepLink::isValid($link))->toBeFalse();
})->with([
    'javascript:alert(1)',
    'javascript:void(0)',
    'http://phishing-site.com/steal-creds',
    'https://malicious.com',
    'file:///etc/passwd',
    'data:text/html,<script>alert(1)</script>',
    'product/123',
    'product::123',
    'custom_scheme:payload',
    'product:123;drop table users;',
    'product:',
    'order:',
    'category:',
]);
