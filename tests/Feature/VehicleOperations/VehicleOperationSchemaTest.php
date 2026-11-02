<?php

namespace Tests\Feature\VehicleOperations;

use App\Modules\DriverPayroll\Models\DriverAttendance;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Route;
use App\Modules\Other\Services\OperationalReportService;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicleScheduleRule;
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
        $service->createRun('tourist', ['customer_id' => $customer->id, 'journey' => 'Tour test', 'run_date' => '2026-02-01', 'pickup_time' => '08:00', 'return_time' => '12:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-000.02', 'is_hired_driver' => false]);

        $this->expectException(ValidationException::class);
        $service->createRun('worker', ['customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'run_date' => '2026-02-01', 'pickup_time' => '09:00', 'return_time' => '11:00', 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-000.02', 'is_hired_driver' => false]);
    }

    public function test_default_seed_uses_only_new_vehicle_operation_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('worker_vehicles', 2);
        $this->assertDatabaseCount('tourist_vehicles', 3);
        $this->assertFalse(Schema::hasTable('vehicles'));
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

    public function test_rule_generates_an_idempotent_ninety_day_window_with_rate_snapshots(): void
    {
        $customer = Customer::query()->create(['code' => 'KH-WINDOW', 'type' => 'company', 'name' => 'Khách lịch lặp']);
        $route = Route::query()->create(['code' => 'T-WINDOW', 'customer_id' => $customer->id, 'name' => 'Tuyến lịch lặp', 'pickup_location' => 'A', 'dropoff_location' => 'B']);
        $driver = Driver::query()->create(['code' => 'LX-WINDOW', 'type' => 'company', 'full_name' => 'Lái xe lịch lặp', 'license_number' => 'D-WINDOW', 'license_class' => 'D']);
        $service = app(VehicleOperationService::class);
        $from = now()->startOfDay();
        $service->saveRate(['route_id' => $route->id, 'license_plate' => '15B-100.90', 'freight_amount' => 1800000, 'driver_wage' => 250000, 'effective_from' => $from->toDateString()]);
        $rule = $service->saveRule(['customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca tất cả các ngày', 'effective_from' => $from->toDateString(), 'pickup_location' => 'A', 'dropoff_location' => 'B', 'is_company_vehicle' => true, 'license_plate' => '15B-100.90', 'driver_id' => $driver->id, 'is_hired_driver' => false, 'days' => array_map(fn (int $weekday): array => ['weekday' => $weekday, 'pickup_time' => '06:00', 'return_time' => '17:00'], range(1, 7))]);
        $model = WorkerVehicleScheduleRule::query()->findOrFail($rule['id']);
        $count = WorkerVehicle::query()->where('schedule_rule_id', $model->id)->count();

        $this->assertGreaterThanOrEqual(90, $count);
        $this->assertDatabaseHas('worker_vehicles', ['schedule_rule_id' => $model->id, 'freight_amount' => 1800000, 'driver_wage' => 250000]);
        $service->generateRuns($model, $from->toDateString(), $from->addDays(90)->toDateString());
        $this->assertSame($count, WorkerVehicle::query()->where('schedule_rule_id', $model->id)->count());
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
}
