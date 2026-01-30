<?php

use Everware\LaravelCherry\Tests\TestCase;

pest()->extends(TestCase::class);

test('explodeTrim', function () {
    $result = \HStr::explodeTrim(',', 'apple, banana , cherry ');

    expect($result)->toBe(['apple', 'banana', 'cherry']);
});

test('explodeTrim with different delimiter', function () {
    $result = \HStr::explodeTrim('|', 'one | two | three');

    expect($result)->toBe(['one', 'two', 'three']);
});

test('explodeTrim with single item', function () {
    $result = \HStr::explodeTrim(',', 'single');

    expect($result)->toBe(['single']);
});

test('explodeTrimFilter removes empty strings', function () {
    $result = \HStr::explodeTrimFilter(',', 'apple, , banana,  , cherry');

    expect($result)->toBe([0 => 'apple', 2 => 'banana', 4 => 'cherry']);
});

test('explodeTrimFilter removes whitespace only', function () {
    $result = \HStr::explodeTrimFilter(',', 'one,   ,two');

    expect($result)->toBe([0 => 'one', 2 => 'two']);
});

test('cleanIntoKey simple', function () {
    $result = \HStr::cleanIntoKey('User Profile');

    expect($result)->toBe('userprofile');
});

test('cleanIntoKey with special characters', function () {
    $result = \HStr::cleanIntoKey('User-Profile_123!@#');

    expect($result)->toBe('userprofile123');
});

test('cleanIntoKey with numbers and letters', function () {
    $result = \HStr::cleanIntoKey('Form Field #1 (Required)');

    expect($result)->toBe('formfield1required');
});

test('base64UrlEncode string', function () {
    $result = \HStr::base64UrlEncode('Hello World');

    expect($result)->toBe('SGVsbG8gV29ybGQ');
    expect(str_contains($result, '+'))->toBeFalse();
    expect(str_contains($result, '/'))->toBeFalse();
});

test('base64UrlEncode array', function () {
    $data = ['name' => 'John', 'age' => 30];
    $result = \HStr::base64UrlEncode($data);

    expect($result)->not->toBeEmpty();
    expect(str_contains($result, '+'))->toBeFalse();
    expect(str_contains($result, '/'))->toBeFalse();
});

test('base64UrlDecode', function () {
    $encoded = \HStr::base64UrlEncode('Hello World');
    $result = \HStr::base64UrlDecode($encoded);

    expect($result)->toBe('Hello World');
});

test('base64UrlDecode with encoded array', function () {
    $data = ['name' => 'John', 'age' => 30];
    $encoded = \HStr::base64UrlEncode($data);
    $result = \HStr::base64UrlDecode($encoded);

    expect(json_decode($result, true))->toBe($data);
});

test('hmac with string', function () {
    $result = \HStr::hmac('secret', 'data');

    expect($result)->not->toBeEmpty();
    expect(strlen($result))->toBe(64); // sha256 produces 64 hex characters
});

test('hmac with array', function () {
    $data = ['key' => 'value'];
    $result = \HStr::hmac('secret', $data);

    expect($result)->not->toBeEmpty();
});

test('hmac with different algorithms', function () {
    $string = 'test';
    $key = 'key';

    $sha256 = \HStr::hmac($key, $string, 'sha256');
    $sha1 = \HStr::hmac($key, $string, 'sha1');

    expect(strlen($sha256))->toBe(64);
    expect(strlen($sha1))->toBe(40);
    expect($sha256)->not->toBe($sha1);
});

test('cleanPhone', function () {
    $result = \HStr::cleanPhone('(555) 123-4567');

    expect($result)->toBe('5551234567');
});

test('cleanPhone with special characters', function () {
    $result = \HStr::cleanPhone('+1 (555) 123-4567 ext.89');

    // cleanPhone only keeps digits, +, #, and *, so 'ext.' is removed
    expect($result)->toBe('+1555123456789');
});

test('cleanPhone with plus and hash', function () {
    $result = \HStr::cleanPhone('+31 (0)6 12345678');

    // The function removes all characters except digits, +, #, and *
    expect($result)->toBe('+310612345678');
});

test('after basic', function () {
    $result = \HStr::after('Hello World', 'World');

    expect($result)->toBe('');
});

test('after with text after search', function () {
    $result = \HStr::after('Hello World Example', 'World');

    expect($result)->toBe(' Example');
});

test('after not found returns empty', function () {
    $result = \HStr::after('Hello World', 'Goodbye');

    expect($result)->toBe('');
});

test('after with includeSearch', function () {
    $result = \HStr::after('Hello World', 'World', includeSearch: true);

    expect($result)->toBe('World');
});

test('after with afterLast', function () {
    $result = \HStr::after('Hello World World', 'World', afterLast: true);

    expect($result)->toBe('');
});

test('after with afterLast and includeSearch', function () {
    $result = \HStr::after('Hello World World', 'World', afterLast: true, includeSearch: true);

    expect($result)->toBe('World');
});

test('contains with wildcard', function () {
    expect(\HStr::contains('Hello World', '*World'))->toBeTrue();
    expect(\HStr::contains('Hello World', 'Hello*'))->toBeTrue();
    expect(\HStr::contains('Hello World', '*o W*'))->toBeTrue();
});

test('contains without match', function () {
    expect(\HStr::contains('Hello World', '*Goodbye*'))->toBeFalse();
});

test('contains with multiple patterns', function () {
    expect(\HStr::contains('Hello World', ['Goodbye', '*World*']))->toBeTrue();
    expect(\HStr::contains('Hello World', ['Goodbye', 'Foo']))->toBeFalse();
});

test('contains with line anchors', function () {
    $multiline = "Hello\nWorld\nPHP";
    expect(\HStr::contains($multiline, '^World$'))->toBeTrue();
    expect(\HStr::contains($multiline, '^Hello$'))->toBeTrue();
    expect(\HStr::contains($multiline, '^Foo$'))->toBeFalse();
});

test('anyContains single haystack', function () {
    expect(\HStr::anyContains('Hello World', 'World'))->toBeTrue();
    expect(\HStr::anyContains('Hello World', 'Goodbye'))->toBeFalse();
});

test('anyContains multiple haystacks', function () {
    $haystacks = ['Hello', 'World', 'PHP'];
    // anyContains uses Str::contains which doesn't handle wildcards
    expect(\HStr::anyContains($haystacks, 'PHP'))->toBeTrue();
    expect(\HStr::anyContains($haystacks, 'Foo'))->toBeFalse();
});

test('anyContains multiple needles', function () {
    $haystacks = ['Hello World', 'PHP Rules'];
    expect(\HStr::anyContains($haystacks, ['Python', 'PHP']))->toBeTrue();
    expect(\HStr::anyContains($haystacks, ['Java', 'Go']))->toBeFalse();
});