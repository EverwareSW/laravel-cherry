<?php

namespace Everware\LaravelCherry\Console\Commands\Interfaces;

interface CreateFromStringParams
{
    public static function fromParams(string...$params): static;
}