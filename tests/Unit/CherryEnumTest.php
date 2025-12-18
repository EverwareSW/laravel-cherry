<?php

use Everware\LaravelCherry\Tests\TestCase;
use Workbench\App\JustSomeEnum;

pest()->extends(TestCase::class);

test('all methods', function () {
    expect(JustSomeEnum::names())->toBe(['Red', 'Green', 'Blue']);

    expect(JustSomeEnum::values())->toBe(['red', 'green', 'blue']);

    expect(JustSomeEnum::array())->toBe([
        'red' => 'Red',
        'green' => 'Green',
        'blue' => 'Blue',
    ]);

    $enums = JustSomeEnum::fromValues('red', 'green');
    expect($enums)->toHaveCount(2)
        ->and($enums[0])->toBe(JustSomeEnum::Red)
        ->and($enums[1])->toBe(JustSomeEnum::Green);

    expect(fn() => JustSomeEnum::fromValues('invalid'))->toThrow(ValueError::class);

    $result = JustSomeEnum::tryFromValues('red', 'invalid', 'blue');
    expect($result)->toHaveCount(3)
        ->and($result[0])->toBe(JustSomeEnum::Red)
        ->and($result[1])->toBeNull()
        ->and($result[2])->toBe(JustSomeEnum::Blue);

    expect(JustSomeEnum::fromName('Green'))->toBe(JustSomeEnum::Green);

    expect(fn() => JustSomeEnum::fromName('NonExistent'))->toThrow(ValueError::class);

    expect(JustSomeEnum::tryFromName('Red'))->toBe(JustSomeEnum::Red)
        ->and(JustSomeEnum::tryFromName('NonExistent'))->toBeNull();

    $enums = JustSomeEnum::fromNames('Red', 'Blue');
    expect($enums)->toHaveCount(2)
        ->and($enums[0])->toBe(JustSomeEnum::Red)
        ->and($enums[1])->toBe(JustSomeEnum::Blue);

    expect(fn() => JustSomeEnum::fromNames('Red', 'NonExistent'))->toThrow(ValueError::class);

    $result = JustSomeEnum::tryFromNames('Red', 'NonExistent', 'Blue');
    expect($result)->toHaveCount(3)
        ->and($result[0])->toBe(JustSomeEnum::Red)
        ->and($result[1])->toBeNull()
        ->and($result[2])->toBe(JustSomeEnum::Blue);
});