<?php

namespace Everware\LaravelCherry\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class IntOrArrayRule implements ValidationRule
{
    /**
     * @inheritDoc
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (\HNum::isInt($value)) {
            return;
        } else if (is_array($value) && !empty($value) && \HArr::every($value, [\HNum::class, 'isInt'])) {
            return;
        }

        $fail('ptchr-bb::validation.int_or_array')->translate();
    }
}
