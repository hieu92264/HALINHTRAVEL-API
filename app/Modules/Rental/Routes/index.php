<?php

use App\Modules\Rental\Controllers\QuotationController;
use App\Modules\Rental\Controllers\RentalRequestController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('rental')->group(function () {
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::RENTAL_REQUESTS_VIEW->value])->group(function () {
        Route::get('/requests', [RentalRequestController::class, 'index']);
        Route::get('/requests/{rentalRequest}', [RentalRequestController::class, 'show']);
    });

    Route::middleware(['auth:api', 'permission:'.PermissionEnum::RENTAL_REQUESTS_MANAGE->value])->group(function () {
        Route::post('/requests', [RentalRequestController::class, 'store']);
        Route::patch('/requests/{rentalRequest}', [RentalRequestController::class, 'update']);
        Route::delete('/requests/{rentalRequest}', [RentalRequestController::class, 'destroy']);
        Route::post('/requests/{rentalRequest}/mark-quoted', [RentalRequestController::class, 'markQuoted']);
        Route::post('/requests/{rentalRequest}/accept', [RentalRequestController::class, 'accept']);
        Route::post('/requests/{rentalRequest}/reject', [RentalRequestController::class, 'reject']);
    });

    Route::middleware(['auth:api', 'permission:'.PermissionEnum::QUOTATIONS_VIEW->value])->group(function () {
        Route::get('/quotations', [QuotationController::class, 'index']);
        Route::get('/quotations/{quotation}', [QuotationController::class, 'show']);
    });

    Route::middleware(['auth:api', 'permission:'.PermissionEnum::QUOTATIONS_MANAGE->value])->group(function () {
        Route::post('/quotations', [QuotationController::class, 'store']);
        Route::patch('/quotations/{quotation}', [QuotationController::class, 'update']);
        Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy']);
        Route::post('/quotations/{quotation}/send', [QuotationController::class, 'send']);
        Route::post('/quotations/{quotation}/expire', [QuotationController::class, 'expire']);
    });
});

Route::prefix('public/quotation-responses')->middleware('throttle:quotation-response')->group(function () {
    Route::get('/{token}', [QuotationController::class, 'response']);
    Route::post('/{token}/accept', [QuotationController::class, 'acceptResponse']);
    Route::post('/{token}/reject', [QuotationController::class, 'rejectResponse']);
});
