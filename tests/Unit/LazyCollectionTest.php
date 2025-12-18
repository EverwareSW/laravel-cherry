<?php

use Everware\LaravelCherry\Collections\LazyCollection;
use Everware\LaravelCherry\Tests\TestCase;
// use Illuminate\Support\LazyCollection;

pest()->extends(TestCase::class);

test('dontPreserveKeys', function () {
    $collection = new LazyCollection(function() {
        yield from [
            1,
            2,
        ];
        yield from [
            3,
            4,
        ];
    });

    $collection->dontPreserveKeys();

    $collection = $collection->map(fn($v)=> $v * 10);

    expect($collection->all())
        ->toBe([
            10, 20, 30, 40,
        ]);

    $collection->preserveKeys();

    expect($collection->all())
        ->toBe([
            30, 40,
        ]);
});