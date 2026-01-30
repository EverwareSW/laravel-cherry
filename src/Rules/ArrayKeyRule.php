<?php

namespace Everware\LaravelCherry\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class ArrayKeyRule implements ValidationRule
{
    public function __construct(
        /** {@see Validator::setRules()} */
        protected string|array $rules,
    ){}

    /**
     * @inheritDoc
     * Also {@see NullableRule::validate()}.
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $validator = validator(\Arr::undot(["$attribute:key" => \Str::afterLast($attribute, '.')]), ["$attribute:key" => $this->rules]);

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
