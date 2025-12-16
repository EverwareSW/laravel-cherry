<?php

namespace Everware\LaravelCherry\Enums\Traits;

trait CherryEnum
{

    /** @return string[] */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /** @return (string|int)[] */
    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }

    /** @return array<string, string|int> */
    public static function array(): array
    {
        return array_combine(static::values(), static::names());
    }


    /**
     * @return static[]
     * @throws \ValueError
     */
    public static function fromValues(int|string|self...$values): array
    {
        return array_map(fn(int|string|self $value) => $value instanceof self ? $value : static::from($value), $values);
    }

    /** @return static[] Wrap with array_filter() if you don't want `null` entries. */
    public static function tryFromValues(int|string|self...$values): array
    {
        return array_map(fn(int|string|self $value) => $value instanceof self ? $value : static::tryFrom($value), $values);
    }


    /** Based on @see BackedEnum::tryFrom() */
    public static function tryFromName(string $name): ?static
    {
        foreach (static::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }
    }

    /** Based on @see BackedEnum::from() */
    public static function fromName(string $name): static
    {
        return static::tryFromName($name)
        ?? throw new \ValueError("\"$name\" is not a valid name for enum " . static::class);
    }


    /**
     * @return static[]
     * @throws \ValueError
     */
    public static function fromNames(string|self...$names): array
    {
        return array_map(fn(string|self $name) => $name instanceof self ? $name : static::fromName($name), $names);
    }

    /** @return static[] Wrap with array_filter() if you don't want `null` entries. */
    public static function tryFromNames(string|self...$names): array
    {
        return array_map(fn(string|self $name) => $name instanceof self ? $name : static::tryFrom($name), $names);
    }
}