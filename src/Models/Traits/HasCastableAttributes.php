<?php

namespace Everware\LaravelCherry\Models\Traits;

use Illuminate\Support\Collection;

trait HasCastableAttributes
{
    /** Based on @see Model::getAttribute() */
    public function getCastedAttributes(): Collection
    {
        return collect($this->getAttributes())->map(function($value, $key) {
            return $this->transformModelValue($key, $value);
        });
    }
}
