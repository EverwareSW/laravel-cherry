<?php

namespace Workbench\App\Enums;

use Everware\LaravelCherry\Enums\Traits\CherryEnum;

enum PermissionEnum: string
{
    use CherryEnum;

    case BlogsIndex   = 'blogs.index';
    case BlogsShow    = 'blogs.show';
    case BlogsStore   = 'blogs.store';
    case BlogsUpdate  = 'blogs.update';
    case BlogsDestroy = 'blogs.destroy';
}