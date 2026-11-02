<?php

use App\Modules\Other\Controllers\DebtReportController;
use App\Modules\Other\Controllers\OperationalReportController;
use App\Modules\Other\Controllers\RestoredReportController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('other')->group(function () {
    // Finance keeps its direct-array debt endpoints below.  The routes in this
    // block are compatibility endpoints for the restored report workspace.
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::WORKER_VEHICLES_VIEW->value])->get('/reports/business-trips', [RestoredReportController::class, 'businessTrips']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::TOURIST_VEHICLES_VIEW->value])->get('/reports/tourism-trips', [RestoredReportController::class, 'tourismTrips']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::RECEIPTS_VIEW->value])->group(function (): void {
        Route::get('/reports/customer-debt-summary', [RestoredReportController::class, 'customerDebts']);
        Route::get('/reports/customer-debt-details', [RestoredReportController::class, 'customerDebtDetails']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PARTNER_PAYMENTS_VIEW->value])->group(function (): void {
        Route::get('/reports/partner-debt-summary', [RestoredReportController::class, 'partnerDebts']);
        Route::get('/reports/partner-debt-details', [RestoredReportController::class, 'partnerDebtDetails']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PAYROLLS_VIEW->value])->group(function (): void {
        Route::get('/reports/driver-payroll', [RestoredReportController::class, 'driverPayroll']);
        Route::get('/reports/driver-payroll-details', [RestoredReportController::class, 'driverPayrollDetails']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::WORKER_VEHICLES_VIEW->value, 'permission:'.PermissionEnum::TOURIST_VEHICLES_VIEW->value, 'permission:'.PermissionEnum::EXPENSES_VIEW->value, 'permission:'.PermissionEnum::PARTNER_PAYMENTS_VIEW->value, 'permission:'.PermissionEnum::PAYROLLS_VIEW->value])->get('/reports/vehicle-profit', [RestoredReportController::class, 'vehicleProfit']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::RECEIPTS_VIEW->value])->get('/reports/customer-debts', [DebtReportController::class, 'customerDebts']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PARTNER_PAYMENTS_VIEW->value])->get('/reports/partner-debts', [DebtReportController::class, 'partnerDebts']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::EXPENSES_VIEW->value])->get('/reports/external-driver-debts', [DebtReportController::class, 'externalDriverDebts']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PAYROLLS_VIEW->value])->group(function (): void {
        Route::get('/reports/worker-vehicles', [OperationalReportController::class, 'workerVehicles']);
        Route::get('/reports/tourist-vehicles', [OperationalReportController::class, 'touristVehicles']);
        Route::get('/reports/profit', [OperationalReportController::class, 'profit']);
        Route::get('/reports/payroll-sources', [OperationalReportController::class, 'payrollSources']);
    });
});
