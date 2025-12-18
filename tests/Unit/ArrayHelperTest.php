<?php

use Everware\LaravelCherry\Tests\TestCase;
use Illuminate\Validation\Rules\RequiredIf;

pest()->extends(TestCase::class);

test('filterFilled', function () {
    // filled() returns true for 0 and false, it only filters out null, '', and undefined
    expect(\HArr::filterFilled([1, null, '', 'text', 0, false]))
        ->toBe([0 => 1, 3 => 'text', 4 => 0, 5 => false]);

    expect(\HArr::filterFilled([]))
        ->toBe([]);

    expect(\HArr::filterFilled(['a' => 'value', 'b' => null, 'c' => '']))
        ->toBe(['a' => 'value']);
});

test('pushUnique strict', function () {
    $array = [1, 2, 3];
    \HArr::pushUnique($array, true, 2, 4, 5);
    expect($array)->toBe([1, 2, 3, 4, 5]);
});

test('pushUnique loose', function () {
    $array = [1, 2, 3];
    \HArr::pushUnique($array, false, '2', 4, 5);
    expect($array)->toBe([1, 2, 3, 4, 5]);
});

test('pushUnique associative', function () {
    $array = ['a' => 1, 'b' => 2];
    $assoc = ['c' => 3, 'd' => 4];
    // Spread the associative array to pass items individually
    \HArr::pushUnique($array, true, ...$assoc);
    expect($array)->toBe(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
});

test('pullMany', function () {
    $array = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
    $result = \HArr::pullMany($array, 'a', 'c');
    expect($result)->toBe(['a' => 1, 'c' => 3]);
    expect($array)->toBe(['b' => 2, 'd' => 4]);
});

test('pullRandom', function () {
    $array = ['a' => 1, 'b' => 2, 'c' => 3];
    $initialCount = count($array);
    $result = \HArr::pullRandom($array);

    expect(in_array($result, [1, 2, 3]))->toBeTrue();
    expect(count($array))->toBe($initialCount - 1);
    expect(in_array($result, array_values($array)))->toBeFalse();
});

test('onlyAndMap', function () {
    $array = ['name' => 'John', 'age' => '30', 'city' => 'NYC'];
    $result = \HArr::onlyAndMap($array, [
        'name' => fn($v) => strtoupper($v),
        'age' => fn($v) => (int)$v,
    ]);

    expect($result)->toBe(['name' => 'JOHN', 'age' => 30]);
});

test('onlyAndMap with null', function () {
    expect(\HArr::onlyAndMap(null, ['name' => fn($v) => $v]))
        ->toBeNull();
});

test('onlyAndMapWithKeys', function () {
    $array = ['user' => 'john', 'role' => 'admin'];
    $result = \HArr::onlyAndMapWithKeys($array, [
        'user' => fn($v) => ['username' => $v],
        'role' => fn($v) => ['permission' => $v],
    ]);

    expect($result)->toBe(['username' => 'john', 'permission' => 'admin']);
});

test('mapFilterJoin default', function () {
    $result = \HArr::mapFilterJoin(', ', ['  hello  ', '  world  ', null, '  php  ']);
    expect($result)->toBe('hello, world, php');
});

test('mapFilterJoin with custom filter', function () {
    $result = \HArr::mapFilterJoin(', ', ['a', 'bb', 'ccc'], filter: fn($v) => strlen($v) > 1);
    expect($result)->toBe('bb, ccc');
});

test('mapFilterJoin with custom map', function () {
    $result = \HArr::mapFilterJoin(', ', ['hello', 'world'], map: 'strtoupper');
    expect($result)->toBe('HELLO, WORLD');
});

test('urlKeys', function () {
    $array = ['user' => ['name' => 'John', 'email' => 'john@example.com'], 'active' => true];
    $result = \HArr::urlKeys($array);

    expect($result)->toBe([
        'user[name]' => 'John',
        'user[email]' => 'john@example.com',
        'active' => true,
    ]);
});

test('urlKeys with parent key', function () {
    $array = ['name' => 'John'];
    $result = \HArr::urlKeys($array, 'person');

    expect($result)->toBe(['person[name]' => 'John']);
});

test('renameKeys', function () {
    $array = ['old_name' => 'value1', 'other_old' => 'value2'];
    $result = \HArr::renameKeys($array, ['old_name' => 'new_name', 'other_old' => 'other_new']);

    expect($result)->toBe(['new_name' => 'value1', 'other_new' => 'value2']);
    expect($array)->toBe(['new_name' => 'value1', 'other_new' => 'value2']);
});

test('prefixKeys', function () {
    $array = ['name' => 'John', 'age' => 30];
    $result = \HArr::prefixKeys($array, 'user_');

    expect($result)->toBe(['user_name' => 'John', 'user_age' => 30]);
});

test('prefixKeys with suffix', function () {
    $array = ['name' => 'John', 'age' => 30];
    $result = \HArr::prefixKeys($array, 'user_', '_end');

    expect($result)->toBe(['user_name_end' => 'John', 'user_age_end' => 30]);
});

test('prefixKeys recursive', function () {
    $array = ['user' => ['name' => 'John'], 'status' => 'active'];
    $result = \HArr::prefixKeys($array, 'prefix_', '', true);

    expect($result)->toBe([
        'prefix_user' => ['prefix_name' => 'John'],
        'prefix_status' => 'active',
    ]);
});

test('shuffle non-associative', function () {
    $array = [1, 2, 3, 4, 5];
    $result = \HArr::shuffle($array, 12345);

    expect(sort($result))->toBe(sort($array));
    expect(count($result))->toBe(count($array));
});

test('shuffle associative', function () {
    $array = ['a' => 1, 'b' => 2, 'c' => 3];
    $result = \HArr::shuffle($array, 12345);

    expect(count($result))->toBe(count($array));
    foreach ($array as $key => $value) {
        expect($result[$key])->toBe($value);
    }
});

test('every true', function () {
    expect(\HArr::every([1, 2, 3], 'is_int'))->toBeTrue();
    expect(\HArr::every([true, 1, 'yes'], fn($v) => value($v)))->toBeTrue();
});

test('every false', function () {
    expect(\HArr::every([1, 'two', 3], 'is_int'))->toBeFalse();
    expect(\HArr::every([true, false, 1]))->toBeFalse();
});

test('some true', function () {
    expect(\HArr::some([1, 'two', 3], 'is_int'))->toBeTrue();
    expect(\HArr::some([false, false, 'text'], fn($v) => value($v)))->toBeTrue();
});

test('some false', function () {
    expect(\HArr::some(['one', 'two'], 'is_int'))->toBeFalse();
    expect(\HArr::some([false, 0, ''], fn($v) => value($v)))->toBeFalse();
});

test('instanceOf', function () {
    $arrayCollection = collect([1, 2, 3]);
    expect(\HArr::instanceOf($arrayCollection, 'Illuminate\Support\Collection'))->toBeTrue();
    expect(\HArr::instanceOf($arrayCollection, 'stdClass', 'Illuminate\Support\Collection'))->toBeTrue();
    expect(\HArr::instanceOf($arrayCollection, 'stdClass', 'Exception'))->toBeFalse();
});

test('mergeRecursively', function () {
    $array1 = ['name' => 'John', 'tags' => ['admin', 'user']];
    $array2 = ['age' => 30, 'tags' => ['moderator']];
    $result = \HArr::mergeRecursively($array1, $array2);

    expect($result['name'])->toBe('John');
    expect($result['age'])->toBe(30);
    // mergeRecursively merges arrays and deduplicates, keeping first array's values
    expect($result['tags'])->toContain('admin');
    expect($result['tags'])->toContain('user');
    expect($result['tags'])->not->toContain('moderator');
});

test('mergeRecursively multiple arrays', function () {
    // Test with numeric arrays (should take first array's values)
    $result = \HArr::mergeRecursively(
        ['a' => [1, 2]],
        ['a' => [2, 3]],
        ['a' => [3, 4]]
    );

    // Should take first array's values without merging
    expect($result['a'])->toBe([1, 2]);

    // Test with empty arrays
    $emptyResult = \HArr::mergeRecursively([], [], []);
    expect($emptyResult)->toBe([]);

    // Test with mixed empty and non-empty arrays
    // The method collects all unique keys from all arrays
    $mixedResult = \HArr::mergeRecursively(
        ['a' => 1],
        [],
        ['a' => 2, 'b' => 3],
        ['a' => 4, 'c' => 5]
    );

    // It should include all keys that appear in any array
    // For non-array values, it takes the first non-null value
    expect($mixedResult)->toHaveKeys(['a', 'b', 'c']);
    expect($mixedResult['a'])->toBe(1);  // From first array
    expect($mixedResult['b'])->toBe(3);  // Exists in array 3 but not in first array
    expect($mixedResult['c'])->toBe(5);  // Exists in array 4 but not in first array

    // Test with nested associative arrays
    $nestedResult = \HArr::mergeRecursively(
        ['user' => ['name' => 'John', 'roles' => ['admin']]],
        ['user' => ['age' => 30, 'roles' => ['user']]],
        ['user' => ['city' => 'NYC', 'roles' => ['editor']]]
    );

    expect($nestedResult['user']['name'])->toBe('John');
    expect($nestedResult['user']['age'])->toBe(30);
    expect($nestedResult['user']['city'])->toBe('NYC');
    // For numeric arrays, it keeps the first array's values
    expect($nestedResult['user']['roles'])->toBe(['admin']);

    // Test with mixed numeric and string keys
    // Only keys from the first array are included
    $mixedKeysResult = \HArr::mergeRecursively(
        ['a' => 1, 0 => 'zero'],
        ['b' => 2, 1 => 'one'],
        ['c' => 3, 0 => 'new zero']
    );

    // Only keys from the first array are included
    expect($mixedKeysResult)->toHaveKeys(['a', 0]);
    expect($mixedKeysResult['a'])->toBe(1);
    expect($mixedKeysResult[0])->toBe('zero'); // First array's value should be preserved

    // Test with null values
    $withNullResult = \HArr::mergeRecursively(
        ['a' => null, 'b' => 1],
        ['a' => 2, 'c' => null]
    );

    // The first array's values take precedence, even if they're null
    expect($withNullResult['a'])->toBeNull();  // First array's null is preserved
    expect($withNullResult['b'])->toBe(1);
    // All keys from all arrays are included, with null for missing values
    expect($withNullResult['c'])->toBeNull();

    // Test with non-array values
    $withNonArrayResult = \HArr::mergeRecursively(
        ['a' => 'string', 'b' => ['nested' => 1]],
        ['a' => ['nested' => 2], 'b' => 'string']
    );

    // The method wraps non-array values in an array and merges them
    // For key 'a': 'string' is wrapped into [0 => 'string'] and merged with ['nested' => 2]
    // For key 'b': 'string' is wrapped into [0 => 'string'] and merged with ['nested' => 1]

    // Check that the structure is as expected
    expect($withNonArrayResult['a'])->toHaveKey('nested');
    expect($withNonArrayResult['a'])->toContain('string');

    expect($withNonArrayResult['b'])->toHaveKey('nested');
    expect($withNonArrayResult['b'])->toContain('string');
});

test('overwriteWildcards simple', function () {
    $target = [];
    \HArr::overwriteWildcards($target, 'user.name', 'John');

    expect($target)->toBe(['user' => ['name' => 'John']]);
});

test('overwriteWildcards with wildcard', function () {
    $target = [];
    \HArr::overwriteWildcards($target, 'items.*.name', ['Alice', 'Bob']);

    expect($target)->toBe([
        'items' => [
            ['name' => 'Alice'],
            ['name' => 'Bob'],
        ],
    ]);
});

test('getVariations', function () {
    $result = \HArr::getVariations(['a', 'b']);
    expect(count($result))->toBe(2);
    expect($result)->toContain(['a', 'b']);
    expect($result)->toContain(['b', 'a']);
});

test('getMultiDimensionalVariations', function () {
    $array = [
        'letter' => ['a', 'b'],
        'number' => [1, 2],
    ];
    $result = \HArr::getMultiDimensionalVariations($array);

    expect(count($result))->toBe(4);
    expect($result)->toContain(['letter' => 'a', 'number' => 1]);
    expect($result)->toContain(['letter' => 'b', 'number' => 2]);
});

test('toHtmlTable', function () {
    $data = [
        ['Name', 'Email'],
        ['John', 'john@example.com'],
    ];
    $result = \HArr::toHtmlTable($data, 'class="table"', '', '', '', '', true);

    expect((string)$result)->toContain('<table class="table">');
    expect((string)$result)->toContain('<th');
    expect((string)$result)->toContain('<td');
    expect((string)$result)->toContain('John');
});

test('iterable with array', function () {
    $result = \HArr::iterable(['a', 'b', 'c']);
    expect($result)->toBe(['a', 'b', 'c']);
});

test('iterable with null', function () {
    $result = \HArr::iterable(null);
    expect($result)->toBe([]);
});

test('iterable with single value', function () {
    $result = \HArr::iterable('single');
    expect($result)->toBe(['single']);
});

test('onlyTypeValidation', function () {
    $rules = [
        'email' => 'required|email|string',
        'age' => ['required', 'integer', 'min:18'],
    ];
    $result = \HArr::onlyTypeValidation($rules);

    expect($result['email'])->toContain('email');
    expect($result['email'])->toContain('string');
    expect($result['age'])->toContain('integer');
    expect($result['age'])->toContain('min:18');
    expect($result['email'])->not->toContain('required');
});