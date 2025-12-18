<?php

use Everware\LaravelCherry\Tests\TestCase;
use Everware\LaravelCherry\Helpers\UrlHelper;

pest()->extends(TestCase::class);

test('from creates instance', function () {
    $helper = \HUrl::from('https://example.com/path?query=value#fragment');

    expect($helper)->toBeInstanceOf(UrlHelper::class);
});

test('from with non-strict invalid url', function () {
    $helper = \HUrl::from('not a valid url', strict: false);

    expect($helper)->toBeInstanceOf(UrlHelper::class);
});

test('parse url components', function () {
    $helper = \HUrl::from('https://user:pass@example.com:8080/path?query=value#fragment');

    expect($helper->scheme)->toBe('https');
    expect($helper->host)->toBe('example.com');
    expect($helper->port)->toBe(8080);
    expect($helper->user)->toBe('user');
    expect($helper->pass)->toBe('pass');
    expect($helper->path)->toBe('/path');
    expect($helper->query)->toBe('query=value');
    expect($helper->fragment)->toBe('fragment');
});

test('scheme setter', function () {
    $helper = \HUrl::from('http://example.com');
    $result = $helper->scheme('https');

    expect($result)->toBe($helper);
    expect($helper->scheme)->toBe('https');
});

test('host setter', function () {
    $helper = \HUrl::from('https://old.example.com');
    $helper->host('new.example.com');

    expect($helper->host)->toBe('new.example.com');
});

test('port setter', function () {
    $helper = \HUrl::from('https://example.com');
    $helper->port(8443);

    expect($helper->port)->toBe(8443);
});

test('path setter', function () {
    $helper = \HUrl::from('https://example.com/old');
    $helper->path('/new/path');

    expect($helper->path)->toBe('/new/path');
});

test('query setter', function () {
    $helper = \HUrl::from('https://example.com?old=value');
    $helper->query('new=param');

    expect($helper->query)->toBe('new=param');
});

test('subdomain setter simple', function () {
    $helper = \HUrl::from('https://example.com');
    $helper->subdomain('api');

    expect($helper->host)->toBe('api.example.com');
});

test('subdomain setter replace existing', function () {
    $helper = \HUrl::from('https://www.example.com');
    $helper->subdomain('api');

    expect($helper->host)->toBe('api.example.com');
});

test('subdomain setter remove', function () {
    $helper = \HUrl::from('https://api.example.com');
    $helper->subdomain(null);

    expect($helper->host)->toBe('example.com');
});

test('subdomain with localhost', function () {
    $helper = \HUrl::from('https://localhost');
    $helper->subdomain('api');

    expect($helper->host)->toBe('api.localhost');
});

test('explicitPort http', function () {
    $helper = \HUrl::from('http://example.com');
    $helper->explicitPort();

    expect($helper->port)->toBe(80);
});

test('explicitPort https', function () {
    $helper = \HUrl::from('https://example.com');
    $helper->explicitPort();

    expect($helper->port)->toBe(443);
});

test('setQuery with array', function () {
    $helper = \HUrl::from('https://example.com');
    $helper->setQuery(['name' => 'John', 'age' => 30]);

    parse_str($helper->query, $params);
    // parse_str converts all values to strings
    expect($params)->toBe(['name' => 'John', 'age' => '30']);
});

test('addQuery merges with existing', function () {
    $helper = \HUrl::from('https://example.com?existing=value');
    $helper->addQuery(['new' => 'param']);

    parse_str($helper->query, $params);
    expect($params)->toHaveKey('existing');
    expect($params)->toHaveKey('new');
});

test('getHostPort with port', function () {
    $helper = \HUrl::from('https://example.com:8080');

    expect($helper->getHostPort())->toBe('example.com:8080');
});

test('getHostPort without port', function () {
    $helper = \HUrl::from('https://example.com');

    expect($helper->getHostPort())->toBe('example.com');
});

test('toString full url', function () {
    $url = 'https://user:pass@example.com:8080/path?query=value#fragment';
    $helper = \HUrl::from($url);

    expect((string)$helper)->toBe($url);
});

test('toString without optional parts', function () {
    $helper = \HUrl::from('https://example.com/path');

    expect((string)$helper)->toContain('https://example.com');
    expect((string)$helper)->toContain('/path');
    expect((string)$helper)->not->toContain('@');
    // Note: URL will contain ':' in the scheme (https:), so we check for user:pass@ instead
    expect((string)$helper)->not->toContain('user');
});

test('method chaining', function () {
    $helper = \HUrl::from('http://example.com')
        ->scheme('https')
        ->port(8443)
        ->path('/api/v1');

    expect($helper->scheme)->toBe('https');
    expect($helper->port)->toBe(8443);
    expect($helper->path)->toBe('/api/v1');
});

test('toString with query', function () {
    $helper = \HUrl::from('https://example.com');
    $helper->setQuery(['param' => 'value']);

    expect((string)$helper)->toContain('?param=value');
});

test('toString with fragment', function () {
    $helper = \HUrl::from('https://example.com');
    $helper->fragment('section');

    expect((string)$helper)->toContain('#section');
});