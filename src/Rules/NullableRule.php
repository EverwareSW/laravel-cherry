<?php

namespace Everware\LaravelCherry\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class NullableRule implements ValidationRule
{
    public function __construct(
        protected string|array $rules,
    ){}

    /**
     * @inheritDoc
     * Also {@see ArrayKeyRule::validate()}.
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (is_string($value) && (strtolower($value) === 'null' || strtolower($value) === 'notnull')) {
            return;
        }

        $validator = validator([$attribute => $value], [$attribute => $this->rules]);

        /** {@see Validator::passes()}. */
        if ($validator->fails()) {
            /** {@see Validator::addFailure()}. */
            foreach ($validator->getMessageBag()->getMessages() as $attr => $messages) {
                foreach ($messages as $message) {
                    $fail($message);
                }
            }
        }
    }
}
