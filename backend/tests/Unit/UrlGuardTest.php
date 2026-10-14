<?php

use App\Exceptions\UrlGuardException;
use App\Services\Security\UrlGuard;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->guard = new UrlGuard;
});

afterEach(function () {
    UrlGuard::setDnsResolver(null);
});

test('url guard blocks DNS rebinding style attacks resolving to private IPs', function () {
    UrlGuard::setDnsResolver(fn (string $host) => ['127.0.0.1']);

    expect(fn () => $this->guard->validateUrl('https://rebind.attacker-domain.com'))
        ->toThrow(UrlGuardException::class, 'internal IP range (127.0.0.1) is blocked');

    UrlGuard::setDnsResolver(fn (string $host) => ['169.254.169.254']);

    expect(fn () => $this->guard->validateUrl('https://rebind-metadata.attacker.com'))
        ->toThrow(UrlGuardException::class, 'internal IP range (169.254.169.254) is blocked');

    UrlGuard::setDnsResolver(fn (string $host) => ['10.0.0.1']);

    expect(fn () => $this->guard->validateUrl('https://rebind-private.attacker.com'))
        ->toThrow(UrlGuardException::class, 'internal IP range (10.0.0.1) is blocked');
});

test('url guard blocks plain unencrypted http schemes with NOT_HTTPS', function () {
    expect(fn () => $this->guard->validateUrl('http://example.com'))
        ->toThrow(UrlGuardException::class, 'Only secure HTTPS URLs are permitted.');
});

test('url guard blocks localhost by hostname', function () {
    expect(fn () => $this->guard->validateUrl('https://localhost'))
        ->toThrow(UrlGuardException::class, 'Access to localhost or local domains is blocked.');

    expect(fn () => $this->guard->validateUrl('https://sub.localhost'))
        ->toThrow(UrlGuardException::class, 'Access to localhost or local domains is blocked.');
});

test('url guard blocks loopback IP 127.0.0.1', function () {
    expect($this->guard->isIpBlocked('127.0.0.1'))->toBeTrue();
    expect($this->guard->isIpBlocked('127.0.0.254'))->toBeTrue();

    expect(fn () => $this->guard->validateUrl('https://127.0.0.1'))
        ->toThrow(UrlGuardException::class, 'internal IP range');
});

test('url guard blocks cloud metadata IP 169.254.169.254 and link-local range', function () {
    expect($this->guard->isIpBlocked('169.254.169.254'))->toBeTrue();
    expect($this->guard->isIpBlocked('169.254.1.1'))->toBeTrue();

    expect(fn () => $this->guard->validateUrl('https://169.254.169.254'))
        ->toThrow(UrlGuardException::class, 'internal IP range');
});

test('url guard blocks private 10.x.x.x range', function () {
    expect($this->guard->isIpBlocked('10.0.0.1'))->toBeTrue();
    expect($this->guard->isIpBlocked('10.255.255.254'))->toBeTrue();

    expect(fn () => $this->guard->validateUrl('https://10.0.0.5'))
        ->toThrow(UrlGuardException::class, 'internal IP range');
});

test('url guard blocks private 192.168.x.x range', function () {
    expect($this->guard->isIpBlocked('192.168.1.1'))->toBeTrue();
    expect($this->guard->isIpBlocked('192.168.100.50'))->toBeTrue();

    expect(fn () => $this->guard->validateUrl('https://192.168.0.1'))
        ->toThrow(UrlGuardException::class, 'internal IP range');
});

test('url guard blocks private 172.16.x.x range', function () {
    expect($this->guard->isIpBlocked('172.16.0.1'))->toBeTrue();
    expect($this->guard->isIpBlocked('172.31.255.254'))->toBeTrue();

    expect(fn () => $this->guard->validateUrl('https://172.16.1.1'))
        ->toThrow(UrlGuardException::class, 'internal IP range');
});

test('url guard blocks IPv6 loopback ::1', function () {
    expect($this->guard->isIpBlocked('::1'))->toBeTrue();
    expect($this->guard->isIpBlocked('fc00::1'))->toBeTrue();
    expect($this->guard->isIpBlocked('fe80::1'))->toBeTrue();
});

test('url guard blocks non-standard port numbers', function () {
    expect(fn () => $this->guard->validateUrl('https://example.com:8080'))
        ->toThrow(UrlGuardException::class, 'Non-standard ports are not allowed');

    expect(fn () => $this->guard->validateUrl('https://example.com:22'))
        ->toThrow(UrlGuardException::class, 'Non-standard ports are not allowed');
});

test('url guard blocks redirects targeting internal IPs', function () {
    Http::fake([
        'https://myshop.com/redirect' => Http::response(null, 302, [
            'Location' => 'https://169.254.169.254/latest/meta-data',
        ]),
        'https://myshop.com/*' => Http::response('OK', 200),
    ]);

    // Validate passes for the initial domain, but safeGet catches the redirect hop to internal IP
    expect(fn () => $this->guard->safeGet('https://myshop.com/redirect'))
        ->toThrow(UrlGuardException::class, 'internal IP range');
});
