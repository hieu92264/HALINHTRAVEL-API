<?php

use App\Modules\DriverPayroll\Controllers\DriverAdvanceController;
use App\Modules\DriverPayroll\Controllers\DriverAttendanceController;
use App\Modules\DriverPayroll\Controllers\PayrollController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('driver-payroll')->group(function () {
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::DRIVER_ADVANCES_VIEW->value])->group(function (): void {
        Route::get('/advances', [DriverAdvanceController::class, 'index']);
        Route::get('/advances/{advance}', [DriverAdvanceController::class, 'show']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::DRIVER_ADVANCES_MANAGE->value])->group(function (): void {
        Route::post('/advances', [DriverAdvanceController::class, 'store']);
        Route::patch('/advances/{advance}', [DriverAdvanceController::class, 'update']);
        Route::delete('/advances/{advance}', [DriverAdvanceController::class, 'destroy']);
        Route::post('/advances/{advance}/confirm', [DriverAdvanceController::class, 'confirm']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::DRIVER_ATTENDANCES_VIEW->value])->group(function (): void {
        Route::get('/attendances', [DriverAttendanceController::class, 'index']);
        Route::get('/attendances/{attendance}', [DriverAttendanceController::class, 'show']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::DRIVER_ATTENDANCES_MANAGE->value])->group(function (): void {
        Route::post('/orders/{dispatchOrder}/attendance', [DriverAttendanceController::class, 'storeForOrder']);
        Route::patch('/attendances/{attendance}', [DriverAttendanceController::class, 'update']);
        Route::post('/attendances/{attendance}/confirm', [DriverAttendanceController::class, 'confirm']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PAYROLLS_VIEW->value])->group(function (): void {
        Route::get('/payrolls', [PayrollController::class, 'index']);
        Route::get('/payrolls/{payroll}', [PayrollController::class, 'show']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PAYROLLS_MANAGE->value])->group(function (): void {
        Route::post('/payrolls', [PayrollController::class, 'store']);
        Route::patch('/payrolls/{payroll}', [PayrollController::class, 'update']);
        Route::post('/payrolls/{payroll}/calculate', [PayrollController::class, 'calculate']);
        Route::patch('/payrolls/{payroll}/items/{payrollItem}', [PayrollController::class, 'updateItem']);
        Route::post('/payrolls/{payroll}/approve', [PayrollController::class, 'approve']);
        Route::post('/payrolls/{payroll}/mark-paid', [PayrollController::class, 'markPaid']);
        Route::post('/payrolls/{payroll}/lock', [PayrollController::class, 'lock']);
    });
});
