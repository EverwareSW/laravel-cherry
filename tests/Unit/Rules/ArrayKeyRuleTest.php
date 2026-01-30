<?php

use Everware\LaravelCherry\Rules\ArrayKeyRule;
use Everware\LaravelCherry\Tests\TestCase;

pest()->extends(TestCase::class);

test('array key rule', function () {
    $validator = validator(
        [
            'days' => [
                '2026-01-28' => ['name' => 'Monday'],
            ],
        ],
        [
            'days'                       => ['required', 'array'],
            'days.*'                     => [new ArrayKeyRule('date_format:Y-m-d'), 'list'],
            'days.*.name'                => ['nullable', 'string', 'max:255'],
        ]
    );

    expect($validator->fails())->toBeFalse();
    expect($validator->getMessageBag()->getMessages())->toBe(['']);//TODO

    $validator = validator(
        [
            'days' => [
                'x2026-01-28' => ['name' => 'Monday'],
            ],
        ],
        [
            'days'                       => ['required', 'array'],
            'days.*'                     => [new ArrayKeyRule('date_format:Y-m-d'), 'list'],
            'days.*.name'                => ['nullable', 'string', 'max:255'],
        ]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->getMessageBag()->getMessages())->toBe(['']);//TODO
});
