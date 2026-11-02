<?php

namespace Database\Seeders;

use App\Modules\MasterData\Database\Seeds\CustomerDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\DriverDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\PartnerDatabaseSeeder;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\ExpenseType;
use App\Modules\MasterData\Models\Route;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicleRate;
use App\Modules\VehicleOperations\Models\WorkerVehicleScheduleRule;
use Illuminate\Database\Seeder;

/** Predictable local/dev operational baseline. It intentionally has no legacy dispatch data. */
class RefreshBusinessDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([CustomerDatabaseSeeder::class, PartnerDatabaseSeeder::class, DriverDatabaseSeeder::class]);
        ExpenseType::firstOrCreate(['code' => 'FUEL'], ['name' => 'Xăng dầu', 'scope' => 'vehicle', 'profit_category' => 'fuel']);
        ExpenseType::firstOrCreate(['code' => 'TOLL'], ['name' => 'Phí cầu đường', 'scope' => 'trip', 'profit_category' => 'other']);
        $customer = Customer::query()->where('is_active', true)->firstOrFail();
        $driver = Driver::query()->where('is_active', true)->firstOrFail();
        $route = Route::query()->firstOrCreate(['code' => 'CN-HP-01'], ['customer_id' => $customer->id, 'name' => 'KCN VSIP - Thủy Nguyên', 'shift_name' => 'Ca sáng', 'pickup_location' => 'Cổng KCN VSIP', 'dropoff_location' => 'Thủy Nguyên', 'default_pickup_time' => '06:30', 'default_return_time' => '17:30']);
        $plate = '15B-123.45';
        WorkerVehicleRate::firstOrCreate(['route_id' => $route->id, 'license_plate' => $plate, 'effective_from' => now()->startOfYear()->toDateString()], ['freight_amount' => 2500000, 'driver_wage' => 300000]);
        $rule = WorkerVehicleScheduleRule::firstOrCreate(['customer_id' => $customer->id, 'route_id' => $route->id, 'license_plate' => $plate, 'effective_from' => now()->startOfMonth()->toDateString()], ['shift_name' => 'Ca sáng', 'pickup_location' => 'Cổng KCN VSIP', 'dropoff_location' => 'Thủy Nguyên', 'is_company_vehicle' => true, 'driver_id' => $driver->id, 'is_hired_driver' => false]);
        $rule->days()->firstOrCreate(['weekday' => now()->dayOfWeekIso, 'pickup_time' => '06:30'], ['return_time' => '17:30']);
        WorkerVehicle::firstOrCreate(['schedule_rule_id' => $rule->id, 'run_date' => now()->toDateString(), 'pickup_time' => '06:30'], ['run_no' => 'CN'.now()->format('Ymd').'001', 'customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'return_date' => now()->toDateString(), 'return_time' => '17:30', 'pickup_location' => 'Cổng KCN VSIP', 'dropoff_location' => 'Thủy Nguyên', 'is_company_vehicle' => true, 'license_plate' => $plate, 'driver_id' => $driver->id, 'is_hired_driver' => false, 'freight_amount' => 2500000, 'driver_wage' => 300000, 'status' => 'planned']);
        TouristVehicle::firstOrCreate(['run_no' => 'DL'.now()->format('Ymd').'001'], ['customer_id' => $customer->id, 'journey' => 'Hải Phòng - Hạ Long', 'run_date' => now()->addDay()->toDateString(), 'return_date' => now()->addDay()->toDateString(), 'pickup_time' => '07:00', 'return_time' => '19:00', 'pickup_location' => 'Hải Phòng', 'dropoff_location' => 'Hạ Long', 'is_company_vehicle' => true, 'license_plate' => '15B-678.90', 'driver_id' => $driver->id, 'is_hired_driver' => false, 'freight_amount' => 6000000, 'driver_wage_rate' => $driver->tourism_commission_rate, 'driver_wage' => 6000000 * (float) $driver->tourism_commission_rate, 'status' => 'planned']);
    }
}
