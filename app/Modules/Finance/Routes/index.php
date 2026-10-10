<?php

use App\Modules\Finance\Controllers\ExpenseController;
use App\Modules\Finance\Controllers\PartnerPaymentController;
use App\Modules\Finance\Controllers\ReceiptController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('finance')->group(function () {
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::RECEIPTS_VIEW->value])->group(function (): void {
        Route::get('/receipts', [ReceiptController::class, 'index']);
        Route::get('/receipts/{receipt}', [ReceiptController::class, 'show']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::RECEIPTS_MANAGE->value])->group(function (): void {
        Route::post('/receipts', [ReceiptController::class, 'store']);
        Route::patch('/receipts/{receipt}', [ReceiptController::class, 'update']);
        Route::delete('/receipts/{receipt}', [ReceiptController::class, 'destroy']);
        Route::post('/receipts/{receipt}/lock', [ReceiptController::class, 'lock']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::EXPENSES_VIEW->value])->group(function (): void {
        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::get('/expenses/{expense}', [ExpenseController::class, 'show']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::EXPENSES_MANAGE->value])->group(function (): void {
        Route::post('/expenses', [ExpenseController::class, 'store']);
        Route::patch('/expenses/{expense}', [ExpenseController::class, 'update']);
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);
        Route::post('/expenses/{expense}/lock', [ExpenseController::class, 'lock']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PARTNER_PAYMENTS_VIEW->value])->group(function (): void {
        Route::get('/partner-payments', [PartnerPaymentController::class, 'index']);
        Route::get('/partner-payments/{partnerPayment}', [PartnerPaymentController::class, 'show']);
    });
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::PARTNER_PAYMENTS_MANAGE->value])->group(function (): void {
        Route::post('/partner-payments', [PartnerPaymentController::class, 'store']);
        Route::patch('/partner-payments/{partnerPayment}', [PartnerPaymentController::class, 'update']);
        Route::delete('/partner-payments/{partnerPayment}', [PartnerPaymentController::class, 'destroy']);
        Route::post('/partner-payments/{partnerPayment}/lock', [PartnerPaymentController::class, 'lock']);
    });
});
