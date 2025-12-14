<?php

namespace Everware\LaravelCherry\Providers;

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
        //
    }
}