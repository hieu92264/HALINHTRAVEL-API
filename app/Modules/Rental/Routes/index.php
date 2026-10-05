<?php

use App\Modules\Rental\Controllers\RentalRequestController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('rental')->group(function () {
    Route::middleware(['auth:api', 'permission:'.PermissionEnum::RENTAL_REQUESTS_MANAGE->value])->group(function () {
        Route::post('/requests', [RentalRequestController::class, 'store']);
        Route::patch('/requests/{rentalRequest}', [RentalRequestController::class, 'update']);
    });
});
