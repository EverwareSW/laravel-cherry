<?php

use Everware\LaravelCherry\Tests\TestCase;
use Everware\LaravelCherry\Helpers\ExceptionHelper;
use Everware\LaravelCherry\Helpers\TimeoutException;

pest()->extends(TestCase::class);

test('ignore catches exception and returns default', function () {
    $result = \HExc::ignore(function () {
        throw new Exception('Something went wrong');
    }, 'default value');

    expect($result)->toBe('default value');
});

test('ignore catches exception and returns null by default', function () {
    $result = \HExc::ignore(function () {
        throw new Exception('Something went wrong');
    });

    expect($result)->toBeNull();
});

test('ignore returns callback result when no exception', function () {
    $result = \HExc::ignore(function () {
        return 'success';
    });

    expect($result)->toBe('success');
});

test('ignore catches any type of throwable', function () {
    $result = \HExc::ignore(function () {
        throw new TypeError('Type error');
    }, 'caught');

    expect($result)->toBe('caught');
});