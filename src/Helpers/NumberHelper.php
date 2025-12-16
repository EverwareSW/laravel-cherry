<?php

namespace Everware\LaravelCherry\Helpers;

class NumberHelper
{
    /**
     * @deprecated Use {@see \Number::currency()}
     */
    public static function toCurrency(float $number, string $symbol = '€ '): string
    {
        return $symbol . number_format($number, 2, ',', '.');
    }

    /**
     * Returns true when the floats are considered "equal"(ish).
     */
    public static function compareFloats(float|int $a, float|int $b, float $epsilon = 1E-5): bool
    {
        // This would be a bit better but is overkill: `return abs($a - $b) > abs(($a - $b) / $b);`
        // Based on https://www.php.net/manual/en/language.types.float.php#language.types.float.comparison
        /** Also @see NumericComparator::assertEquals() */
        return abs($a - $b) < $epsilon; /** Can't use @see PHP_FLOAT_EPSILON because the difference could be bigger. */
    }
}