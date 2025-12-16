<?php

namespace Everware\LaravelCherry\Data\Traits;

use Illuminate\Validation\Validator;
use Spatie\LaravelData\Support\Creation\CreationContext;

/**
 * Only works when https://github.com/spatie/laravel-data/pull/1108 is merged.
 */
trait DataStoreValidator
{
    protected ?Validator $validator = null;

    public function validator(): ?Validator
    {
        return $this->validator;
    }

    public static function withValidator(Validator $validator, ?CreationContext $context = null): void
    {
        if ($context instanceof \App\Data\Creation\CreationContext) {
            $context->validator = $validator;
        }
    }

    public static function makeWithContext(CreationContext $context, ...$properties): static
    {
        $data = new static(...$properties);
        $data->validator = $context->validator;
        return $data;
    }
}