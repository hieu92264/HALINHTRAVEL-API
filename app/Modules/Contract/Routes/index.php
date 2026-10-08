<?php

use App\Modules\Contract\Controllers\ContractController;
use App\Modules\Contract\Controllers\ContractScheduleRuleController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('contract')->group(function () {
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::CONTRACTS_VIEW->value])->group(function () {
        Route::get('/contracts', [ContractController::class, 'index']);
        Route::get('/contracts/{contract}', [ContractController::class, 'show']);
        Route::get('/contracts/{contract}/schedule-rules', [ContractScheduleRuleController::class, 'index']);
        Route::get('/schedule-rules/{scheduleRule}', [ContractScheduleRuleController::class, 'show']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::CONTRACTS_MANAGE->value])->group(function () {
        Route::post('/contracts', [ContractController::class, 'store']);
        Route::post('/contracts/from-quotation', [ContractController::class, 'fromQuotation']);
        Route::post('/contracts/{contract}/schedule-rules', [ContractScheduleRuleController::class, 'store']);
        Route::patch('/contracts/{contract}', [ContractController::class, 'update']);
        Route::patch('/schedule-rules/{scheduleRule}', [ContractScheduleRuleController::class, 'update']);
        Route::put('/schedule-rules/{scheduleRule}/days', [ContractScheduleRuleController::class, 'replaceDays']);
        Route::post('/schedule-rules/{scheduleRule}/generate-trip-schedules', [ContractScheduleRuleController::class, 'generateTripSchedules']);
        Route::delete('/schedule-rules/{scheduleRule}', [ContractScheduleRuleController::class, 'destroy']);
        Route::delete('/contracts/{contract}', [ContractController::class, 'destroy']);
        Route::post('/contracts/{contract}/activate', [ContractController::class, 'activate']);
        Route::post('/contracts/{contract}/complete', [ContractController::class, 'complete']);
        Route::post('/contracts/{contract}/cancel', [ContractController::class, 'cancel']);
    });
});
