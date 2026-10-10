<?php

use App\Modules\Dispatch\Controllers\AvailabilityController;
use App\Modules\Dispatch\Controllers\DispatchController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('dispatch')->group(function () {
    Route::post('/availability', AvailabilityController::class)
        ->middleware(['auth:api', 'permission:'.PermissionEnum::RENTAL_CAPACITY_VIEW->value]);

    Route::middleware(['auth:api', 'permission:'.PermissionEnum::TRIP_SCHEDULES_VIEW->value])->group(function () {
        Route::get('/trip-schedules', [DispatchController::class, 'schedules']);
        Route::get('/trip-schedules/{tripSchedule}', [DispatchController::class, 'schedule']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::TRIP_SCHEDULES_MANAGE->value])->group(function () {
        Route::post('/trip-schedules', [DispatchController::class, 'storeSchedule']);
        Route::patch('/trip-schedules/{tripSchedule}', [DispatchController::class, 'updateSchedule']);
        Route::delete('/trip-schedules/{tripSchedule}', [DispatchController::class, 'cancelSchedule']);
        Route::post('/trip-schedules/{tripSchedule}/cancel', [DispatchController::class, 'cancelSchedule']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::TRIP_ASSIGNMENTS_VIEW->value])->group(function () {
        Route::get('/trip-schedules/{tripSchedule}/assignments', [DispatchController::class, 'assignments']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::TRIP_ASSIGNMENTS_MANAGE->value])->group(function () {
        Route::post('/trip-schedules/{tripSchedule}/assignments', [DispatchController::class, 'assign']);
        Route::post('/trip-schedules/{tripSchedule}/assignments/substitute', [DispatchController::class, 'substitute']);
        Route::delete('/trip-assignments/{assignment}', [DispatchController::class, 'removeAssignment']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::DISPATCH_ORDERS_VIEW->value])->group(function () {
        Route::get('/dispatch-orders', [DispatchController::class, 'orders']);
        Route::get('/dispatch-orders/{dispatchOrder}', [DispatchController::class, 'order']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::DISPATCH_ORDERS_MANAGE->value])->group(function () {
        Route::post('/trip-schedules/{tripSchedule}/dispatch-order', [DispatchController::class, 'createOrder']);
        Route::post('/dispatch-orders/{dispatchOrder}/assign', [DispatchController::class, 'assignOrder']);
        Route::post('/dispatch-orders/{dispatchOrder}/cancel', [DispatchController::class, 'cancelOrder']);
    });
    Route::middleware(['auth:api'])->group(function () {
        Route::post('/dispatch-orders/{dispatchOrder}/start', [DispatchController::class, 'start']);
        Route::post('/dispatch-orders/{dispatchOrder}/complete', [DispatchController::class, 'complete']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::DRIVER_ORDERS_VIEW->value])->group(function () {
        Route::get('/my-orders', [DispatchController::class, 'myOrders']);
        Route::get('/my-orders/{dispatchOrder}', [DispatchController::class, 'myOrder']);
    });
});
