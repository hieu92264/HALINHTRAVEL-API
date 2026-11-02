<?php

use App\Modules\VehicleOperations\Controllers\VehicleOperationController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('vehicle-operations')->controller(VehicleOperationController::class)->group(function (): void {
    Route::middleware('permission:'.PermissionEnum::WORKER_VEHICLES_VIEW->value)->get('worker-vehicles', 'workerIndex');
    Route::middleware('permission:'.PermissionEnum::WORKER_VEHICLES_VIEW->value)->get('worker-vehicles/{workerVehicle}', 'workerShow');
    Route::middleware('permission:'.PermissionEnum::WORKER_VEHICLES_MANAGE->value)->group(function (): void {
        Route::post('worker-vehicles', 'workerStore');
        Route::patch('worker-vehicles/{workerVehicle}', 'workerUpdate');
        Route::delete('worker-vehicles/{workerVehicle}', 'workerDestroy');
        Route::post('worker-vehicles/{workerVehicle}/start', 'workerStart');
        Route::post('worker-vehicles/{workerVehicle}/complete', 'workerComplete');
    });
    Route::middleware('permission:'.PermissionEnum::TOURIST_VEHICLES_VIEW->value)->get('tourist-vehicles', 'touristIndex');
    Route::middleware('permission:'.PermissionEnum::TOURIST_VEHICLES_VIEW->value)->get('tourist-vehicles/{touristVehicle}', 'touristShow');
    Route::middleware('permission:'.PermissionEnum::TOURIST_VEHICLES_MANAGE->value)->group(function (): void {
        Route::post('tourist-vehicles', 'touristStore');
        Route::patch('tourist-vehicles/{touristVehicle}', 'touristUpdate');
        Route::delete('tourist-vehicles/{touristVehicle}', 'touristDestroy');
        Route::post('tourist-vehicles/{touristVehicle}/start', 'touristStart');
        Route::post('tourist-vehicles/{touristVehicle}/complete', 'touristComplete');
    });
    Route::middleware('permission:'.PermissionEnum::WORKER_VEHICLE_RATES_VIEW->value)->get('worker-vehicle-rates', 'rateIndex');
    Route::middleware('permission:'.PermissionEnum::WORKER_VEHICLE_RATES_MANAGE->value)->group(function (): void {
        Route::post('worker-vehicle-rates', 'rateStore');
        Route::patch('worker-vehicle-rates/{workerVehicleRate}', 'rateUpdate');
    });
    Route::middleware('permission:'.PermissionEnum::WORKER_VEHICLE_SCHEDULES_MANAGE->value)->group(function (): void {
        Route::get('worker-vehicle-schedule-rules', 'ruleIndex');
        Route::post('worker-vehicle-schedule-rules', 'ruleStore');
        Route::patch('worker-vehicle-schedule-rules/{workerVehicleScheduleRule}', 'ruleUpdate');
        Route::delete('worker-vehicle-schedule-rules/{workerVehicleScheduleRule}', 'ruleDestroy');
        Route::post('worker-vehicle-schedule-rules/{workerVehicleScheduleRule}/generate', 'generate');
    });
});
