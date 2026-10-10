<?php

namespace App\Providers;

use App\Modules\Auth\Interfaces\AccessManagementServiceInterface;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\Services\AccessManagementService;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Contract\Interfaces\ContractScheduleRuleServiceInterface;
use App\Modules\Contract\Interfaces\ContractServiceInterface;
use App\Modules\Contract\Services\ContractScheduleRuleService;
use App\Modules\Contract\Services\ContractService;
use App\Modules\Dashboard\Interfaces\DashboardOverviewServiceInterface;
use App\Modules\Dashboard\Observers\DashboardModelObserver;
use App\Modules\Dashboard\Services\DashboardOverviewService;
use App\Modules\Dispatch\Interfaces\AvailabilityServiceInterface;
use App\Modules\Dispatch\Interfaces\DispatchServiceInterface;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\Dispatch\Models\TripAssignment;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\Dispatch\Services\AvailabilityService;
use App\Modules\Dispatch\Services\DispatchService;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\MasterData\Interfaces\CustomerServiceInterface;
use App\Modules\MasterData\Interfaces\DriverServiceInterface;
use App\Modules\MasterData\Interfaces\ExpenseTypeServiceInterface;
use App\Modules\MasterData\Interfaces\PartnerServiceInterface;
use App\Modules\MasterData\Interfaces\RouteRateServiceInterface;
use App\Modules\MasterData\Interfaces\RouteServiceInterface;
use App\Modules\MasterData\Interfaces\VehicleServiceInterface;
use App\Modules\MasterData\Interfaces\VehicleTypeServiceInterface;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Services\CustomerService;
use App\Modules\MasterData\Services\DriverService;
use App\Modules\MasterData\Services\ExpenseTypeService;
use App\Modules\MasterData\Services\PartnerService;
use App\Modules\MasterData\Services\RouteRateService;
use App\Modules\MasterData\Services\RouteService;
use App\Modules\MasterData\Services\VehicleService;
use App\Modules\MasterData\Services\VehicleTypeService;
use App\Modules\Rental\Interfaces\QuotationServiceInterface;
use App\Modules\Rental\Interfaces\RentalRequestServiceInterface;
use App\Modules\Rental\Services\QuotationService;
use App\Modules\Rental\Services\RentalRequestService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
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
        $this->app->singleton(RouteServiceInterface::class, RouteService::class);
        $this->app->singleton(RouteRateServiceInterface::class, RouteRateService::class);
        $this->app->singleton(VehicleTypeServiceInterface::class, VehicleTypeService::class);
        $this->app->singleton(VehicleServiceInterface::class, VehicleService::class);
        $this->app->singleton(AvailabilityServiceInterface::class, AvailabilityService::class);
        $this->app->singleton(DispatchServiceInterface::class, DispatchService::class);
        $this->app->singleton(DashboardOverviewServiceInterface::class, DashboardOverviewService::class);

        // rental
        $this->app->singleton(RentalRequestServiceInterface::class, RentalRequestService::class);
        $this->app->singleton(QuotationServiceInterface::class, QuotationService::class);
        $this->app->singleton(ContractServiceInterface::class, ContractService::class);
        $this->app->singleton(ContractScheduleRuleServiceInterface::class, ContractScheduleRuleService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config([
            'reverb.apps.apps.0.allowed_origins' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('REVERB_ALLOWED_ORIGINS', config('app.url'))),
            ))),
        ]);
        Broadcast::routes(['prefix' => 'api', 'middleware' => ['api', 'auth:api']]);
        require base_path('routes/channels.php');
        foreach ([TripSchedule::class, TripAssignment::class, DispatchOrder::class, Vehicle::class, Driver::class, Receipt::class, Expense::class, PartnerPayment::class] as $model) {
            $model::observe(DashboardModelObserver::class);
        }

        RateLimiter::for('login', static function (Request $request): Limit {
            $login = Str::lower((string) $request->input('user_name'));

            return Limit::perMinute(5)->by($login.'|'.$request->ip());
        });
        RateLimiter::for('quotation-response', static function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip().'|'.$request->route('token'));
        });
    }
}
