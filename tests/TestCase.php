<?php

namespace Everware\LaravelCherry\Tests;

use Everware\LaravelCherry\Tests\Concerns\TestCaseHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Workbench\Database\Seeders\RolePermissionSeeder;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench, RefreshDatabase, TestCaseHelper;

    /** {@see PackageManifest::getManifest()}. */
    protected $enablesPackageDiscoveries = true;

    /** {@see RefreshDatabase::migrateFreshUsing()} from {@see static::setUpTraits()} */
    protected string $seeder = RolePermissionSeeder::class;
}