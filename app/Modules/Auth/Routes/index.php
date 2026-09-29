<?php

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\PermissionController;
use App\Modules\Auth\Controllers\RoleController;
use App\Modules\Auth\Controllers\UserController;
use App\Shared\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/login', 'login')->middleware('throttle:login');

        Route::middleware('auth:api')->group(function () {
            Route::post('/refresh', 'refresh');
            Route::get('/me', 'me');
            Route::post('/logout', 'logout');
        });
    });

    Route::middleware('auth:api')->group(function () {
        Route::middleware('permission:'.PermissionEnum::USERS_VIEW->value)->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{user}', [UserController::class, 'show']);
        });
        Route::middleware('permission:'.PermissionEnum::USERS_MANAGE->value)->group(function () {
            Route::post('/users', [UserController::class, 'store']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
            Route::put('/users/{user}/roles', [UserController::class, 'syncRoles']);
            Route::put('/users/{user}/permissions', [UserController::class, 'syncPermissions']);
        });

        Route::middleware('permission:'.PermissionEnum::ROLES_MANAGE->value)->group(function () {
            Route::get('/roles', [RoleController::class, 'index']);
            Route::post('/roles', [RoleController::class, 'store']);
            Route::get('/roles/{role}', [RoleController::class, 'show']);
            Route::put('/roles/{role}', [RoleController::class, 'update']);
            Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
            Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
        });

        Route::middleware('permission:'.PermissionEnum::PERMISSIONS_VIEW->value)->group(function () {
            Route::get('/permissions', [PermissionController::class, 'index']);
            Route::get('/permissions/{permission}', [PermissionController::class, 'show']);
        });
        Route::middleware('permission:'.PermissionEnum::PERMISSIONS_MANAGE->value)->group(function () {
            Route::post('/permissions', [PermissionController::class, 'store']);
            Route::put('/permissions/{permission}', [PermissionController::class, 'update']);
            Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']);
        });
    });
});
