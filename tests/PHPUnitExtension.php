<?php declare(strict_types=1);

namespace Everware\LaravelCherry\Tests;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class PHPUnitExtension implements Extension
{
    /**
     * This method is called when PHPUnit loads the extension.
     */
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        /** {@see Application::initializeTestResultCache()}. */
        $facade->registerSubscriber(new LastRunSubscriber($configuration));
    }
}