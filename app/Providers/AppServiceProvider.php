<?php

namespace App\Providers;

use App\Modules\Auth\Interfaces\AccessManagementServiceInterface;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\Services\AccessManagementService;
use App\Modules\Auth\Services\AuthService;
use App\Modules\MasterData\Interfaces\CustomerServiceInterface;
use App\Modules\MasterData\Interfaces\DriverServiceInterface;
use App\Modules\MasterData\Interfaces\ExpenseTypeServiceInterface;
use App\Modules\MasterData\Interfaces\PartnerServiceInterface;
use App\Modules\MasterData\Interfaces\RouteRateServiceInterface;
use App\Modules\MasterData\Interfaces\VehicleServiceInterface;
use App\Modules\MasterData\Interfaces\VehicleTypeServiceInterface;
use App\Modules\MasterData\Services\CustomerService;
use App\Modules\MasterData\Services\DriverService;
use App\Modules\MasterData\Services\ExpenseTypeService;
use App\Modules\MasterData\Services\PartnerService;
use App\Modules\MasterData\Services\RouteRateService;
use App\Modules\MasterData\Services\VehicleService;
use App\Modules\MasterData\Services\VehicleTypeService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
        $this->app->singleton(AccessManagementServiceInterface::class, AccessManagementService::class);
        $this->app->singleton(CustomerServiceInterface::class, CustomerService::class);
        $this->app->singleton(DriverServiceInterface::class, DriverService::class);
        $this->app->singleton(ExpenseTypeServiceInterface::class, ExpenseTypeService::class);
        $this->app->singleton(PartnerServiceInterface::class, PartnerService::class);
        $this->app->singleton(RouteRateServiceInterface::class, RouteRateService::class);
        $this->app->singleton(VehicleTypeServiceInterface::class, VehicleTypeService::class);
        $this->app->singleton(VehicleServiceInterface::class, VehicleService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', static function (Request $request): Limit {
            $login = Str::lower((string) $request->input('user_name'));

            return Limit::perMinute(5)->by($login.'|'.$request->ip());
        });
    }
}
