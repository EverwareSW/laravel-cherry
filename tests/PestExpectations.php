<?php

use Illuminate\Testing\Assert;
use Illuminate\Testing\TestResponse;
use Pest\TestSuite;
use PHPUnit\Framework\ExpectationFailedException;

if (function_exists('pest') && function_exists('expect')) {
    /**
     * Based on @see Expectation::toMatchSnapshot()
     */
    function getLastSnapshot() {
        $snapshots = TestSuite::getInstance()->snapshots;
        if (!$snapshots->has()) {
            throw new \Exception('No snapshot found');
        }
        [$filename, $content] = $snapshots->get();
        return $content;
    }

    /**
     * Based on @see Expectation::toMatchSnapshot()
     */
    expect()->extend('responseToString', function() {
        $this->value = match(true) {
            $this->value instanceof TestResponse => $this->value->getContent(),
            /** Also see toMatchSnapshot() below. Based on @see JsonResponse::setData() */
            is_array($this->value) => json_encode($this->value, JSON_PRETTY_PRINT),
            is_string($this->value) => $this->value,
            default => throw new \Exception('Unexpected value type in responseToString(): ' . gettype($this->value)),
        };
    });

    /**
     * Based on {@see Expectation::json()} and {@see Expectation::toMatchSnapshot()}
     */
    expect()->extend('responseToJson', function() {
        if (is_array($this->value)) {
            return;
        }

        $this->responseToString();
        $this->value = match(true) {
            is_string($this->value) => json_decode($this->value, true, 512, JSON_THROW_ON_ERROR),
        };
    });

    expect()->pipe('toMatchSnapshot', function(\Closure $next) {
        if (is_array($this->value)) {
            /** Also see responseToString() above. Based on @see JsonResponse::setData() */
            $this->value = json_encode($this->value, JSON_PRETTY_PRINT);
        }
        return $next();
    });

    /**
     * Replaces keys with values in TEXT response using `preg_replace()`.
     * @param iterable $replacements E.g. `[['/[a-z]/'], ['search', 'replace'], ['s', 'r', 2]]`
     */
    expect()->extend('dynamicSnapshot', function(iterable $replacements) {
        $this->responseToString();

        $i = 0;
        foreach ($replacements as $replacement) {
            [$search, $replace, $limit] = $replacement + [1 => "snapdyn|preg|$replacement[0]|$i", 2 => -1];
            $this->value = preg_replace($search, $replace, $this->value, $limit, $foundAmount);
            expect($foundAmount)->toBeGreaterThan(0, "Failed asserting that snapshot contains regex `$search` \n$this->value");
            $i++;
        }
    });

    /**
     * Removes given keys from JSON response entirely. Wildcard '*' possible.
     * @param iterable $keys E.g. `[data.*.id']`.
     */
    expect()->extend('ignoreJsonSnapshot', function(string|iterable $keys) {
        $this->responseToJson();
        $keys = \HArr::iterable($keys);
        $notFound = (object) ['found' => false];
        foreach($keys as $key) {
            $data = data_get($this->value, $key, $notFound);
            expect($data)->not()->toBe($notFound, "Key `$key` not found in " . json_encode($this->value));
            data_forget($this->value, $key);
        }
    });

    /**
     * Replaces keys with values in JSON response. Wildcard '*' possible.
     * @param iterable $keys E.g. `[data.*.id']`.
     */
    expect()->extend('dynamicJsonSnapshot', function(iterable $keys, ?int $count = null) {
        $this->responseToJson();

        // $notFound = (object) ['found' => false];
        foreach ($keys as $key) {
            // $values = data_get($this->value, $key, $notFound);
            // throw_if($values === $notFound, ExpectationFailedException::class, "Key `$key` not found in " . json_encode($this->value));

            $i = 0;
            $this->value = \HArr::overwriteWildcards($this->value, $key, function($value, $k) use($key, &$i) {
                $type = gettype($value);
                $replace = "snapdyn|$type|$key|$i";
                $i++;
                return $replace;
            }, true);

            if ($count !== null) {
                expect($i)->toEqual($count);
            }
        }
    });

    /**
     * Replaces keys with values in JSON response. Wildcard '*' possible.
     * @param iterable $replacements E.g. `[data.*.id => 'replacement']`
     */
    expect()->extend('replaceJsonSnapshot', function(iterable $replacements) {
        $this->responseToJson();
        foreach($replacements as $key => $value) {
            if (is_callable($value)) {
                $value = $value(data_get($this->value, $key));
            }

            $this->value = \HArr::overwriteWildcards($this->value, $key, $value);
        }
    });

    /**
     * Based on @see Expectation::toMatchSnapshot()
     */
    expect()->extend('matchesLastSnapshot', function() {
        /** Based on {@see Expectation::toMatchArray()} and {@see Expectation::toContain()}. */
        $compare = function($snapData, $compareData) use(&$compare) {
            foreach ($snapData as $key => $value) {
                Assert::assertArrayHasKey($key, $compareData,
                    "Failed asserting that the snapshot is contained within \$data. Missing key '$key' in array " . json_encode($compareData) . ".");
                if (is_array($value) || is_object($value)) {
                    $compare($value, $compareData[$key]);
                } elseif (str_starts_with($value, 'snapdyn|')) {
                    $type = explode('|', substr($value, 8), 2)[0];
                    expect(gettype($compareData[$key]))->toBe($type,
                        "Failed asserting that the snapshot is contained within \$data. Value under key '$key' is not type $type in array " . json_encode($compareData) . ".");
                } else {
                    expect($value)->toBe($compareData[$key],
                        "Failed asserting that the snapshot is contained within \$data. Value under key '$key' is not " . json_encode($value) . " in array " . json_encode($compareData) . ".");
                }
            }
        };

        $snapshot = getLastSnapshot();
        $compare(json_decode($snapshot, true), $this->value);
    });
}