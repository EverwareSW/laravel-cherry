<?php

use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;

if (!function_exists('fail')) {
    /**
     * Like @see abort(), but with the params rearranged for shorter code.
     */
    function fail(string $message = '', int|Response|Responsable $code = 500, array $headers = []): void
    {
        abort($code, $message, $headers);
    }
}

if (!function_exists('if_isset')) {
    /**
     * Shorthand function for `isset($a[$k]) ? $c($a[$k]) : null`
     */
    function if_isset(array|\ArrayAccess $array, string $key, ?callable $if = null, mixed $else = null): mixed
    {
        if (isset($array[$key])) {
            return $if ? $if($array[$key]) : $array[$key];
        } else {
            return value($else);
        }
    }
}

if (!function_exists('if_exists')) {
    /**
     * Shorthand function for `array_key_exists($k, $a) ? $c($a[$k]) : null`
     */
    function if_exists(array|\ArrayAccess $array, string $key, ?callable $if = null, mixed $else = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $if ? $if($array[$key]) : $array[$key];
        } else {
            return value($else);
        }
    }
}

if (!function_exists('if_filled')) {
    /**
     * Shorthand function for `filled($a[$k]) ? $c($a[$k]) : null`
     */
    function if_filled(array|\ArrayAccess $array, string $key, ?callable $if = null, mixed $else = null): mixed
    {
        if (isset($array[$key]) && filled($array[$key])) {
            return $if ? $if($array[$key]) : $array[$key];
        } else {
            return value($else);
        }
    }
}

if (!interface_exists('Arraylike')) {
    /**
     * Something that can be traversed e.g. using `foreach` (either implements {@see \Iterator} or {@see \IteratorAggregate})
     * and that can be offset accessed e.g. `$obj['key']`.
     * Usually used in conjunction with `array` e.g. `array|\Arraylike`.
     */
    interface Arraylike extends \Traversable, \ArrayAccess { }
}
if (!interface_exists('CountableArraylike')) {
    /**
     * Arraylike (see above) and can be counted e.g. `count($obj)`.
     * Usually used in conjunction with `array` e.g. `array|\CountableArraylike`.
     */
    interface CountableArraylike extends \Arraylike, \Countable { }
}