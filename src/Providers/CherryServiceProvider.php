<?php

namespace Everware\LaravelCherry\Providers;

use Everware\LaravelCherry\Console\Commands\DispatchJobCommand;
use Everware\LaravelCherry\Console\Commands\TestResnapCommand;
use Illuminate\Support\ServiceProvider;
use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand;

class CherryServiceProvider extends ServiceProvider
{
    public function register()
    {
        // $this->mergeConfigFrom(
        //     __DIR__.'/../../config/auth.guards.php', 'auth.guards'
        // );
    }

    public function boot(): void
    {
        $namespace = 'cherry';

        $this->loadTranslationsFrom(__DIR__.'/../../lang', $namespace);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../lang' => app()->langPath("vendor/$namespace"),
            ], 'translations');

            $this->commands(\HArr::filterFilled([
                DispatchJobCommand::class,
                // Because TestComment not installed when `composer install --no-dev`.
                class_exists(TestCommand::class) ? TestResnapCommand::class : null,
            ]));
        }
    }
}