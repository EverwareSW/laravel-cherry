<?php

use Everware\LaravelCherry\Tests\TestCase;

pest()->extends(TestCase::class);

test('castBoolean with null', function () {
    expect(\HCast::castBoolean(null))
        ->toBeNull();
});

test('castBoolean true cases', function () {
    expect(\HCast::castBoolean(true))->toBeTrue();
    expect(\HCast::castBoolean(1))->toBeTrue();
    expect(\HCast::castBoolean('1'))->toBeTrue();
});

test('castBoolean false cases', function () {
    expect(\HCast::castBoolean(false))->toBeFalse();
    expect(\HCast::castBoolean(0))->toBeFalse();
    expect(\HCast::castBoolean('0'))->toBeFalse();
    expect(\HCast::castBoolean(''))->toBeFalse();
    expect(\HCast::castBoolean('false'))->toBeFalse();
    expect(\HCast::castBoolean([]))->toBeFalse();
});

test('castFloat with null', function () {
    expect(\HCast::castFloat(null))
        ->toBeNull();
});

test('castFloat normal values', function () {
    expect(\HCast::castFloat(3.14))
        ->toBe(3.14);

    expect(\HCast::castFloat(10))
        ->toBe(10.0);

    expect(\HCast::castFloat('25.5'))
        ->toBe(25.5);
});

test('castFloat special values', function () {
    expect(is_nan(\HCast::castFloat('NaN')))
        ->toBeTrue();

    expect(\HCast::castFloat('Infinity'))
        ->toBe(INF);

    expect(\HCast::castFloat('-Infinity'))
        ->toBe(-INF);
});