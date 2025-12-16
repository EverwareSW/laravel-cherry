<?php

namespace Everware\LaravelCherry\Enums\Traits;

trait CherryTranslatableEnum
{
    public function translate(): string
    {
        return __('enums.' . static::class . '.' . $this->name);
    }

    public static function translatedArray(): array
    {
        return collect(static::cases())
            ->mapWithKeys(fn($enum) => [$enum->value => $enum->translate()])
            ->all();
    }
}