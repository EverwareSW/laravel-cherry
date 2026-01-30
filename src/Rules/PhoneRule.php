<?php

namespace Everware\LaravelCherry\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Validate string is valid phone number (only allowed special characters: ' +*#')
 * Comprehensive example: "#31# +31 *12345678"
 *
 * You can optionally pass a separator to validate multiple phone numbers in the same string.
 * However, it's cleaner to split the string in FE and validate an array of strings (e.g. `'numbers.*' => new PhoneRule`).
 */
class PhoneRule implements Rule
{
    public function __construct(
        protected string $separator = '',
    ){}

    /**
     * Also @see StringHelper::cleanPhone()
     *
     * @param string $attribute
     * @param string $value
     */
    public function passes($attribute, $value): bool
    {
        $regex = '([\s\+\*\#0-9]{3,})';
        if ($this->separator) {
            $separator = preg_quote($this->separator, '/');
            $regex .= $separator . $regex;
        }

        $matches = preg_match("/^$regex$/", $value);
        return $matches;
    }

    public function message(): string
    {
        return __('ptchr-bb::validation.phone');
    }
}
