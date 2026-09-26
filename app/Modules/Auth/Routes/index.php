<?php

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/login', 'login')->middleware('throttle:login');

        Route::middleware('auth:api')->group(function () {
            Route::post('/refresh', 'refresh');
            Route::get('/me', 'me');
            Route::post('/logout', 'logout');
        });
    });
});
