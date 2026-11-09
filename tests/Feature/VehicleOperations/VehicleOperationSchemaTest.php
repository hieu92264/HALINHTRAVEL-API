<?php

namespace Tests\Feature\VehicleOperations;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\DriverPayroll\Models\DriverAttendance;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\RouteVehicleTypeRate;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\Other\Services\OperationalReportService;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicleScheduleRule;
use App\Modules\VehicleOperations\Services\VehicleOperationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
        $service->createRun('tourist', ['customer_id' => $customer->id, 'journey' => 'Tour test', 'run_date' => '2026-02-01', 'pickup_time' => '08:00', 'return_time' => '12:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-000.02', 'is_hired_driver' => false]);

        $this->expectException(ValidationException::class);
        $service->createRun('worker', ['customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'run_date' => '2026-02-01', 'pickup_time' => '09:00', 'return_time' => '11:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-000.02', 'is_hired_driver' => false]);
    }

    public function test_selected_vehicle_is_snapshotted_for_new_run(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-VEHICLE-SNAPSHOT', 'type' => 'company', 'name' => 'Khách hàng test']);
        $route = Route::query()->create(['code' => 'T-VEHICLE-SNAPSHOT', 'customer_id' => $customer->id, 'name' => 'Tuyến test', 'pickup_location' => 'A', 'dropoff_location' => 'B']);
        $vehicle = Vehicle::query()->create(['code' => 'VX-SNAPSHOT', 'license_plate' => '15B-888.88', 'seat_count' => 29, 'ownership_type' => 'company']);

        $run = app(VehicleOperationService::class)->createRun('worker', [
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'route_id' => $route->id,
            'shift_name' => 'Ca sáng',
            'run_date' => '2026-02-01',
            'pickup_time' => '08:00',
            'return_time' => '12:00',
            'pickup_location' => 'A',
            'dropoff_location' => 'B',
            'is_hired_driver' => false,
        ]);

        $this->assertSame($vehicle->id, $run['vehicle_id']);
        $this->assertSame('15B-888.88', $run['license_plate']);
        $this->assertTrue($run['is_company_vehicle']);
    }

    public function test_editing_a_generated_run_accepts_legacy_seconds_and_preserves_its_schedule_rule(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-RUN-EDIT', 'type' => 'company', 'name' => 'Khách hàng test']);
        $route = Route::query()->create(['code' => 'T-RUN-EDIT', 'customer_id' => $customer->id, 'name' => 'Tuyến test', 'pickup_location' => 'A', 'dropoff_location' => 'B']);
        $rule = WorkerVehicleScheduleRule::query()->create(['customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'effective_from' => '2026-01-01', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-999.01', 'is_hired_driver' => false]);
        $run = WorkerVehicle::query()->create(['run_no' => 'CN-EDIT-001', 'schedule_rule_id' => $rule->id, 'customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'run_date' => '2026-11-09', 'pickup_time' => '06:30:00', 'return_time' => '17:30:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-999.01', 'is_hired_driver' => false, 'hired_driver_cost' => 0, 'partner_vehicle_cost' => 0, 'freight_amount' => 0, 'driver_wage' => 0, 'status' => 'planned']);
        $service = app(VehicleOperationService::class);

        $payload = $service->workerRun($run);
        $this->assertSame('06:30', $payload['pickup_time']);
        $this->assertSame('17:30', $payload['return_time']);
        $updated = $service->updateRun('worker', $run, [...$payload, 'schedule_rule_id' => null, 'hired_driver_cost' => null, 'note' => 'Đã cập nhật ghi chú']);

        $this->assertSame('Đã cập nhật ghi chú', $updated['note']);
        $this->assertSame($rule->id, $updated['schedule_rule_id']);
        $this->assertDatabaseHas('worker_vehicles', ['id' => $run->id, 'schedule_rule_id' => $rule->id, 'hired_driver_cost' => 0, 'note' => 'Đã cập nhật ghi chú']);
    }

    public function test_legacy_worker_schedule_and_rate_endpoints_are_not_registered(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $dispatcher = User::query()->where('user_name', 'dispatcher')->firstOrFail();
        $this->actingAs($dispatcher, 'api')->getJson('/api/vehicle-operations/worker-vehicle-rates')
            ->assertNotFound();
        $this->actingAs($dispatcher, 'api')->getJson('/api/vehicle-operations/worker-vehicle-schedule-rules')
            ->assertNotFound();
    }

    public function test_default_seed_uses_only_new_vehicle_operation_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('worker_vehicles', 5);
        $this->assertDatabaseCount('tourist_vehicles', 5);
        $this->assertTrue(Schema::hasTable('vehicles'));
        $this->assertFalse(Schema::hasTable('dispatch_orders'));
    }

    public function test_completing_worker_run_creates_at_most_one_pending_attendance(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-ATTENDANCE', 'type' => 'company', 'name' => 'Khách hàng test']);
        $route = Route::query()->create(['code' => 'T-ATTENDANCE', 'customer_id' => $customer->id, 'name' => 'Tuyến test', 'pickup_location' => 'A', 'dropoff_location' => 'B']);
        $driver = Driver::query()->create(['code' => 'LX-ATTENDANCE', 'type' => 'company', 'full_name' => 'Lái xe test', 'license_number' => 'B2-ATTENDANCE', 'license_class' => 'B2']);
        $service = app(VehicleOperationService::class);
        $run = $service->createRun('worker', ['customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'run_date' => '2026-02-02', 'pickup_time' => '08:00', 'return_time' => '12:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-000.03', 'driver_id' => $driver->id, 'is_hired_driver' => false, 'driver_wage' => 250000]);
        $model = WorkerVehicle::query()->findOrFail($run['id']);

        $service->completeWorkerRun($model);
        $service->completeWorkerRun($model);

        $this->assertDatabaseCount('driver_attendances', 1);
        $this->assertSame('pending', DriverAttendance::query()->sole()->status->value);
    }

    public function test_tourist_run_snapshots_wage_and_creates_one_attendance(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-TOUR', 'type' => 'company', 'name' => 'Khách tour']);
        $driver = Driver::query()->create(['code' => 'LX-TOUR', 'type' => 'company', 'full_name' => 'Lái xe tour', 'license_number' => 'B2-TOUR', 'license_class' => 'B2', 'tourism_commission_rate' => 0.1]);
        $service = app(VehicleOperationService::class);
        $run = $service->createRun('tourist', ['customer_id' => $customer->id, 'journey' => 'Hải Phòng - Hạ Long', 'run_date' => '2026-02-04', 'pickup_time' => '08:00', 'return_time' => '17:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-000.04', 'driver_id' => $driver->id, 'is_hired_driver' => false, 'freight_amount' => 5000000]);

        $this->assertSame('0.1000', $run['driver_wage_rate']);
        $this->assertSame('500000.00', $run['driver_wage']);
        $model = TouristVehicle::query()->findOrFail($run['id']);
        $service->startTouristRun($model);
        $service->completeTouristRun($model);
        $service->completeTouristRun($model);

        $this->assertDatabaseCount('driver_attendances', 1);
        $this->assertSame('tourism_trip', DriverAttendance::query()->sole()->work_type->value);
    }

    public function test_operational_reports_use_completed_worker_and_tourist_runs_directly(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-REPORT', 'type' => 'company', 'name' => 'Khách báo cáo']);
        $route = Route::query()->create(['code' => 'T-REPORT', 'customer_id' => $customer->id, 'name' => 'Tuyến báo cáo', 'pickup_location' => 'A', 'dropoff_location' => 'B']);
        WorkerVehicle::query()->create(['run_no' => 'CN-RP-001', 'customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'run_date' => '2026-11-01', 'pickup_time' => '07:00', 'return_time' => '16:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-100.01', 'is_hired_driver' => true, 'hired_driver_name' => 'Lái thuê A', 'hired_driver_cost' => 300000, 'freight_amount' => 1500000, 'driver_wage' => 0, 'status' => 'completed', 'actual_completed_at' => '2026-11-01 16:00:00']);
        TouristVehicle::query()->create(['run_no' => 'DL-RP-001', 'customer_id' => $customer->id, 'journey' => 'Hải Phòng - Hạ Long', 'run_date' => '2026-11-01', 'pickup_time' => '08:00', 'return_time' => '18:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-100.02', 'is_hired_driver' => false, 'freight_amount' => 5000000, 'driver_wage' => 500000, 'status' => 'completed', 'actual_completed_at' => '2026-11-01 18:00:00']);

        $service = app(OperationalReportService::class);
        $worker = $service->workerVehicles(['from_date' => '2026-11-01', 'to_date' => '2026-11-01']);
        $tourist = $service->touristVehicles(['from_date' => '2026-11-01', 'to_date' => '2026-11-01']);
        $profit = $service->profit(['from_date' => '2026-11-01', 'to_date' => '2026-11-01']);

        $this->assertSame('1500000.00', $worker['summary']['freight_amount']);
        $this->assertSame('5000000.00', $tourist['summary']['freight_amount']);
        $this->assertSame('6500000.00', $profit['summary']['total_revenue']);
        $this->assertSame('300000.00', $profit['hired_drivers'][0]['incurred_amount']);
    }

    public function test_run_conflict_also_blocks_an_internal_driver_on_a_different_plate(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-DRIVER-CONFLICT', 'type' => 'company', 'name' => 'Khách hàng test']);
        $route = Route::query()->create(['code' => 'T-DRIVER-CONFLICT', 'customer_id' => $customer->id, 'name' => 'Tuyến test', 'pickup_location' => 'A', 'dropoff_location' => 'B']);
        $driver = Driver::query()->create(['code' => 'LX-CONFLICT', 'type' => 'company', 'full_name' => 'Lái xe xung đột', 'license_number' => 'D-CONFLICT', 'license_class' => 'D']);
        $service = app(VehicleOperationService::class);
        $service->createRun('tourist', ['customer_id' => $customer->id, 'journey' => 'Tour test', 'run_date' => '2026-04-01', 'pickup_time' => '08:00', 'return_time' => '12:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-100.11', 'driver_id' => $driver->id, 'is_hired_driver' => false]);

        $this->expectException(ValidationException::class);
        $service->createRun('worker', ['customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'run_date' => '2026-04-01', 'pickup_time' => '09:00', 'return_time' => '11:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-100.12', 'driver_id' => $driver->id, 'is_hired_driver' => false]);
    }

    public function test_partner_and_hired_tourist_runs_do_not_create_company_driver_attendance(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-TOUR-MODES', 'type' => 'company', 'name' => 'Khách tour']);
        $partner = Partner::query()->create(['code' => 'DT-TOUR-MODES', 'type' => 'transport_company', 'name' => 'Đối tác tour']);
        $service = app(VehicleOperationService::class);
        $partnerRun = $service->createRun('tourist', ['customer_id' => $customer->id, 'journey' => 'Tour đối tác', 'run_date' => '2026-05-01', 'pickup_time' => '08:00', 'return_time' => '17:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => false, 'partner_id' => $partner->id, 'partner_vehicle_cost' => 3000000, 'license_plate' => '15B-100.13', 'is_hired_driver' => false, 'freight_amount' => 5000000]);
        $hiredRun = $service->createRun('tourist', ['customer_id' => $customer->id, 'journey' => 'Tour thuê lái', 'run_date' => '2026-05-02', 'pickup_time' => '08:00', 'return_time' => '17:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-100.14', 'is_hired_driver' => true, 'hired_driver_name' => 'Lái thuê', 'hired_driver_cost' => 600000, 'freight_amount' => 4000000]);

        $service->completeTouristRun(TouristVehicle::query()->findOrFail($partnerRun['id']));
        $service->completeTouristRun(TouristVehicle::query()->findOrFail($hiredRun['id']));

        $this->assertDatabaseCount('driver_attendances', 0);
    }

    public function test_worker_run_uses_route_snapshot_and_requires_a_catalog_company_vehicle(): void
    {
        $routeCustomer = Customer::query()->create(['code' => 'KH-ROUTE-SNAPSHOT', 'type' => 'company', 'name' => 'Khách tuyến']);
        $otherCustomer = Customer::query()->create(['code' => 'KH-CLIENT-SNAPSHOT', 'type' => 'company', 'name' => 'Khách client']);
        $type = VehicleType::query()->create(['code' => 'XE-SNAPSHOT', 'name' => 'Xe snapshot', 'tourism_wage_rate' => 0]);
        $route = Route::query()->create(['code' => 'T-SNAPSHOT', 'customer_id' => $routeCustomer->id, 'name' => 'Tuyến snapshot', 'shift_name' => 'Ca tuyến', 'pickup_location' => 'Điểm tuyến đón', 'dropoff_location' => 'Điểm tuyến trả', 'default_pickup_time' => '06:30', 'default_return_time' => '17:30']);
        RouteVehicleTypeRate::query()->create(['route_id' => $route->id, 'vehicle_type_id' => $type->id, 'freight_amount' => 1500000, 'driver_wage' => 250000]);
        $vehicle = Vehicle::query()->create(['code' => 'VX-ROUTE-SNAPSHOT', 'license_plate' => '15B-777.77', 'seat_count' => 29, 'ownership_type' => 'company', 'vehicle_type_id' => $type->id]);

        $run = app(VehicleOperationService::class)->createRun('worker', [
            'route_id' => $route->id,
            'customer_id' => $otherCustomer->id,
            'shift_name' => 'Ca giả',
            'run_date' => '2026-02-10',
            'pickup_time' => '08:00',
            'return_time' => '20:00',
            'pickup_location' => 'Điểm giả',
            'dropoff_location' => 'Điểm giả',
            'freight_amount' => 1,
            'driver_wage' => 1,
            'is_company_vehicle' => true,
            'vehicle_id' => $vehicle->id,
            'license_plate' => '15B-GIA.00',
            'is_hired_driver' => false,
        ]);

        $this->assertSame($routeCustomer->id, $run['customer_id']);
        $this->assertSame('Ca tuyến', $run['shift_name']);
        $this->assertSame('Điểm tuyến đón', $run['pickup_location']);
        $this->assertSame('06:30', $run['pickup_time']);
        $this->assertSame('15B-777.77', $run['license_plate']);
        $this->assertSame('1500000.00', $run['freight_amount']);

        $this->expectException(ValidationException::class);
        app(VehicleOperationService::class)->createRun('worker', [
            'route_id' => $route->id,
            'run_date' => '2026-02-11',
            'is_company_vehicle' => true,
            'license_plate' => '15B-NHAP.01',
            'is_hired_driver' => false,
        ]);
    }

    public function test_worker_external_vehicle_requires_partner_plate_type_and_rental_cost(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-EXTERNAL-RUN', 'type' => 'company', 'name' => 'Khách xe ngoài']);
        $partner = Partner::query()->create(['code' => 'DT-EXTERNAL-RUN', 'type' => 'transport_company', 'name' => 'Hãng xe ngoài']);
        $type = VehicleType::query()->create(['code' => 'XE-EXTERNAL', 'name' => 'Xe ngoài', 'tourism_wage_rate' => 0]);
        $route = Route::query()->create(['code' => 'T-EXTERNAL', 'customer_id' => $customer->id, 'name' => 'Tuyến xe ngoài', 'shift_name' => 'Ca sáng', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'default_pickup_time' => '07:00', 'default_return_time' => '16:00']);
        RouteVehicleTypeRate::query()->create(['route_id' => $route->id, 'vehicle_type_id' => $type->id, 'freight_amount' => 2100000, 'driver_wage' => 300000]);

        $run = app(VehicleOperationService::class)->createRun('worker', [
            'route_id' => $route->id,
            'run_date' => '2026-02-12',
            'is_company_vehicle' => false,
            'partner_id' => $partner->id,
            'license_plate' => '15B-888.88',
            'vehicle_type_id' => $type->id,
            'partner_vehicle_cost' => 1200000,
            'is_hired_driver' => false,
        ]);

        $this->assertFalse($run['is_company_vehicle']);
        $this->assertSame($partner->id, $run['partner_id']);
        $this->assertSame('15B-888.88', $run['license_plate']);
        $this->assertSame(1200000, $run['partner_vehicle_cost']);
    }

    public function test_hired_worker_driver_has_no_internal_wage_or_driver_for_company_and_partner_vehicles(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-HIRED-WORKER', 'type' => 'company', 'name' => 'Khách thuê lái']);
        $partner = Partner::query()->create(['code' => 'DT-HIRED-WORKER', 'type' => 'transport_company', 'name' => 'Đối tác thuê lái']);
        $driver = Driver::query()->create(['code' => 'LX-HIRED-WORKER', 'type' => 'company', 'full_name' => 'Lái nội bộ', 'license_number' => 'B2-HIRED-WORKER', 'license_class' => 'B2']);
        $type = VehicleType::query()->create(['code' => 'XE-HIRED-WORKER', 'name' => 'Xe thuê lái', 'tourism_wage_rate' => 0]);
        $route = Route::query()->create(['code' => 'T-HIRED-WORKER', 'customer_id' => $customer->id, 'name' => 'Tuyến thuê lái', 'shift_name' => 'Ca sáng', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'default_pickup_time' => '07:00', 'default_return_time' => '16:00']);
        RouteVehicleTypeRate::query()->create(['route_id' => $route->id, 'vehicle_type_id' => $type->id, 'freight_amount' => 2000000, 'driver_wage' => 300000]);
        $vehicle = Vehicle::query()->create(['code' => 'VX-HIRED-WORKER', 'license_plate' => '15B-333.33', 'seat_count' => 29, 'ownership_type' => 'company', 'vehicle_type_id' => $type->id, 'default_driver_id' => $driver->id]);
        $service = app(VehicleOperationService::class);

        $companyRun = $service->createRun('worker', ['route_id' => $route->id, 'run_date' => '2026-02-20', 'is_company_vehicle' => true, 'vehicle_id' => $vehicle->id, 'is_hired_driver' => true, 'hired_driver_name' => 'Lái thuê công ty', 'hired_driver_cost' => 350000]);
        $partnerRun = $service->createRun('worker', ['route_id' => $route->id, 'run_date' => '2026-02-21', 'is_company_vehicle' => false, 'partner_id' => $partner->id, 'license_plate' => '15B-444.44', 'vehicle_type_id' => $type->id, 'partner_vehicle_cost' => 1200000, 'driver_id' => $driver->id, 'is_hired_driver' => true, 'hired_driver_name' => 'Lái thuê đối tác', 'hired_driver_cost' => 400000]);

        foreach ([$companyRun, $partnerRun] as $run) {
            $this->assertSame(0, (int) $run['driver_wage']);
            $this->assertNull($run['driver_id']);
        }
        $this->assertSame(350000, (int) $companyRun['hired_driver_cost']);
        $this->assertSame(400000, (int) $partnerRun['hired_driver_cost']);
    }

    public function test_worker_wage_switches_between_route_rate_and_zero_when_driver_is_hired(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-HIRED-EDIT', 'type' => 'company', 'name' => 'Khách sửa thuê lái']);
        $driver = Driver::query()->create(['code' => 'LX-HIRED-EDIT', 'type' => 'company', 'full_name' => 'Lái nội bộ', 'license_number' => 'B2-HIRED-EDIT', 'license_class' => 'B2']);
        $type = VehicleType::query()->create(['code' => 'XE-HIRED-EDIT', 'name' => 'Xe sửa thuê lái', 'tourism_wage_rate' => 0]);
        $route = Route::query()->create(['code' => 'T-HIRED-EDIT', 'customer_id' => $customer->id, 'name' => 'Tuyến sửa thuê lái', 'shift_name' => 'Ca chiều', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'default_pickup_time' => '13:00', 'default_return_time' => '18:00']);
        RouteVehicleTypeRate::query()->create(['route_id' => $route->id, 'vehicle_type_id' => $type->id, 'freight_amount' => 1800000, 'driver_wage' => 280000]);
        $vehicle = Vehicle::query()->create(['code' => 'VX-HIRED-EDIT', 'license_plate' => '15B-555.55', 'seat_count' => 29, 'ownership_type' => 'company', 'vehicle_type_id' => $type->id, 'default_driver_id' => $driver->id]);
        $service = app(VehicleOperationService::class);
        $run = $service->createRun('worker', ['route_id' => $route->id, 'run_date' => '2026-02-22', 'is_company_vehicle' => true, 'vehicle_id' => $vehicle->id, 'is_hired_driver' => false]);

        $this->assertSame(280000, (int) $run['driver_wage']);
        $hired = $service->updateRun('worker', WorkerVehicle::query()->findOrFail($run['id']), ['is_hired_driver' => true, 'hired_driver_name' => 'Lái thuê', 'hired_driver_cost' => 360000]);
        $this->assertSame(0, (int) $hired['driver_wage']);
        $this->assertNull($hired['driver_id']);

        $internal = $service->updateRun('worker', WorkerVehicle::query()->findOrFail($run['id']), ['is_hired_driver' => false, 'driver_id' => $driver->id]);
        $this->assertSame(280000, (int) $internal['driver_wage']);
        $this->assertSame($driver->id, $internal['driver_id']);
    }

    public function test_generated_hired_worker_run_has_zero_internal_wage(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-HIRED-SCHEDULE', 'type' => 'company', 'name' => 'Khách lịch thuê lái']);
        $partner = Partner::query()->create(['code' => 'DT-HIRED-SCHEDULE', 'type' => 'transport_company', 'name' => 'Đối tác lịch thuê lái']);
        $route = Route::query()->create(['code' => 'T-HIRED-SCHEDULE', 'customer_id' => $customer->id, 'name' => 'Tuyến lịch thuê lái', 'shift_name' => 'Ca sáng', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'default_pickup_time' => '07:00', 'default_return_time' => '16:00']);
        $service = app(VehicleOperationService::class);
        $service->saveRate(['route_id' => $route->id, 'license_plate' => '15B-666.66', 'freight_amount' => 1900000, 'driver_wage' => 290000, 'effective_from' => '2026-01-01']);
        $rule = WorkerVehicleScheduleRule::query()->create(['customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'effective_from' => '2026-01-01', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => false, 'partner_id' => $partner->id, 'license_plate' => '15B-666.66', 'is_hired_driver' => true, 'hired_driver_name' => 'Lái thuê lịch', 'hired_driver_cost' => 390000]);
        $rule->days()->create(['weekday' => 1, 'pickup_time' => '07:00', 'return_time' => '16:00']);

        $generated = $service->generateRuns($rule, '2026-02-02', '2026-02-02');

        $this->assertCount(1, $generated['created']);
        $this->assertSame(0, (int) $generated['created'][0]['driver_wage']);
        $this->assertNull($generated['created'][0]['driver_id']);
        $this->assertDatabaseHas('worker_vehicles', ['id' => $generated['created'][0]['id'], 'driver_wage' => 0, 'driver_id' => null]);

        WorkerVehicle::query()->findOrFail($generated['created'][0]['id'])->forceFill(['driver_wage' => 290000])->save();
        $service->generateRuns($rule, '2026-02-02', '2026-02-02');

        $this->assertDatabaseHas('worker_vehicles', ['id' => $generated['created'][0]['id'], 'driver_wage' => 0, 'driver_id' => null]);
    }

    public function test_tourist_run_uses_the_vehicle_type_rate_instead_of_client_wage_values(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-TOUR-RATE', 'type' => 'company', 'name' => 'Khách du lịch']);
        $driver = Driver::query()->create(['code' => 'LX-TOUR-RATE', 'type' => 'company', 'full_name' => 'Lái xe du lịch', 'license_number' => 'B2-TOUR-RATE', 'license_class' => 'B2']);
        $type = VehicleType::query()->create(['code' => 'XE-TOUR-RATE', 'name' => 'Xe du lịch', 'tourism_wage_rate' => 0.105]);
        $vehicle = Vehicle::query()->create(['code' => 'VX-TOUR-RATE', 'license_plate' => '15B-999.99', 'seat_count' => 29, 'ownership_type' => 'company', 'vehicle_type_id' => $type->id, 'default_driver_id' => $driver->id]);

        $run = app(VehicleOperationService::class)->createRun('tourist', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'journey' => 'Hải Phòng - Hạ Long',
            'run_date' => '2026-02-15',
            'pickup_time' => '08:00',
            'return_time' => '17:00',
            'pickup_location' => 'A',
            'dropoff_location' => 'B',
            'is_company_vehicle' => true,
            'vehicle_id' => $vehicle->id,
            'is_hired_driver' => false,
            'freight_amount' => 5000000,
            'driver_wage_rate' => 0.9,
            'driver_wage' => 1,
        ]);

        $this->assertSame('0.1050', $run['driver_wage_rate']);
        $this->assertSame('525000.00', $run['driver_wage']);
    }

    public function test_tourist_run_can_snapshot_an_overridden_rate_but_never_a_client_wage(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-TOUR-OVERRIDE', 'type' => 'company', 'name' => 'Khách du lịch']);
        $driver = Driver::query()->create(['code' => 'LX-TOUR-OVERRIDE', 'type' => 'company', 'full_name' => 'Lái xe du lịch', 'license_number' => 'B2-TOUR-OVERRIDE', 'license_class' => 'B2']);
        $type = VehicleType::query()->create(['code' => 'XE-TOUR-OVERRIDE', 'name' => 'Xe du lịch', 'tourism_wage_rate' => 0.1]);
        $vehicle = Vehicle::query()->create(['code' => 'VX-TOUR-OVERRIDE', 'license_plate' => '15B-888.88', 'seat_count' => 29, 'ownership_type' => 'company', 'vehicle_type_id' => $type->id, 'default_driver_id' => $driver->id]);
        $service = app(VehicleOperationService::class);
        $run = $service->createRun('tourist', ['customer_name' => $customer->name, 'journey' => 'Hải Phòng - Hạ Long', 'run_date' => '2026-03-01', 'pickup_time' => '08:00', 'return_time' => '17:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'vehicle_id' => $vehicle->id, 'is_hired_driver' => false, 'freight_amount' => 5000000]);

        $updated = $service->updateRun('tourist', TouristVehicle::query()->findOrFail($run['id']), ['driver_wage_is_overridden' => true, 'driver_wage_rate' => 0.125, 'driver_wage' => 1]);

        $this->assertTrue($updated['driver_wage_is_overridden']);
        $this->assertEquals(0.125, $updated['driver_wage_rate']);
        $this->assertEquals(625000, $updated['driver_wage']);
    }

    public function test_completed_tourist_run_rejects_a_rate_change(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-TOUR-LOCK', 'type' => 'company', 'name' => 'Khách du lịch']);
        $driver = Driver::query()->create(['code' => 'LX-TOUR-LOCK', 'type' => 'company', 'full_name' => 'Lái xe du lịch', 'license_number' => 'B2-TOUR-LOCK', 'license_class' => 'B2']);
        $type = VehicleType::query()->create(['code' => 'XE-TOUR-LOCK', 'name' => 'Xe du lịch', 'tourism_wage_rate' => 0.1]);
        $vehicle = Vehicle::query()->create(['code' => 'VX-TOUR-LOCK', 'license_plate' => '15B-777.77', 'seat_count' => 29, 'ownership_type' => 'company', 'vehicle_type_id' => $type->id, 'default_driver_id' => $driver->id]);
        $service = app(VehicleOperationService::class);
        $run = $service->createRun('tourist', ['customer_name' => $customer->name, 'journey' => 'Hải Phòng - Hạ Long', 'run_date' => '2026-03-02', 'pickup_time' => '08:00', 'return_time' => '17:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'vehicle_id' => $vehicle->id, 'is_hired_driver' => false, 'freight_amount' => 5000000]);
        $model = TouristVehicle::query()->findOrFail($run['id']);
        $service->completeTouristRun($model, '2026-03-02 17:00:00');

        $this->expectException(HttpException::class);
        $service->updateRun('tourist', $model->fresh(), ['driver_wage_is_overridden' => true, 'driver_wage_rate' => 0.125]);
    }

    public function test_worker_list_falls_back_to_the_route_shift_and_only_planned_runs_can_be_cancelled(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-CANCEL-RUN', 'type' => 'company', 'name' => 'Khách hàng']);
        $route = Route::query()->create(['code' => 'T-CANCEL-RUN', 'customer_id' => $customer->id, 'name' => 'Tuyến xe', 'shift_name' => 'Ca chiều', 'pickup_location' => 'A', 'dropoff_location' => 'B']);
        $planned = WorkerVehicle::query()->create(['run_no' => 'CN-CANCEL-001', 'customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => '', 'run_date' => '2026-04-01', 'pickup_time' => '13:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => false, 'partner_id' => Partner::query()->create(['code' => 'DT-CANCEL-RUN', 'type' => 'transport_company', 'name' => 'Đối tác'])->id, 'license_plate' => '15B-555.55', 'is_hired_driver' => true, 'hired_driver_name' => 'Lái thuê', 'hired_driver_cost' => 300000, 'partner_vehicle_cost' => 1000000, 'status' => 'planned']);
        $service = app(VehicleOperationService::class);

        $this->assertSame('Ca chiều', $service->workerRun($planned)['shift_name']);
        $service->deleteRun($planned);
        $this->assertSame('cancelled', $planned->fresh()->status);

        $completed = WorkerVehicle::query()->create(['run_no' => 'CN-CANCEL-002', 'customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca chiều', 'run_date' => '2026-04-02', 'pickup_time' => '13:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => false, 'partner_id' => $planned->partner_id, 'license_plate' => '15B-555.56', 'is_hired_driver' => true, 'hired_driver_name' => 'Lái thuê', 'hired_driver_cost' => 300000, 'partner_vehicle_cost' => 1000000, 'status' => 'completed']);
        $this->expectException(HttpException::class);
        $service->deleteRun($completed);
    }
}
