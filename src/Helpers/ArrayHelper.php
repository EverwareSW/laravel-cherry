<?php

namespace Everware\LaravelCherry\Helpers;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class ArrayHelper
{
    public static function filterFilled(array $array): array
    {
        return array_filter($array, 'filled');
    }

    /**
     * Push only the values that do not yet exist in the array.
     *
     * @param  array  $array
     * @param  bool  $strict  Compare values strictly or loosly.
     * @param  mixed  ...$values  Can also be associative array, then value is set in array using corresponding key.
     */
    public static function pushUnique(array &$array, bool $strict, ...$values): void
    {
        $valuesIsAssoc = \Arr::isAssoc($values);

        foreach ($values as $key => $value) {
            if (!in_array($value, $array, $strict)) {
                if ($valuesIsAssoc) {
                    $array[$key] = $value;
                } else {
                    $array[] = $value;
                }
            }
        }
    }

    /**
     * Expansion of @see \Arr::pull()
     *
     * @param  array  $array
     * @param  string  ...$keys
     * @return mixed
     */
    public static function pullMany(array &$array, string...$keys): array
    {
        $value = \Arr::only($array, $keys);

        \Arr::forget($array, $keys);

        return $value;
    }

    /**
     * Get a random value from the array, remove it from the array and return the value.
     *
     * @param array $array
     * @return mixed
     */
    public static function pullRandom(array &$array): mixed
    {
        $key = \Arr::random(array_keys($array));

        return \Arr::pull($array, $key);
    }

    /**
     * @param array $array
     * @param array<string, \Closure(mixed,string):mixed> $keysAndMaps
     * @return array
     */
    public static function onlyAndMap(?array $array, array $keysAndMaps): ?array
    {
        if ($array === null) {
            return null;
        }

        $return = [];
        foreach ($keysAndMaps as $key => $map) {
            $value = \Arr::get($array, $key, '__uNd3finEd___');
            if ($value !== '__uNd3finEd___') {
                $return[$key] = $map($value, $key);
            }
        }
        return $return;
    }

    /**
     * @param array $array
     * @param array<string, \Closure(mixed,string):array> $keysAndMaps
     * @return array
     */
    public static function onlyAndMapWithKeys(array $array, array $keysAndMaps): array
    {
        $return = [];
        foreach ($keysAndMaps as $key => $map) {
            $value = \Arr::get($array, $key);
            foreach ($map($value, $key) as $k => $v) {
                $return[$k] = $v;
            }
        }
        return $return;
    }

    /**
     * A concise way to map, filter and join an iterable while being able to use string callables like 'trim'.
     */
    public static function mapFilterJoin(string $glue, iterable $array, string $finalGlue = '', ?callable $filter = null, callable|string|null $map = 'trim'): string
    {
        return collect($array)
            ->when(isset($map), fn(Collection $c)=> $c->map(is_string($map) ? fn($v, $k)=>$map($v) : $map))
            ->filter($filter)
            ->join($glue, $finalGlue);
    }

