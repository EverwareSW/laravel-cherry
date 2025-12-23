<?php

namespace Workbench\App\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

abstract class BlogAbstractData extends Data
{
    #[Max(127)]
    public string $title;

    #[Max(65535)]
    public string $content;
}