<?php

namespace Database\Seeders;

use App\Modules\DriverPayroll\Models\DriverAdvance;
use App\Modules\DriverPayroll\Models\DriverAttendance;
use App\Modules\DriverPayroll\Models\Payroll;
use App\Modules\DriverPayroll\Models\PayrollItem;
use App\Modules\DriverPayroll\Models\PayrollItemDetail;
use App\Modules\MasterData\Database\Seeds\CustomerDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\DriverDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\PartnerDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\VehicleDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\VehicleTypeDatabaseSeeder;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use Illuminate\Database\Seeder;

/** Idempotent demo data that follows the normalized vehicle-type workflow. */
class BusinessDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CustomerDatabaseSeeder::class,
            PartnerDatabaseSeeder::class,
            VehicleTypeDatabaseSeeder::class,
            DriverDatabaseSeeder::class,
            VehicleDatabaseSeeder::class,
        ]);

        $customer = Customer::query()->where('code', 'KH0001')->firstOrFail();
        $driver = Driver::query()->where('code', 'LX0001')->firstOrFail();
        $carrier = Partner::query()->where('code', 'DT0001')->firstOrFail();
        $route = Route::query()->updateOrCreate(['code' => 'CN-HP-01'], [
            'customer_id' => $customer->id,
            'name' => 'KCN VSIP - Thủy Nguyên',
            'shift_name' => 'Ca sáng',
            'pickup_location' => 'Cổng KCN VSIP',
            'dropoff_location' => 'Thủy Nguyên',
            'default_pickup_time' => '06:30',
            'default_return_time' => '17:30',
            'estimated_distance_km' => 25.50,
        ]);

        $rates = [
            'Xe04' => [500000, 180000], 'Xe07' => [650000, 200000],
            'Xe16' => [1200000, 280000], 'Xe29' => [1800000, 350000],
            'Xe34' => [2200000, 400000], 'Xe45' => [2800000, 480000],
        ];
        foreach ($rates as $typeCode => [$freight, $wage]) {
            $type = VehicleType::query()->where('code', $typeCode)->firstOrFail();
            $route->vehicleTypeRates()->updateOrCreate(['vehicle_type_id' => $type->id], [
                'freight_amount' => $freight,
                'driver_wage' => $wage,
            ]);
        }

        $companyVehicle = Vehicle::query()->where('license_plate', '15B-123.45')->firstOrFail();
        $companySnapshot = [
            'vehicle_id' => $companyVehicle->id,
            'vehicle_type_id' => $companyVehicle->vehicle_type_id,
            'is_company_vehicle' => true,
            'partner_id' => null,
            'license_plate' => $companyVehicle->license_plate,
        ];
        $workerRate = $route->vehicleTypeRates()->where('vehicle_type_id', $companyVehicle->vehicle_type_id)->firstOrFail();
        $completedAt = now()->subDay()->setTime(17, 30);

        $worker = WorkerVehicle::query()->updateOrCreate(['run_no' => 'CN-SEED-001'], [
            ...$companySnapshot,
            'customer_id' => $customer->id,
            'route_id' => $route->id,
            'shift_name' => $route->shift_name,
            'run_date' => $completedAt->toDateString(),
            'return_date' => $completedAt->toDateString(),
            'pickup_time' => $route->default_pickup_time,
            'return_time' => $route->default_return_time,
            'pickup_location' => $route->pickup_location,
            'dropoff_location' => $route->dropoff_location,
            'driver_id' => $driver->id,
            'is_hired_driver' => false,
            'hired_driver_cost' => 0,
            'partner_vehicle_cost' => 0,
            'freight_amount' => $workerRate->freight_amount,
            'driver_wage' => $workerRate->driver_wage,
            'status' => 'completed',
            'actual_started_at' => $completedAt->copy()->setTime(6, 30),
            'actual_completed_at' => $completedAt,
            'completed_at' => $completedAt,
        ]);

        WorkerVehicle::query()->updateOrCreate(['run_no' => 'CN-SEED-002'], [
            ...$companySnapshot,
            'customer_id' => $customer->id,
            'route_id' => $route->id,
            'shift_name' => $route->shift_name,
            'run_date' => now()->addDays(2)->toDateString(),
            'return_date' => now()->addDays(2)->toDateString(),
            'pickup_time' => $route->default_pickup_time,
            'return_time' => $route->default_return_time,
            'pickup_location' => $route->pickup_location,
            'dropoff_location' => $route->dropoff_location,
            'driver_id' => $driver->id,
            'is_hired_driver' => false,
            'hired_driver_cost' => 0,
            'partner_vehicle_cost' => 0,
            'freight_amount' => $workerRate->freight_amount,
            'driver_wage' => $workerRate->driver_wage,
            'status' => 'planned',
        ]);

        $partnerWorkerType = VehicleType::query()->where('code', 'Xe29')->firstOrFail();
        WorkerVehicle::query()->updateOrCreate(['run_no' => 'CN-SEED-003'], [
            'vehicle_id' => null, 'vehicle_type_id' => $partnerWorkerType->id,
            'customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => $route->shift_name,
            'run_date' => $completedAt->toDateString(), 'return_date' => $completedAt->toDateString(),
            'pickup_time' => '13:00', 'return_time' => '18:00',
            'pickup_location' => $route->dropoff_location, 'dropoff_location' => $route->pickup_location,
            'is_company_vehicle' => false, 'partner_id' => $carrier->id, 'license_plate' => '15B-999.29',
            'driver_id' => null, 'is_hired_driver' => true, 'hired_driver_name' => 'Lái xe thuê mẫu',
            'hired_driver_cost' => 350000, 'partner_vehicle_cost' => 1200000,
            'freight_amount' => 1800000, 'driver_wage' => 0, 'status' => 'completed',
            'actual_started_at' => $completedAt->copy()->subHours(4),
            'actual_completed_at' => $completedAt->copy()->subHour(), 'completed_at' => $completedAt->copy()->subHour(),
        ]);

        $tourVehicle = Vehicle::query()->where('license_plate', '15B-678.90')->firstOrFail();
        $tourType = VehicleType::query()->findOrFail($tourVehicle->vehicle_type_id);
        $tourFreight = 6000000;
        $tourWage = $tourFreight * (float) $tourType->tourism_wage_rate;
        $tourist = TouristVehicle::query()->updateOrCreate(['run_no' => 'DL-SEED-001'], [
            'vehicle_id' => $tourVehicle->id,
            'vehicle_type_id' => $tourType->id,
            'customer_id' => null,
            'customer_name' => 'Công ty Du lịch Ánh Dương',
            'journey' => 'Hải Phòng - Hạ Long',
            'run_date' => $completedAt->toDateString(),
            'return_date' => $completedAt->toDateString(),
            'pickup_time' => '07:00', 'return_time' => '19:00',
            'pickup_location' => 'Hải Phòng', 'dropoff_location' => 'Hạ Long',
            'is_company_vehicle' => true, 'partner_id' => null,
            'license_plate' => $tourVehicle->license_plate, 'driver_id' => $driver->id,
            'is_hired_driver' => false, 'hired_driver_cost' => 0, 'partner_vehicle_cost' => 0,
            'freight_amount' => $tourFreight, 'driver_wage_rate' => $tourType->tourism_wage_rate,
            'driver_wage' => $tourWage, 'driver_wage_is_overridden' => false,
            'driver_collected_amount' => 450000, 'company_collected_amount' => 1500000,
            'driver_remitted_amount' => 200000, 'driver_expense_amount' => 50000,
            'status' => 'completed', 'actual_started_at' => $completedAt->copy()->setTime(7, 0),
            'actual_completed_at' => $completedAt->copy()->setTime(19, 0), 'completed_at' => $completedAt->copy()->setTime(19, 0),
        ]);

        $partnerType = VehicleType::query()->where('code', 'Xe29')->firstOrFail();
        TouristVehicle::query()->updateOrCreate(['run_no' => 'DL-SEED-002'], [
            'vehicle_id' => null, 'vehicle_type_id' => $partnerType->id,
            'customer_id' => null, 'customer_name' => 'Đoàn khách Tam Đảo',
            'journey' => 'Hải Phòng - Tam Đảo', 'run_date' => now()->addDays(3)->toDateString(),
            'return_date' => now()->addDays(3)->toDateString(), 'pickup_time' => '06:00', 'return_time' => '20:00',
            'pickup_location' => 'Hải Phòng', 'dropoff_location' => 'Tam Đảo',
            'is_company_vehicle' => false, 'partner_id' => $carrier->id, 'license_plate' => '15B-999.29',
            'driver_id' => null, 'is_hired_driver' => true, 'hired_driver_name' => 'Lái xe thuê mẫu',
            'hired_driver_cost' => 700000, 'partner_vehicle_cost' => 4800000,
            'freight_amount' => 7500000, 'driver_wage_rate' => 0, 'driver_wage' => 0,
            'driver_wage_is_overridden' => false, 'status' => 'planned',
        ]);

        DriverAttendance::query()->updateOrCreate(
            ['worker_vehicle_id' => $worker->id, 'driver_id' => $driver->id],
            ['work_date' => $completedAt->toDateString(), 'work_type' => 'fixed_trip', 'work_units' => 1, 'base_amount' => $workerRate->driver_wage, 'rate' => 1, 'calculated_wage' => $workerRate->driver_wage, 'status' => 'confirmed'],
        );
        $tourAttendance = DriverAttendance::query()->updateOrCreate(
            ['tourist_vehicle_id' => $tourist->id, 'driver_id' => $driver->id],
            ['work_date' => $completedAt->toDateString(), 'work_type' => 'tourism_trip', 'work_units' => 1, 'base_amount' => $tourFreight, 'rate' => $tourType->tourism_wage_rate, 'calculated_wage' => $tourWage, 'status' => 'confirmed'],
        );
        $advance = DriverAdvance::query()->updateOrCreate(['advance_no' => 'TU-SEED-001'], ['driver_id' => $driver->id, 'advance_date' => $completedAt->toDateString(), 'amount' => 200000, 'description' => 'Tạm ứng lương mẫu', 'status' => 'confirmed']);
        $gross = (float) $driver->responsibility_allowance + (float) $workerRate->driver_wage + $tourWage;
        $net = $gross - (float) $advance->amount - 450000 + 200000 + 50000;
        $payroll = Payroll::query()->updateOrCreate(
            ['month' => now()->month, 'year' => now()->year, 'revision' => 1],
            ['code' => sprintf('LUONG-%04d-%02d-B01', now()->year, now()->month), 'from_date' => now()->startOfMonth()->toDateString(), 'to_date' => now()->endOfMonth()->toDateString(), 'status' => 'calculated', 'is_current' => true],
        );
        $item = PayrollItem::query()->updateOrCreate(['payroll_id' => $payroll->id, 'driver_id' => $driver->id], [
            'base_salary' => 0, 'responsibility_allowance' => $driver->responsibility_allowance,
            'meal_allowance' => 0, 'fixed_trip_wage' => $workerRate->driver_wage,
            'tourism_commission' => $tourWage, 'other_allowance' => 0, 'advance_amount' => $advance->amount,
            'driver_collected_amount' => 450000, 'driver_remitted_amount' => 200000,
            'driver_expense_amount' => 50000, 'deduction_amount' => 0,
            'gross_salary' => $gross, 'net_salary' => $net, 'note' => 'Bản mẫu hiện hành',
        ]);
        PayrollItemDetail::query()->updateOrCreate(['payroll_item_id' => $item->id, 'driver_attendance_id' => $tourAttendance->id], [
            'tourist_vehicle_id' => $tourist->id, 'calculation_type' => 'tourism_commission', 'base_amount' => $tourFreight,
            'rate' => $tourType->tourism_wage_rate, 'amount' => $tourWage, 'description' => 'Lương du lịch theo loại xe',
        ]);
    }
}
