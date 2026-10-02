<?php

use App\Modules\MasterData\Controllers\CustomerController;
use App\Modules\MasterData\Controllers\PartnerController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('master-data')->group(function () {
    Route::middleware('auth:api')->group(function () {
        // Customer routes
        Route::middleware('permission:' . PermissionEnum::CUSTOMERS_VIEW->value)->group(function () {
            Route::get('/customers', [CustomerController::class, 'index']);
            Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        });

        Route::middleware('permission:' . PermissionEnum::CUSTOMERS_MANAGE->value)->group(function () {
            Route::post('/customers', [CustomerController::class, 'store']);
            Route::put('/customers/{customer}', [CustomerController::class, 'update']);
            Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
        });

        // Partner routes
        Route::middleware('permission:' . PermissionEnum::PARTNERS_VIEW->value)->group(function () {
            Route::get('/partners', [PartnerController::class, 'index']);
            Route::get('/partners/{partner}', [PartnerController::class, 'show']);
        });

        Route::middleware('permission:' . PermissionEnum::PARTNERS_MANAGE->value)->group(function () {
            Route::post('/partners', [PartnerController::class, 'store']);
            Route::put('/partners/{partner}', [PartnerController::class, 'update']);
            Route::delete('/partners/{partner}', [PartnerController::class, 'destroy']);
        });
    });
});
