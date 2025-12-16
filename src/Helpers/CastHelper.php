<?php

namespace Everware\LaravelCherry\Helpers;

class CastHelper
{
    /**
     * Based on @see Model::castAttribute()
     * Based on @see ValidatesAttributes::validateBoolean()
     *
     * @param mixed $value
     * @return bool|null
     */
    public static function castBoolean(mixed $value): ?bool
    {
        return match ($value) {
            null => null,
            true, 1, '1' => true,
            default => false,
        };
    }

    /**
     * Based on @see Model::fromFloat()
     * Based on @see ValidatesAttributes::validateNumeric()
     *
     * @param mixed $value
     * @return float|null
     */
    public static function castFloat(mixed $value): ?float
    {
         return match ($value) {
             null => null,
            'NaN' => NAN,
            'Infinity' => INF,
            '-Infinity' => -INF,
            default => (float) $value,
        };
    }
}