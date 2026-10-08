<?php

use App\Modules\Dashboard\Controllers\DashboardOverviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('dashboard')->group(function (): void {
    Route::get('/overview', DashboardOverviewController::class);
});
