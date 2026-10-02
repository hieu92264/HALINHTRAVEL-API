<?php

use App\Modules\MasterData\Controllers\CustomerController;
use App\Modules\MasterData\Controllers\DriverController;
use App\Modules\MasterData\Controllers\PartnerController;
use App\Modules\MasterData\Controllers\VehicleController;
use App\Modules\MasterData\Controllers\VehicleTypeController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('master-data')->group(function () {
    Route::middleware('auth:api')->group(function () {
        // Customer routes
        Route::middleware('permission:'.PermissionEnum::CUSTOMERS_VIEW->value)->group(function () {
            Route::get('/customers', [CustomerController::class, 'index']);
            Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        });

        Route::middleware('permission:'.PermissionEnum::CUSTOMERS_MANAGE->value)->group(function () {
            Route::post('/customers', [CustomerController::class, 'store']);
            Route::put('/customers/{customer}', [CustomerController::class, 'update']);
            Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
        });

        // Partner routes
        Route::middleware('permission:'.PermissionEnum::PARTNERS_VIEW->value)->group(function () {
            Route::get('/partners', [PartnerController::class, 'index']);
            Route::get('/partners/{partner}', [PartnerController::class, 'show']);
        });

        Route::middleware('permission:'.PermissionEnum::PARTNERS_MANAGE->value)->group(function () {
            Route::post('/partners', [PartnerController::class, 'store']);
            Route::put('/partners/{partner}', [PartnerController::class, 'update']);
            Route::delete('/partners/{partner}', [PartnerController::class, 'destroy']);
        });

        // Vehicle type routes
        Route::middleware('permission:'.PermissionEnum::VEHICLE_TYPES_VIEW->value)->group(function () {
            Route::get('/vehicle-types', [VehicleTypeController::class, 'index']);
            Route::get('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'show']);
        });

        Route::middleware('permission:'.PermissionEnum::VEHICLE_TYPES_MANAGE->value)->group(function () {
            Route::post('/vehicle-types', [VehicleTypeController::class, 'store']);
            Route::put('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'update']);
            Route::delete('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'destroy']);
        });

        // Vehicle routes
        Route::middleware('permission:'.PermissionEnum::VEHICLES_VIEW->value)->group(function () {
            Route::get('/vehicles', [VehicleController::class, 'index']);
            Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show']);
        });

        Route::middleware('permission:'.PermissionEnum::VEHICLES_MANAGE->value)->group(function () {
            Route::post('/vehicles', [VehicleController::class, 'store']);
            Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update']);
            Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy']);
        });

        // Driver routes
        Route::middleware('permission:'.PermissionEnum::DRIVERS_VIEW->value)->group(function () {
            Route::get('/drivers', [DriverController::class, 'index']);
            Route::get('/drivers/{driver}', [DriverController::class, 'show']);
        });

        Route::middleware('permission:'.PermissionEnum::DRIVERS_MANAGE->value)->group(function () {
            Route::post('/drivers', [DriverController::class, 'store']);
            Route::put('/drivers/{driver}', [DriverController::class, 'update']);
            Route::delete('/drivers/{driver}', [DriverController::class, 'destroy']);
        });
    });
});
