<?php

use App\Modules\Other\Controllers\DebtReportController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('other')->group(function () {
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::RECEIPTS_VIEW->value])->get('/reports/customer-debts', [DebtReportController::class, 'customerDebts']);
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PARTNER_PAYMENTS_VIEW->value])->get('/reports/partner-debts', [DebtReportController::class, 'partnerDebts']);
});
