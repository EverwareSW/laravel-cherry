<?php

namespace Workbench\Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Workbench\App\Enums\PermissionEnum;

class RolePermissionSeeder extends \Everware\LaravelCherry\Database\Seeders\RolePermissionSeeder
{
    protected static string $USER_MODEL = \Workbench\App\Models\User::class;

    protected static function rolesPermissions(): array
    {
        return ['admin' => PermissionEnum::cases()];
    }
}