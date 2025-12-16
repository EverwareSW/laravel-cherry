<?php

namespace Everware\LaravelCherry\Helpers;

use Illuminate\Http\RedirectResponse;

class InertiaHelper
{
    /**
     * Create a RedirectResponse that forces Inertia to hard redirect instead of inline.
     * Based on {@see Inertia::location()} which facades {@see ResponseFactory::location()}.
     *
     * Creates RedirectResponse instead of normal Response so can be used with Fortify Actions
     * E.g. {@see TeamController::store()} into {@see RedirectsActions::redirectPath()}.
     *
     * @param  string  $to
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return RedirectResponse
     */
    public static function createHardRedirectResponse(string $to, array $headers = [], ?bool $secure = null): RedirectResponse
    {
        // Create redirect response with 302 so no error is thrown, move to 409 after creation.
        $response = redirect($to, 302, ['X-Inertia-Location' => $to] + $headers, $secure);
        // Move redirect response to 409 so Inertia does full redirect instead of inline inject.
        $response->setStatusCode(409);

        return $response;
    }
}