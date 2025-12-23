<?php

namespace Everware\LaravelCherry\Http\Middleware;

use Illuminate\Http\Request;

class MfaEnabledMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        $route = $request->route();
        $middleware = $route->gatherMiddleware();

        // Intentionally 'sanctum', not 'fortify-sanctum' because we only use that guard for Fortify routes,
        // we use the sanctum guard to authenticate after we've used fortify-sanctum for the initial login.
        if (!in_array('auth:sanctum', $middleware)
        //TODO route exceptions e.g.
        || $route->named('api.me')
        //TODO mfa_enabled check e.g.
        || \Auth::user()?->mfa_enabled) {
            return $next($request);
        }

        \Gate::denyWithStatus(401, 'Multi-factor authentication must be enabled.')->authorize();
    }
}