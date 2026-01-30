<?php

use Everware\LaravelCherry\Tests\TestCase;
use Illuminate\Http\RedirectResponse;

pest()->extends(TestCase::class);

test('createHardRedirectResponse creates redirect response', function () {
    $response = \HInertia::createHardRedirectResponse('/dashboard');

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getStatusCode())->toBe(409);
});

test('createHardRedirectResponse sets X-Inertia-Location header', function () {
    $response = \HInertia::createHardRedirectResponse('/dashboard');

    expect($response->headers->get('X-Inertia-Location'))->toBe('/dashboard');
});

test('createHardRedirectResponse with custom headers', function () {
    $response = \HInertia::createHardRedirectResponse(
        '/dashboard',
        ['X-Custom-Header' => 'custom-value']
    );

    expect($response->headers->get('X-Custom-Header'))->toBe('custom-value');
    expect($response->headers->get('X-Inertia-Location'))->toBe('/dashboard');
});

test('createHardRedirectResponse returns 409 status', function () {
    $response = \HInertia::createHardRedirectResponse('/test-path');

    expect($response->status())->toBe(409);
});

test('createHardRedirectResponse with secure parameter', function () {
    $response = \HInertia::createHardRedirectResponse('/secure-path', secure: true);

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getStatusCode())->toBe(409);
});