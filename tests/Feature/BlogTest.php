<?php

use Workbench\App\Models\Blog;
use Workbench\App\Models\UserWithRoles;

// test('action permission protection', function() {
//     $this->setFactorySeed(34237529342);
//
//     \Date::setTestNow('2025-04-28 10:00:00');
//     $blogs = Blog::factory(2)->create();
//     $route = route('api.blogs.index');
//     $response = $this->getJson($route);
//     $response->assertUnauthorized();
//
//     $auth = User::factory()->create();
//     $this->actingAs($auth);
//     $response = $this->getJson($route);
//     $response->assertForbidden();
//
//     $auth->syncRoles('admin');
//     $response = $this->getJson($route);
//     $response->assertOk();
// })
//     ->skip(!class_exists(Spatie\Permission\PermissionServiceProvider::class));

test('', function() {
    $this->setFactorySeed(34237529342);

    \Date::setTestNow('2025-04-28 10:00:00');
    $blogs = Blog::factory(2)->create();
    $route = route('api.blogs.index');
    $response = $this->getJson($route);
    $response->assertUnauthorized();

    $auth = UserWithRoles::factory()->create();
    $this->actingAs($auth);
    $response = $this->getJson($route);
    $response->assertForbidden();

    // app(EnableTwoFactorAuthentication::class)($auth);
    // $response = $this->getJson($route);
    // $response->assertUnauthorized();

    // /** {@see ConfirmTwoFactorAuthentication::__invoke()}. */
    // $auth->forceFill(['two_factor_confirmed_at' => now()])->save();
    // $response = $this->getJson($route);
    // $response->assertForbidden();

    $auth->syncRoles('admin');
    $response = $this->getJson($route);
    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    expect($response)
        ->dynamicJsonSnapshot(['data.*.id'])
        ->toMatchSnapshot();
})
    ->skip(!class_exists(Spatie\Permission\PermissionServiceProvider::class)
        || !class_exists(Spatie\LaravelData\Data::class));