<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\BlogController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::name('api.')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {

        Route::apiResource('blogs', BlogController::class);
    });
});