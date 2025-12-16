<?php

namespace Everware\LaravelCherry\Providers;

use Everware\LaravelCherry\Console\Commands\DispatchJobCommand;
use Illuminate\Support\ServiceProvider;

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
        if ($this->app->runningInConsole()) {
            $this->commands([
                DispatchJobCommand::class,
            ]);
        }
    }
}