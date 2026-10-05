<?php

use App\Modules\Dispatch\Controllers\AvailabilityController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('dispatch')->group(function () {
    Route::post('/availability', AvailabilityController::class)
        ->middleware(['auth:api', 'permission:'.PermissionEnum::RENTAL_CAPACITY_VIEW->value]);
});
