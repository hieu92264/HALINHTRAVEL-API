<?php

use App\Modules\MasterData\Controllers\CustomerController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('master-data')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::middleware('permission:'.PermissionEnum::CUSTOMERS_VIEW->value)->group(function () {
            Route::get('/customers', [CustomerController::class, 'index']);
            Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        });

        Route::middleware('permission:'.PermissionEnum::CUSTOMERS_MANAGE->value)->group(function () {
            Route::post('/customers', [CustomerController::class, 'store']);
            Route::put('/customers/{customer}', [CustomerController::class, 'update']);
            Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
        });
    });
});
