<?php

namespace Everware\LaravelCherry\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class StringOrArrayRule implements ValidationRule
{
    /**
     *
     * @param null | \Closure(string, string, \Closure(string, ?string=): PotentiallyTranslatedString): void $validateSingle
     * @param null | \Closure(string, array, \Closure(string, ?string=): PotentiallyTranslatedString): void $validateArray
     */
    public function __construct(
        public ?\Closure $validateSingle = null,
        public ?\Closure $validateArray = null,
    ){}

    /**
     * @inheritDoc
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (is_string($value)) {
            $this->validateSingle and ($this->validateSingle)($attribute, $value, $fail);
            return;
        } else if (is_array($value) && \HArr::every($value, 'is_string')) {
            $this->validateArray and ($this->validateArray)($attribute, $value, $fail);
            if ($this->validateSingle) foreach ($value as $v) ($this->validateSingle)($attribute, $v, $fail);
            return;
        }

        $fail('ptchr-bb::validation.string_or_array')->translate();
    }
}
