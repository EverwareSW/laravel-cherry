<?php

use Everware\LaravelCherry\Tests\TestCase;

pest()->extends(TestCase::class);

test('toCurrency with default symbol', function () {
    $result = \HNum::toCurrency(1234.56);

    expect($result)->toBe('€ 1.234,56');
});

test('toCurrency with custom symbol', function () {
    $result = \HNum::toCurrency(1234.56, '$');

    expect($result)->toBe('$1.234,56');
});

test('toCurrency with whole number', function () {
    $result = \HNum::toCurrency(1000);

    expect($result)->toBe('€ 1.000,00');
});

test('toCurrency with zero', function () {
    $result = \HNum::toCurrency(0);

    expect($result)->toBe('€ 0,00');
});

test('compareFloats equal', function () {
    expect(\HNum::compareFloats(1.0, 1.0))->toBeTrue();
    expect(\HNum::compareFloats(1.0000001, 1.0000002))->toBeTrue();
});

test('compareFloats not equal', function () {
    expect(\HNum::compareFloats(1.0, 1.1))->toBeFalse();
    expect(\HNum::compareFloats(1.0, 2.0))->toBeFalse();
});

test('compareFloats with custom epsilon', function () {
    expect(\HNum::compareFloats(1.0, 1.01, 0.1))->toBeTrue();
    expect(\HNum::compareFloats(1.0, 1.01, 0.001))->toBeFalse();
});

test('compareFloats with integers', function () {
    expect(\HNum::compareFloats(5, 5))->toBeTrue();
    expect(\HNum::compareFloats(5, 6))->toBeFalse();
});

test('compareFloats with mixed types', function () {
    expect(\HNum::compareFloats(5.0, 5))->toBeTrue();
    expect(\HNum::compareFloats(5.00001, 5, 1E-5))->toBeTrue();
});