<?php

namespace Tests\Feature\VehicleOperations;

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Route;
use App\Modules\VehicleOperations\Services\VehicleOperationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VehicleOperationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_operations_schema_and_rate_period_validation_are_available(): void
    {
        foreach (['worker_vehicles', 'tourist_vehicles', 'worker_vehicle_schedule_rules', 'worker_vehicle_schedule_rule_days', 'worker_vehicle_rates'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $customer = Customer::query()->create(['code' => 'KH-OPS', 'type' => 'company', 'name' => 'Khách hàng test']);
        $route = Route::query()->create(['code' => 'T-OPS', 'customer_id' => $customer->id, 'name' => 'Tuyến test', 'pickup_location' => 'A', 'dropoff_location' => 'B']);
        $service = app(VehicleOperationService::class);
        $service->saveRate(['route_id' => $route->id, 'license_plate' => '15B-000.01', 'freight_amount' => 1000000, 'driver_wage' => 100000, 'effective_from' => '2026-01-01']);

        $this->expectException(ValidationException::class);
        $service->saveRate(['route_id' => $route->id, 'license_plate' => '15B-000.01', 'freight_amount' => 1200000, 'driver_wage' => 120000, 'effective_from' => '2026-01-15']);
    }

    public function test_worker_and_tourist_runs_cannot_overlap_on_a_plate(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-CONFLICT', 'type' => 'company', 'name' => 'Khách hàng test']);
        $route = Route::query()->create(['code' => 'T-CONFLICT', 'customer_id' => $customer->id, 'name' => 'Tuyến test', 'pickup_location' => 'A', 'dropoff_location' => 'B']);
        $service = app(VehicleOperationService::class);
        $service->createRun('tourist', ['journey' => 'Tour test', 'run_date' => '2026-02-01', 'pickup_time' => '08:00', 'return_time' => '12:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-000.02', 'is_hired_driver' => false]);

        $this->expectException(ValidationException::class);
        $service->createRun('worker', ['customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'run_date' => '2026-02-01', 'pickup_time' => '09:00', 'return_time' => '11:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-000.02', 'is_hired_driver' => false]);
    }

    public function test_default_seed_uses_only_new_vehicle_operation_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('worker_vehicles', 1);
        $this->assertDatabaseCount('tourist_vehicles', 1);
        $this->assertFalse(Schema::hasTable('vehicles'));
        $this->assertFalse(Schema::hasTable('dispatch_orders'));
    }
}
