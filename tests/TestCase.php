<?php

namespace Everware\LaravelCherry\Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    /** {@see PackageManifest::getManifest()}. */
    protected $enablesPackageDiscoveries = true;
}