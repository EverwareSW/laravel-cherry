<?php

namespace Everware\LaravelCherry\Helpers;

// use JetBrains\PhpStorm\ExpectedValues;

/**
 * Based on @see parse_url()
 * #[ArrayShape(["scheme" => "string", "host" => "string", "port" => "int", "user" => "string", "pass" => "string", "query" => "string", "path" => "string", "fragment" => "string"])]
 *
 *
 * @property-read string|null scheme
 * @property-read string|null host
 * @property-read int|null port
 * @property-read string|null user
 * @property-read string|null pass
 * @property-read string|null query
 * @property-read string|null path
 * @property-read string|null fragment
 * @method UrlHelper scheme(string|null $part)
 * @method UrlHelper host(string|null $part)
 * @method UrlHelper port(int|null $part)
 * @method UrlHelper user(string|null $part)
 * @method UrlHelper pass(string|null $part)
 * @method UrlHelper query(string|null $part)
 * @method UrlHelper path(string|null $part)
 * @method UrlHelper fragment(string|null $part)
 */
class UrlHelper
{
    public static function from(string $url, bool $strict = true): static
    {
        return new static($url, $strict);
    }

    protected array $parts;

    public function __construct(string $url, bool $strict = true)
    {
        // Will throw error if is not valid URL.
        // See https://www.php.net/manual/en/function.parse-url.php
        $parts = parse_url($url);

        if (!is_array($parts)) {
            if ($strict) {
                throw new \InvalidArgumentException("Invalid URL: '$url'.");
            } else {
                $parts = [];
            }
        }

        $this->parts = $parts;
    }

    /*
     *
     * Setters
     *
     */

    public function __call(
        /** Based on @see parse_url() */
        // #[ExpectedValues(["scheme", "host", "port", "user", "pass", "query", "path", "fragment"])] // Does this work without PhpStorm?
        string $name,
        array $arguments
    ) : static {
        $this->parts[$name] = $arguments[0];
        return $this;
    }

    public function subdomain(?string $subdomain): static
    {
        // Takes single top-level domain (e.g. 'localhost') in account.
        $domainLevels = array_slice(explode('.', $this->host), -2);
        $subdomain !== null && $subdomain !== '' and array_unshift($domainLevels, rtrim($subdomain, '.'));
        $this->host(implode('.', $domainLevels));
        return $this;
    }

    public function explicitPort(): static
    {
        return $this->port($this->port ?? ($this->scheme === 'https' ? 443 : 80));
    }

    public function setQuery(array $query): static
    {
        return $this->query(http_build_query($query));
    }

    public function addQuery(array $query): static
    {
        parse_str($this->query ?? '', $currentQuery);
        return $this->setQuery(ArrayHelper::mergeRecursively($query, $currentQuery));
    }

    /*
     *
     * Getters
     *
     */

    public function __get(string $name): string|int|null
    {
        return $this->parts[$name] ?? null;
    }

    public function getHostPort(): string
    {
        return $this->port ? "$this->host:$this->port" : "$this->host";
    }

    /** Also {@see \http\Url} and {@see http_build_url()} */
    public function __toString(): string
    {
        $p = $this->parts;
        // Based on https://stackoverflow.com/a/35207936/3017716
        $builder = "scheme://user:pass@host:portpath?query#fragment";

        if (empty($p['scheme']))
            $builder = str_replace('scheme:', '', $builder);
        if (empty($p['user']) && empty($p['pass']) && empty($p['host']))
            $builder = str_replace('//', '', $builder);
        if (empty($p['user']))
            $builder = str_replace('user', '', $builder);
        if (empty($p['pass']))
            $builder = str_replace(':pass', '', $builder);
        if (empty($p['user']) && empty($p['pass']))
            $builder = str_replace('@', '', $builder);
        if (empty($p['host']))
            $builder = str_replace('host', '', $builder);
        if (empty($p['port']))
            $builder = str_replace(':port', '', $builder);
        if (empty($p['path']))
            $builder = str_replace('path', '', $builder);
        if (empty($p['query']))
            $builder = str_replace('?query', '', $builder);
        if (empty($p['fragment']))
            $builder = str_replace('#fragment', '', $builder);

        return strtr($builder, $p);
    }
}