    /**
     * @deprecated use {@see \Arr::mapWithKeys()}
     */
    public static function map(array $array, callable $callback): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $assoc = $callback($value, $key);

            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return $result;
    }

    /**
     * Convert an array that looks like this `["hi" => ["hello" => "bye"]]`
     * Into an array that looks like this: `["hi[hello]" => "bye"]`.
     */
    public static function urlKeys(array $array, string $parentKey = ''): array
    {
        $return = [];
        foreach ($array as $key => $value) {
            $newKey = $parentKey ? "{$parentKey}[$key]" : $key;
            if (is_array($value)) {
                $return += static::urlKeys($value, $newKey);
            } else {
                $return[$newKey] = $value;
            }
        }

        return $return;
    }

    /**
     * @param array $array
     * @param array $map  [oldKey => newKey]
     * @return array
     */
    public static function renameKeys(array &$array, array $map): array
    {
        foreach ($map as $oldKey => $newKey) {
            $value = \Arr::pull($array, $oldKey);
            \Arr::set($array, $newKey, $value);
        }

        return $array;
    }

    /**
     * Based on @see \Arr::dot()
     *
     * @param  array  $array
     * @param  string  $prefix
     * @param  string  $suffix
     * @param  bool  $recursive
     * @return array
     */
    public static function prefixKeys(array $array, string $prefix, string $suffix = '', bool $recursive = false): array
    {
        $return = [];

        $isAssoc = \Arr::isAssoc($array);
        foreach ($array as $key => $value) {

            if ($recursive && is_array($value)) {
                $value = static::prefixKeys($value, $prefix, $suffix, $recursive);
            }
            if ($isAssoc) {
                $key = "$prefix$key$suffix";
            }

            $return[$key] = $value;
        }

        return $return;
    }

    /**
     * Shuffle any array, associative or non-associative.
     *
     * @param array $array
     * @param int|null $seed
     * @return array
     */
    public static function shuffle(array $array, ?int $seed = null): array
    {
        if (!\Arr::isAssoc($array)) {
            return \Arr::shuffle($array, $seed);
        }

        $rtn = [];
        foreach (\Arr::shuffle(array_keys($array), $seed) as $key) {
            $rtn[$key] = $array[$key];
        }

        return $rtn;
    }

    /**
     * Like @see Collection::every() but you can use string callables like 'is_int' and pass Closures as the array's values.
     *
     * @param ?callable $callable Can be e.g. 'is_int'
     */
    public static function every(iterable $iter, ?callable $callable = null): bool
    {
        foreach ($iter as $k => $v) {
            // Not passing $k to $callable so it can be e.g. 'is_int'.
            if ($callable ? !$callable(value($v)) : !value($v)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Like @see Collection::some() but you can use string callables like 'is_int' and pass Closures as the array's values.
     *
     * @param ?callable $callable Can be e.g. 'is_int'
     */
    public static function some(iterable $iter, ?callable $callable = null): bool
    {
        foreach ($iter as $k => $v) {
            // Not passing $k to $callable so it can be e.g. 'is_int'.
            if ($callable ? $callable(value($v)) : value($v)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an object is an instance of any of the given classes.
     */
    public static function instanceOf(object $object, string...$classes): bool
    {
        foreach ($classes as $class) {
            if ($object instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * Merge dictionaries and child dictionaries recursively. The first array key takes precedence.
     * Does not merge lists (arrays with numeric keys).
     *
     * @param array ...$arrays
     * @return array
     */
    public static function mergeRecursively(array...$arrays): array
    {
        $rtn = array_shift($arrays);
        foreach ($arrays as $array) {
            foreach ($array as $key => $newValue) {
                if (!array_key_exists($key, $rtn)) {
                    $rtn[$key] = $newValue;
                    continue;
                }

                $oldValue = $rtn[$key];
                if (is_array($oldValue) || is_array($newValue)) {
                    // Not using array_unique() because see https://stackoverflow.com/a/18373723/3017716#comment-117672159
                    $rtn[$key] = static::mergeRecursively(\Arr::wrap($oldValue), \Arr::wrap($newValue));
                    //// Clone any objects to avoid reference issues.
                    // $rtn[$key] = array_map('unserialize', array_unique(array_map('serialize', $rtn[$key])));
                }
            }
        }

        return $rtn;
    }

    /**
     * Like @see data_set() but with smarter wildcard replacements.
     */
    public static function overwriteWildcards(array|\ArrayAccess &$target, string $key, mixed $values, bool $map = false): array|\ArrayAccess
    {
        if (!str_contains($key, '*')) { // !in_array('*', explode('.', $key))
            $map and $values = $values(\Arr::get($target, $key), $key);
            \Arr::set($target, $key, $values);
            return $target;
        }

        if (!$map) {
            foreach (static::iterable($values) as $k => $v) {
                $indexedKey = \Str::replaceFirst('*', $k, $key);
                static::overwriteWildcards($target, $indexedKey, $v, $map);
            }
        } else {
            for ($i = 0; true; $i++) {
                preg_match('/^.*?\*/', $key, $match);
                $indexedKeyPrefix = \Str::replaceFirst('*', $i, $match[0]);
                if (!\Arr::has($target, $indexedKeyPrefix)) {
                    break;
                }
                $indexedKey = \Str::replaceFirst('*', $i, $key);
                static::overwriteWildcards($target, $indexedKey, $values, $map);
            }
        }

        return $target;
    }

    /**
     * @param  array  $options  E.g. `['one','two','three']`
     * @return array  E.g. `[ ['one','two','three'], ['one','three','two'] ... ]
     */
    public static function getVariations(array $options): array
    {
        $optionAmount = count($options);

        if ($optionAmount === 0) {
            return [];
        } elseif ($optionAmount === 1) {
            return $options;
        } elseif ($optionAmount === 2) {
            return [$options, array_reverse($options)];
        }

        $variations = [];
        foreach ($options as $key => $value) {

            $optionsCopy = $options;
            unset($optionsCopy[$key]);

            $variationsAfter = static::getVariations($optionsCopy);
            foreach ($variationsAfter as $variationAfter) {
                // array_merge() ignores any integer keys and merges as non-assoc array,
                // even if the key is greater than 0. It only merges assoc with string keys.
                $variation = array_merge([$key => $value], $variationAfter);
                $variations[] = $variation;
            }
        }

        return $variations;
    }

    /**
     * @param  array  $array Like `[ 'name'=>['one','two'], 'direction'=>['left','right'] ]`
     * @param  array  $base
     * @return array  Like `[ ['name'=>'one', 'direction'=>'left'], ['name'=>'one', 'direction'=>'right'] ... ]`
     */
    public static function getMultiDimensionalVariations(array $array, array $base = []): array
    {
        $variations = [];

        $options = reset($array);
        $key = key($array);
        unset($array[$key]);

        foreach ($options as $option) {
            $current = $base + [$key => $option];

            if (empty($array)) {
                $variations[] = $current;
            } else {
                $nextVariations = static::getMultiDimensionalVariations($array, $current);
                array_push($variations, ...$nextVariations);
            }
        }

        return $variations;
    }

    /**
     * @param string[][] $a
     */
    public static function toHtmlTable(
        array  $a,
        string $tableAttrs = '',
        string $trAttrs = '',
        string $thAttrs = '',
        string $tdAttrs = '',
        string $thTdAttrs = '',
        bool   $thRow1 = false,
        bool   $thCol1 = false,
        bool   $encode = true,
    ) : HtmlString
    {
        $table = "<table $tableAttrs>";

        $firstRow = true;
        foreach ($a as $row) {
            $table .= "<tr $trAttrs>";

            $firstColumn = true;
            foreach ($row as $column) {
                $tag = $firstRow && $thRow1 || $firstColumn && $thCol1 ? 'th' : 'td';
                $table .= "<$tag " . ($tag === 'th' ? $thAttrs : $tdAttrs) . " $thTdAttrs>" . ($encode ? e($column) : $column) . "</$tag>";
                $firstColumn = false;
            }

            $table .= '</tr>';
            $firstRow = false;
        }

        $table .= '</table>';

        return new HtmlString($table);
    }

    /**
     * Like {@see \Arr::wrap()} but allows any iterable instead of only arrays.
     */
    public static function iterable($param): iterable
    {
        return $param === null ? [] : (is_iterable($param) ? $param : [$param]);
    }

    /**
     * Strip all required/nullable rules and only preserve defining rules.
     * @param array<string, string|array<string|Rule|ValidationRule>> $rules E.g. `['field' => 'required|string', 'other_field' => ['required', 'integer']]`.
     * @return array<string, array> E.g. `['field' => ['string'], 'other_field' => ['integer']]`
     */
    public static function onlyTypeValidation(array $rules): array
    {
        foreach ($rules as $attribute => $attributeRules) {
            /** Based on {@see ValidationRuleParser::explodeExplicitRule()}. */
            if (is_string($attributeRules)) {
                $attributeRules = explode('|', $attributeRules);
            }
            $rules[$attribute] = array_filter($attributeRules, fn($r)
                => is_object($r)
                ? !static::instanceOf($r, RequiredIf::class)
                : !\Str::startsWith($r, [
                    'nullable',
                    'present',
                    'required',
                    'sometimes', // necessary?
                ])
            );
        }

        return $rules;
    }
}