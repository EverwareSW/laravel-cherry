<?php

namespace Everware\LaravelCherry\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Concerns\ValidatesAttributes;
use Symfony\Component\Mime\Address;

/**
 * Allow e-mail strings like "john@wick.com" or "Alan Dutch Schaefer <arnold@schwarzenegger.at>"
 */
class EmailRule implements Rule
{
    use ValidatesAttributes;

    public function passes($attribute, $value): bool
    {
        try {
            $address = Address::create($value);
        /**
         * Both {@see \Symfony\Component\Mime\Exception\InvalidArgumentException}
         * and {@see \Symfony\Component\Mime\Exception\RfcComplianceException}
         */
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return $this->validateEmail($attribute, $address->getAddress(), ['strict']);
    }

    public function message(): string
    {
        return __('validation.email');
    }
}
