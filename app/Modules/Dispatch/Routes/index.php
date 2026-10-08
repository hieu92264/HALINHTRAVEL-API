<?php

use App\Modules\Dispatch\Controllers\AvailabilityController;
use App\Modules\Dispatch\Controllers\DispatchOrderController;
use App\Modules\Dispatch\Controllers\TripAssignmentController;
use App\Modules\Dispatch\Controllers\TripScheduleController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('dispatch')->group(function () {
    Route::post('/availability', AvailabilityController::class)
        ->middleware(['auth:api', 'permission:'.PermissionEnum::RENTAL_CAPACITY_VIEW->value]);

    Route::middleware(['auth:api', 'permission:'.PermissionEnum::TRIP_SCHEDULES_VIEW->value])->group(function () {
        Route::get('/trip-schedules', [TripScheduleController::class, 'index']);
        Route::get('/trip-schedules/{tripSchedule}', [TripScheduleController::class, 'show']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::TRIP_ASSIGNMENTS_VIEW->value])->group(function () {
        Route::get('/trip-schedules/{tripSchedule}/assignments', [TripAssignmentController::class, 'index']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::DISPATCH_ORDERS_VIEW->value])->group(function () {
        Route::get('/orders', [DispatchOrderController::class, 'index']);
        Route::get('/orders/{dispatchOrder}', [DispatchOrderController::class, 'show']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::TRIP_SCHEDULES_MANAGE->value])->group(function () {
        Route::post('/trip-schedules', [TripScheduleController::class, 'store']);
        Route::patch('/trip-schedules/{tripSchedule}', [TripScheduleController::class, 'update']);
        Route::delete('/trip-schedules/{tripSchedule}', [TripScheduleController::class, 'destroy']);
        Route::post('/trip-schedules/{tripSchedule}/cancel', [TripScheduleController::class, 'cancel']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::TRIP_ASSIGNMENTS_MANAGE->value])->group(function () {
        Route::post('/trip-schedules/{tripSchedule}/assignments', [TripAssignmentController::class, 'store']);
        Route::post('/trip-schedules/{tripSchedule}/assignments/substitute', [TripAssignmentController::class, 'substitute']);
        Route::delete('/assignments/{tripAssignment}', [TripAssignmentController::class, 'destroy']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::DISPATCH_ORDERS_MANAGE->value])->group(function () {
        Route::post('/trip-schedules/{tripSchedule}/orders', [DispatchOrderController::class, 'issue']);
        Route::post('/orders/{dispatchOrder}/assign', [DispatchOrderController::class, 'assign']);
        Route::post('/orders/{dispatchOrder}/start', [DispatchOrderController::class, 'start']);
        Route::post('/orders/{dispatchOrder}/report-completion', [DispatchOrderController::class, 'report']);
        Route::post('/orders/{dispatchOrder}/confirm-completion', [DispatchOrderController::class, 'confirm']);
        Route::post('/orders/{dispatchOrder}/return-completion', [DispatchOrderController::class, 'returnCompletion']);
        Route::post('/orders/{dispatchOrder}/cancel', [DispatchOrderController::class, 'cancel']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::MY_DISPATCH_ORDERS_VIEW->value])->group(function () {
        Route::get('/my-orders', [DispatchOrderController::class, 'myIndex']);
        Route::get('/my-orders/{dispatchOrder}', [DispatchOrderController::class, 'myShow']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::MY_DISPATCH_ORDERS_MANAGE->value])->group(function () {
        Route::post('/my-orders/{dispatchOrder}/start', [DispatchOrderController::class, 'myStart']);
        Route::post('/my-orders/{dispatchOrder}/report-completion', [DispatchOrderController::class, 'myReport']);
    });
});
