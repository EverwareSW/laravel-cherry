<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;

class UserWithRoles extends User
{
    /** @use HasFactory<\Workbench\Database\Factories\UserFactory> */
    use HasRoles;

    protected $table = 'users';
}