<?php

namespace Everware\LaravelCherry\Helpers;

class StringHelper
{
    /** @return string[] */
    public static function explodeTrim(string $delimiter, string $value): array
    {
        return array_map('trim', explode($delimiter, $value));
    }

    /** @return string[] */
    public static function explodeTrimFilter(string $delimiter, string $value): array
    {
        return array_filter(static::explodeTrim($delimiter, $value), fn($v)=> $v !== '');
    }

    /**
     * Useful when you have an object description or name but not a unique key.
     * This way you make it less probable human typos cause duplicate keys.
     */
    public static function cleanIntoKey(string $description): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($description));
    }

    /**
     * Base64UrlEncode a string.
     * Based on https://stackoverflow.com/a/56314337/3017716
     * and https://dev.to/robdwaller/how-to-create-a-json-web-token-using-php-3gml
     *
     * Also {@see FileHelper::dataToBase64()} and {@see FileHelper::fileToBase64()}.
     *
     * @param string|array $data
     * @return string
     */
    public static function base64UrlEncode(string|array $data): string
    {
        if (!is_string($data)) {
            /** Json encoded like @see Client::applyOptions() would. */
            $data = json_encode($data);
        }

        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Base64UrlDecode a string.
     * Based on https://stackoverflow.com/a/56314337/3017716
     * and https://dev.to/robdwaller/how-to-create-a-json-web-token-using-php-3gml
     *
     * Also {@see FileHelper::base64ToFile()}.
     *
     * @param string $string
     * @return string
     */
    public static function base64UrlDecode(string $string): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $string));
    }

    /**
     * Somewhat more convenient way to encode using HMAC.
     *
     * @param string $key
     * @param string|array $data
     * @param string $algo
     * @return string
     */
    public static function hmac(string $key, string|array $data, string $algo = 'sha256'): string
    {
        if (!is_string($data)) {
            /** Json encoded like @see Client::applyOptions() would. */
            $data = json_encode($data);
        }

        return hash_hmac($algo, $data, $key);
    }

    /**
     * Format a phone number safely for use in an HTML <a href> attribute.
     * Also @see PhoneRule::passes()
     *
     * @param string $phoneNumber
     * @return string
     */
    public static function cleanPhone(string $phoneNumber): string
    {
        return preg_replace('/[^0-9\+\#\*]/', '', $phoneNumber);
    }

    /**
     * Returns empty string if $search not found (instead of complete $subject like {@see \Str::after()}).
     * Based on {@see \Str::after()} and {@see \Str::afterLast()}
     */
    public static function after(string $subject, string $search, bool $afterLast = false, bool $includeSearch = false): string
    {
        if ($search === '') {
            return '';
        }

        $position = $afterLast ? strrpos($subject, $search) : strpos($subject, $search);

        if ($position === false) {
            return '';
        }

        if (!$includeSearch) {
            $position += strlen($search);
        }

        return substr($subject, $position);
    }

    /**
     * Like @see \Str::is() but without the start '^' and end '\z' of string restrictions.
     * You can use '*' as a wildcard and optionally '^' and '$' at the start and end of the patterns to match the start and end of a line.
     *
     * @param string|array $patterns Match any of these wildcard strings.
     * @param string $mods See https://www.php.net/manual/en/reference.pcre.pattern.modifiers.php.
     */
    public static function contains(string $haystack, string|array $patterns, string $mods = 'msuU'): bool
    {
         foreach ((array) $patterns as $pattern) {
             $pattern = (string) $pattern;
             $pattern = preg_quote($pattern, '/');
             $pattern = str_replace('\*', '.*', $pattern);
             str_starts_with($pattern, '\^') and $pattern = substr($pattern, 1);
             str_ends_with($pattern, '\$') and $pattern = substr($pattern, 0, -2) . '$';
             if (preg_match("/$pattern/$mods", $haystack) === 1) {
                 return true;
             }
         }

         return false;
    }

    /**
     * Check if any of the haystacks contains any of the needles.
     */
    public static function anyContains(string|iterable $haystacks, string|iterable $needles): bool
    {
        if (!is_iterable($haystacks)) {
            $haystacks = (array) $haystacks;
        }

        foreach ($haystacks as $haystack) {
            if (\Str::contains($haystack, $needles)) {
                return true;
            }
        }

        return false;
    }
}