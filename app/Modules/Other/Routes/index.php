<?php

use App\Modules\Other\Controllers\DebtReportController;
use App\Modules\Other\Controllers\OperationalReportController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('other')->group(function () {
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
