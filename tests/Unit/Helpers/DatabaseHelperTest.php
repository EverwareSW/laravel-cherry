<?php

use Everware\LaravelCherry\Tests\TestCase;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

pest()->extends(TestCase::class);

test('retryDuplicate successful on first attempt', function () {
    $attempts = 0;
    $result = \HDb::retryDuplicate(function () use (&$attempts) {
        $attempts++;
        return 'success';
    });

    expect($result)->toBe('success');
    expect($attempts)->toBe(1);
});

test('retryDuplicate retries on UniqueConstraintViolationException', function () {
    $attempts = 0;
    $result = \HDb::retryDuplicate(function () use (&$attempts) {
        $attempts++;
        if ($attempts < 2) {
            // Create a QueryException with code 23000 (integrity constraint violation)
            $e = new QueryException('mysql', 'INSERT', [], new Exception());
            // Use reflection to set the code
            $reflection = new ReflectionProperty(QueryException::class, 'code');
            $reflection->setValue($e, '23000');
            throw $e;
        }
        return 'success after retry';
    }, times: 3, sleep: 0);

    expect($result)->toBe('success after retry');
    expect($attempts)->toBe(2);
});

test('retryDuplicate gives up after max retries', function () {
    $attempts = 0;
    expect(function () use (&$attempts) {
        \HDb::retryDuplicate(function () use (&$attempts) {
            $attempts++;
            $e = new QueryException('mysql', 'INSERT', [], new Exception());
            $reflection = new ReflectionProperty(QueryException::class, 'code');
            $reflection->setValue($e, '23000');
            throw $e;
        }, times: 2, sleep: 0);
    })->toThrow(QueryException::class);

    expect($attempts)->toBe(2);
});

test('retryDeadlock successful on first attempt', function () {
    $attempts = 0;
    $result = \HDb::retryDeadlock(function () use (&$attempts) {
        $attempts++;
        return 'success';
    });

    expect($result)->toBe('success');
    expect($attempts)->toBe(1);
});

test('retryDeadlock retries on deadlock', function () {
    $attempts = 0;
    $result = \HDb::retryDeadlock(function () use (&$attempts) {
        $attempts++;
        if ($attempts < 2) {
            $e = new QueryException('mysql', 'SELECT', [], new Exception('Deadlock'));
            $reflection = new ReflectionProperty(QueryException::class, 'code');
            $reflection->setValue($e, '40001');
            throw $e;
        }
        return 'success after retry';
    }, times: 3, sleep: 0);

    expect($result)->toBe('success after retry');
    expect($attempts)->toBe(2);
});

test('retryDeadlock gives up after max retries', function () {
    $attempts = 0;
    expect(function () use (&$attempts) {
        \HDb::retryDeadlock(function () use (&$attempts) {
            $attempts++;
            $e = new QueryException('mysql', 'SELECT', [], new Exception());
            $reflection = new ReflectionProperty(QueryException::class, 'code');
            $reflection->setValue($e, '40001');
            throw $e;
        }, times: 2, sleep: 0);
    })->toThrow(QueryException::class);

    expect($attempts)->toBe(2);
});

test('retryDuplicate does not retry on other exceptions', function () {
    $attempts = 0;
    expect(function () use (&$attempts) {
        \HDb::retryDuplicate(function () use (&$attempts) {
            $attempts++;
            throw new Exception('Other error');
        }, times: 3, sleep: 0);
    })->toThrow(Exception::class);

    expect($attempts)->toBe(1);
});

test('retryDeadlock does not retry on other exceptions', function () {
    $attempts = 0;
    expect(function () use (&$attempts) {
        \HDb::retryDeadlock(function () use (&$attempts) {
            $attempts++;
            throw new Exception('Other error');
        }, times: 3, sleep: 0);
    })->toThrow(Exception::class);

    expect($attempts)->toBe(1);
});