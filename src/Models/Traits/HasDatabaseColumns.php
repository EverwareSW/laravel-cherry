<?php

namespace Everware\LaravelCherry\Models\Traits;

use Everware\LaravelCherry\Models\Repositories\ModelRepository;

trait HasDatabaseColumns
{
    public static function getColumns(): array
    {
        return ModelRepository::getModelColumns(new static);
    }

    public static function hasColumns(string...$columns): bool
    {
        return ModelRepository::modelHasColumns(new static, ...$columns);
    }
}
