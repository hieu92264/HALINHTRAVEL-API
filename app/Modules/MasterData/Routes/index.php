<?php

use App\Modules\MasterData\Controllers\CustomerController;
use App\Modules\MasterData\Controllers\DriverController;
use App\Modules\MasterData\Controllers\ExpenseTypeController;
use App\Modules\MasterData\Controllers\PartnerController;
use App\Modules\MasterData\Controllers\RouteController;
use App\Modules\MasterData\Controllers\VehicleController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('master-data')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::middleware('permission:'.PermissionEnum::PARTNERS_VIEW->value)->group(function () {
            Route::get('/partners/options', [PartnerController::class, 'options']);
        });

        Route::middleware('permission:'.PermissionEnum::ROUTES_MANAGE->value)->group(function () {
            Route::get('/customers/options', [CustomerController::class, 'options']);
        });

        Route::middleware('permission:'.PermissionEnum::VEHICLES_VIEW->value)->group(function () {
            Route::get('/vehicles/options', [VehicleController::class, 'options']);
            Route::get('/vehicles', [VehicleController::class, 'index']);
            Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show']);
        });

        Route::middleware('permission:'.PermissionEnum::VEHICLES_MANAGE->value)->group(function () {
            Route::post('/vehicles', [VehicleController::class, 'store']);
            Route::patch('/vehicles/{vehicle}', [VehicleController::class, 'update']);
            Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy']);
        });

        // Customer routes
        Route::middleware('permission:'.PermissionEnum::CUSTOMERS_VIEW->value)->group(function () {
            Route::get('/customers', [CustomerController::class, 'index']);
            Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        });

        Route::middleware('permission:'.PermissionEnum::CUSTOMERS_MANAGE->value)->group(function () {
            Route::post('/customers', [CustomerController::class, 'store']);
            Route::patch('/customers/{customer}', [CustomerController::class, 'update']);
            Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
        });

        // Partner routes
        Route::middleware('permission:'.PermissionEnum::PARTNERS_VIEW->value)->group(function () {
            Route::get('/partners', [PartnerController::class, 'index']);
            Route::get('/partners/{partner}', [PartnerController::class, 'show']);
        });

        Route::middleware('permission:'.PermissionEnum::PARTNERS_MANAGE->value)->group(function () {
            Route::post('/partners', [PartnerController::class, 'store']);
            Route::patch('/partners/{partner}', [PartnerController::class, 'update']);
            Route::delete('/partners/{partner}', [PartnerController::class, 'destroy']);
        });

        // Driver routes
        Route::middleware('permission:'.PermissionEnum::DRIVERS_VIEW->value)->group(function () {
            Route::get('/drivers', [DriverController::class, 'index']);
            Route::get('/drivers/{driver}', [DriverController::class, 'show']);
        });

        Route::middleware('permission:'.PermissionEnum::DRIVERS_MANAGE->value)->group(function () {
            Route::post('/drivers', [DriverController::class, 'store']);
            Route::patch('/drivers/{driver}', [DriverController::class, 'update']);
            Route::delete('/drivers/{driver}', [DriverController::class, 'destroy']);
        });

        // Route routes
        Route::middleware('permission:'.PermissionEnum::ROUTES_VIEW->value)->group(function () {
            Route::get('/routes', [RouteController::class, 'index']);
            Route::get('/routes/{route}', [RouteController::class, 'show']);
        });

        Route::middleware('permission:'.PermissionEnum::ROUTES_MANAGE->value)->group(function () {
            Route::post('/routes', [RouteController::class, 'store']);
            Route::put('/routes/{route}', [RouteController::class, 'update']);
            Route::delete('/routes/{route}', [RouteController::class, 'destroy']);
        });

        // Expense type routes
        Route::middleware('permission:'.PermissionEnum::EXPENSE_TYPES_VIEW->value)->group(function () {
            Route::get('/expense-types', [ExpenseTypeController::class, 'index']);
            Route::get('/expense-types/{expenseType}', [ExpenseTypeController::class, 'show']);
        });

        Route::middleware('permission:'.PermissionEnum::EXPENSE_TYPES_MANAGE->value)->group(function () {
            Route::post('/expense-types', [ExpenseTypeController::class, 'store']);
            Route::patch('/expense-types/{expenseType}', [ExpenseTypeController::class, 'update']);
            Route::delete('/expense-types/{expenseType}', [ExpenseTypeController::class, 'destroy']);
        });

    });
});
