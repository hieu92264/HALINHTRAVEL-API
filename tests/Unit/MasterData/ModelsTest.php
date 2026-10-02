<?php

namespace Tests\Unit\MasterData;

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\ExpenseType;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\RouteRate;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use App\Shared\Enums\CustomerEnum;
use App\Shared\Enums\ExpenseTypeEnum;
use App\Shared\Enums\OwnershipTypeEnum;
use App\Shared\Enums\PartnerTypeEnum;
use App\Shared\Enums\VehicleStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    public function test_models_apply_expected_casts(): void
    {
        $customer = new Customer(['type' => 'company', 'opening_balance' => '1250']);
        $partner = new Partner(['type' => 'garage', 'opening_balance' => '2500']);
        $vehicleType = new VehicleType(['seats' => '16', 'tour_driver_commission_rate' => '12.5']);
        $vehicle = new Vehicle([
            'ownership_type' => 'partner',
            'vehicle_status' => 'available',
            'manufacture_year' => '2024',
            'current_odometer' => '12345',
        ]);
        $driver = new Driver([
            'type' => 'company',
            'license_issued_at' => '2020-01-15',
            'base_salary' => '10000000',
        ]);
        $route = new Route(['estimated_distance_km' => '25.5']);
        $routeRate = new RouteRate([
            'customer_price' => '1200000',
            'effective_from' => '2026-01-01',
        ]);
        $expenseType = new ExpenseType(['scope' => 'trip']);

        $this->assertSame(CustomerEnum::COMPANY, $customer->type);
        $this->assertSame('1250.00', $customer->opening_balance);
        $this->assertSame(PartnerTypeEnum::GARAGE, $partner->type);
        $this->assertSame('2500.00', $partner->opening_balance);
        $this->assertSame(16, $vehicleType->seats);
        $this->assertSame('12.50', $vehicleType->tour_driver_commission_rate);
        $this->assertSame(OwnershipTypeEnum::PARTNER, $vehicle->ownership_type);
        $this->assertSame(VehicleStatusEnum::AVAILABLE, $vehicle->vehicle_status);
        $this->assertSame(2024, $vehicle->manufacture_year);
        $this->assertSame(12345, $vehicle->current_odometer);
        $this->assertSame(OwnershipTypeEnum::COMPANY, $driver->type);
        $this->assertInstanceOf(Carbon::class, $driver->license_issued_at);
        $this->assertSame('10000000.00', $driver->base_salary);
        $this->assertSame('25.50', $route->estimated_distance_km);
        $this->assertSame('1200000.00', $routeRate->customer_price);
        $this->assertInstanceOf(Carbon::class, $routeRate->effective_from);
        $this->assertSame(ExpenseTypeEnum::TRIP, $expenseType->scope);
    }

    public function test_models_expose_internal_relationships(): void
    {
        $this->assertInstanceOf(HasMany::class, (new Customer)->routes());
        $this->assertInstanceOf(HasMany::class, (new Partner)->vehicles());
        $this->assertInstanceOf(HasMany::class, (new Partner)->drivers());
        $this->assertInstanceOf(HasMany::class, (new VehicleType)->vehicles());
        $this->assertInstanceOf(HasMany::class, (new VehicleType)->routeRates());
        $this->assertInstanceOf(BelongsTo::class, (new Vehicle)->vehicleType());
        $this->assertInstanceOf(BelongsTo::class, (new Vehicle)->partner());
        $this->assertInstanceOf(BelongsTo::class, (new Driver)->partner());
        $this->assertInstanceOf(BelongsTo::class, (new Route)->customer());
        $this->assertInstanceOf(HasMany::class, (new Route)->routeRates());
        $this->assertInstanceOf(BelongsTo::class, (new RouteRate)->route());
        $this->assertInstanceOf(BelongsTo::class, (new RouteRate)->vehicleType());
    }
}
