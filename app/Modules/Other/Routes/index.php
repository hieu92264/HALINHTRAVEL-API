<?php

use App\Modules\Other\Controllers\ReportController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('other')->group(function () {
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::DISPATCH_ORDERS_VIEW->value])->group(function (): void {
        Route::get('/reports/fixed-trips', [ReportController::class, 'fixedTrips']);
        Route::get('/reports/tourism-trips', [ReportController::class, 'tourismTrips']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::EXPENSES_VIEW->value])->get('/reports/expenses', [ReportController::class, 'expenses']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::RECEIPTS_VIEW->value])->get('/reports/customer-debts', [ReportController::class, 'customerDebts']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::RECEIPTS_VIEW->value])->get('/reports/customer-debt-details', [ReportController::class, 'customerDebtDetails']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PARTNER_PAYMENTS_VIEW->value])->get('/reports/partner-debts', [ReportController::class, 'partnerDebts']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PARTNER_PAYMENTS_VIEW->value])->get('/reports/partner-debt-details', [ReportController::class, 'partnerDebtDetails']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::EXPENSES_VIEW->value])->get('/reports/external-driver-debts', [ReportController::class, 'externalDriverDebts']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PAYROLLS_VIEW->value])->get('/reports/driver-payroll', [ReportController::class, 'payroll']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PAYROLLS_VIEW->value])->get('/reports/driver-payroll-details', [ReportController::class, 'payrollDetails']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::RECEIPTS_VIEW->value, 'permission:'.PermissionEnum::EXPENSES_VIEW->value, 'permission:'.PermissionEnum::PARTNER_PAYMENTS_VIEW->value, 'permission:'.PermissionEnum::DRIVER_ADVANCES_VIEW->value])->get('/reports/cashflow', [ReportController::class, 'cashflow']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::DISPATCH_ORDERS_VIEW->value, 'permission:'.PermissionEnum::EXPENSES_VIEW->value, 'permission:'.PermissionEnum::PARTNER_PAYMENTS_VIEW->value, 'permission:'.PermissionEnum::PAYROLLS_VIEW->value])->get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::DISPATCH_ORDERS_VIEW->value, 'permission:'.PermissionEnum::EXPENSES_VIEW->value, 'permission:'.PermissionEnum::PARTNER_PAYMENTS_VIEW->value, 'permission:'.PermissionEnum::PAYROLLS_VIEW->value])->get('/reports/vehicle-profit', [ReportController::class, 'vehicleProfit']);
});
