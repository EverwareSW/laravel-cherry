<?php

namespace Everware\LaravelCherry\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Guard;
use Spatie\Permission\Traits\HasPermissions;

class RolePermissionSeeder extends Seeder
{
    /** @var class-string Define User model. */
    protected static string $USER_MODEL = \App\Models\User::class;

    /** @var bool Delete all permissions that (no longer) exist in {@see rolesPermissions()} array. */
    public static bool $DELETE_PERMISSIONS = true;

    /** @var array<string, array<string|\StringBackedEnum>> Define roles and permissions. */
    protected static array $ROLES_PERMISSIONS = [
        'admin' => [
            // You only have to add permissions to admin that do not exist within any other Role.
        ],
        '' => [
            // Add permissions here that don't belong to any Role but do need to exist.
        ],
    ];

    /** @return array<string, array<string|\StringBackedEnum>> Define roles and permissions. */
    protected static function rolesPermissions(): array
    {
        return static::$ROLES_PERMISSIONS;
    }

    /*
     *
     * Internals
     *
     */

    public static function getRolesWithPermissionsStructure(): array
    {
        static $rolesWithPermissions;

        if ($rolesWithPermissions === null) {
            // Copies array.
            $rolesWithPermissions = static::rolesPermissions();
            $adminName = isset($rolesWithPermissions['Admin']) ? 'Admin' : (isset($rolesWithPermissions['admin']) ? 'admin' : null);

            foreach ($rolesWithPermissions as $roleName => $permissionNames) {
                // Add the 'anyone' permission to everyone.
                $rolesWithPermissions[$roleName][] = 'anyone';

                // Add all permissions that weren't added solely to admin, to admin.
                if ($adminName !== null && $roleName !== $adminName) {
                    foreach ($permissionNames as $permissionName) {
                        if (!in_array($permissionName, $rolesWithPermissions[$adminName])) {
                            $rolesWithPermissions[$adminName][] = $permissionName;
                        }
                    }
                }
            }
        }

        return $rolesWithPermissions;
    }

    public function run()
    {
        /** @var class-string<\Spatie\Permission\Models\Role> $ROLE_MODEL */
        $ROLE_MODEL = config('permission.models.role');
        /** @var class-string<\Spatie\Permission\Models\Permission> $PERMISSION_MODEL */
        $PERMISSION_MODEL = config('permission.models.permission');

        // Reset cached roles and permissions, see https://spatie.be/docs/laravel-permission/v4/advanced-usage/seeding
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $structure = static::getRolesWithPermissionsStructure();

        $handledPermissionKeys = [];

        /**
         * config('auth.guards') is not correct
         * @see HasPermissions::ensureModelSharesGuard()
         * called from
         * @see HasRoles::assignRole()
         */
        $guards = Guard::getNames(static::$USER_MODEL);
        foreach ($guards as $guardName) {
            foreach ($structure as $roleName => $permissionNames) {
                foreach ($permissionNames as $permissionName) {
                    if ($permissionName instanceof \BackedEnum) {
                        $permissionName = $permissionName->value;
                    }

                    $permission = $PERMISSION_MODEL::findOrCreate($permissionName, $guardName);
                    $handledPermissionKeys[] = $permission->getKey();
                }

                if ($roleName !== '') {
                    // Reset cached roles and permissions, see https://spatie.be/docs/laravel-permission/v4/advanced-usage/seeding
                    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

                    $role = $ROLE_MODEL::findOrCreate($roleName, $guardName);
                    $role->syncPermissions(...$permissionNames);
                }
            }
        }

        if (static::$DELETE_PERMISSIONS) {
            $PERMISSION_MODEL::whereKeyNot($handledPermissionKeys)->delete();
        }
    }
}