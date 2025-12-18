<?php

namespace Workbench\App;

use Everware\LaravelCherry\Enums\Traits\CherryEnum;

enum JustSomeEnum: string
{
    use CherryEnum;

    case Red = 'red';
    case Green = 'green';
    case Blue = 'blue';
}