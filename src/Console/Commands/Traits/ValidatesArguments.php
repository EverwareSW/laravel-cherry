<?php

namespace Everware\LaravelCherry\Console\Commands\Traits;

use Illuminate\Validation\ValidationException;

trait ValidatesArguments
{
    /**
     * @param  array|null  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return array|int
     */
    public function validate(?array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        try {
            return validator($data ?? $this->arguments(), $rules, $messages, $customAttributes)->validate();
        }
        catch (ValidationException $e) {
            $this->error($e->getMessage());
            $this->error(json_encode($e->errors()));
            return 1;
        }
    }
}