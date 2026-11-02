<?php

namespace Database\Seeders;

use App\Modules\DriverPayroll\Models\DriverAdvance;
use App\Modules\DriverPayroll\Models\DriverAttendance;
use App\Modules\DriverPayroll\Models\Payroll;
use App\Modules\DriverPayroll\Models\PayrollItem;
use App\Modules\DriverPayroll\Models\PayrollItemDetail;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\MasterData\Database\Seeds\CustomerDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\DriverDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\PartnerDatabaseSeeder;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\ExpenseType;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Route;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicleRate;
use App\Modules\VehicleOperations\Models\WorkerVehicleScheduleRule;
use Illuminate\Database\Seeder;

/** Compact, idempotent local data for vehicle operations, finance, payroll and reports. */
class RefreshBusinessDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([CustomerDatabaseSeeder::class, PartnerDatabaseSeeder::class, DriverDatabaseSeeder::class]);

        $fuel = ExpenseType::firstOrCreate(['code' => 'FUEL'], ['name' => 'Xăng dầu', 'scope' => 'general', 'profit_category' => 'fuel']);
        $hireExpense = ExpenseType::firstOrCreate(['code' => 'HIRED_DRIVER'], ['name' => 'Chi trả thuê lái', 'scope' => 'general', 'profit_category' => 'other']);
        $customer = Customer::query()->where('code', 'KH0001')->firstOrFail();
        $driver = Driver::query()->where('code', 'LX0001')->firstOrFail();
        $driverTwo = Driver::query()->where('code', 'LX0004')->firstOrFail();
        if ((float) $driver->tourism_commission_rate === 0.0) {
            $driver->update(['tourism_commission_rate' => 0.1]);
            $driver->refresh();
        }
        $partner = Partner::query()->where('code', 'DT0001')->firstOrFail();
        $route = Route::query()->firstOrCreate(['code' => 'CN-HP-01'], ['customer_id' => $customer->id, 'name' => 'KCN VSIP - Thủy Nguyên', 'shift_name' => 'Ca sáng', 'pickup_location' => 'Cổng KCN VSIP', 'dropoff_location' => 'Thủy Nguyên', 'default_pickup_time' => '06:30', 'default_return_time' => '17:30']);
        $start = now()->startOfMonth();
        $completed = now()->subDay()->setTime(17, 30);
        $plate = '15B-123.45';

        WorkerVehicleRate::firstOrCreate(['route_id' => $route->id, 'license_plate' => $plate, 'effective_from' => $start->copy()->startOfYear()->toDateString()], ['freight_amount' => 2500000, 'driver_wage' => 300000]);
        $rule = WorkerVehicleScheduleRule::firstOrCreate(['customer_id' => $customer->id, 'route_id' => $route->id, 'license_plate' => $plate, 'effective_from' => $start->toDateString()], ['shift_name' => 'Ca sáng', 'pickup_location' => 'Cổng KCN VSIP', 'dropoff_location' => 'Thủy Nguyên', 'is_company_vehicle' => true, 'driver_id' => $driver->id, 'is_hired_driver' => false]);
        $rule->days()->firstOrCreate(['weekday' => now()->dayOfWeekIso, 'pickup_time' => '06:30'], ['return_time' => '17:30']);

        $worker = WorkerVehicle::firstOrCreate(['run_no' => 'CN-SEED-001'], ['schedule_rule_id' => $rule->id, 'customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'run_date' => $completed->toDateString(), 'return_date' => $completed->toDateString(), 'pickup_time' => '06:30', 'return_time' => '17:30', 'pickup_location' => 'Cổng KCN VSIP', 'dropoff_location' => 'Thủy Nguyên', 'is_company_vehicle' => true, 'license_plate' => $plate, 'driver_id' => $driver->id, 'is_hired_driver' => false, 'freight_amount' => 2500000, 'driver_wage' => 300000, 'status' => 'completed', 'actual_started_at' => $completed->copy()->setTime(6, 30), 'actual_completed_at' => $completed, 'completed_at' => $completed]);
        WorkerVehicle::firstOrCreate(['run_no' => 'CN-SEED-002'], ['schedule_rule_id' => $rule->id, 'customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'run_date' => now()->addDays(7)->toDateString(), 'return_date' => now()->addDays(7)->toDateString(), 'pickup_time' => '06:30', 'return_time' => '17:30', 'pickup_location' => 'Cổng KCN VSIP', 'dropoff_location' => 'Thủy Nguyên', 'is_company_vehicle' => true, 'license_plate' => $plate, 'driver_id' => $driver->id, 'is_hired_driver' => false, 'freight_amount' => 2500000, 'driver_wage' => 300000, 'status' => 'planned']);
        $workerPending = WorkerVehicle::firstOrCreate(['run_no' => 'CN-SEED-003'], ['schedule_rule_id' => $rule->id, 'customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca chiều', 'run_date' => $completed->toDateString(), 'return_date' => $completed->toDateString(), 'pickup_time' => '13:00', 'return_time' => '18:00', 'pickup_location' => 'Thủy Nguyên', 'dropoff_location' => 'Cổng KCN VSIP', 'is_company_vehicle' => true, 'license_plate' => '15B-123.46', 'driver_id' => $driver->id, 'is_hired_driver' => false, 'freight_amount' => 1800000, 'driver_wage' => 250000, 'status' => 'completed', 'actual_started_at' => $completed->copy()->subHours(3), 'actual_completed_at' => $completed->copy()->subHour(), 'completed_at' => $completed->copy()->subHour()]);
        WorkerVehicle::firstOrCreate(['run_no' => 'CN-SEED-004'], ['schedule_rule_id' => $rule->id, 'customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'run_date' => now()->toDateString(), 'return_date' => now()->toDateString(), 'pickup_time' => '08:00', 'return_time' => '17:00', 'pickup_location' => 'Cổng KCN VSIP', 'dropoff_location' => 'Thủy Nguyên', 'is_company_vehicle' => true, 'license_plate' => '15B-123.47', 'is_hired_driver' => true, 'hired_driver_name' => 'Nguyễn Văn Thuê', 'hired_driver_cost' => 350000, 'freight_amount' => 2200000, 'driver_wage' => 0, 'status' => 'in_progress', 'actual_started_at' => now()->setTime(8, 0)]);
        WorkerVehicle::firstOrCreate(['run_no' => 'CN-SEED-005'], ['schedule_rule_id' => $rule->id, 'customer_id' => $customer->id, 'route_id' => $route->id, 'shift_name' => 'Ca sáng', 'run_date' => now()->addDays(14)->toDateString(), 'return_date' => now()->addDays(14)->toDateString(), 'pickup_time' => '06:30', 'return_time' => '17:30', 'pickup_location' => 'Cổng KCN VSIP', 'dropoff_location' => 'Thủy Nguyên', 'is_company_vehicle' => true, 'license_plate' => '15B-123.48', 'driver_id' => $driver->id, 'is_hired_driver' => false, 'freight_amount' => 2500000, 'driver_wage' => 300000, 'status' => 'cancelled', 'note' => 'Tạm dừng theo yêu cầu khách hàng']);

        $tourist = TouristVehicle::firstOrCreate(['run_no' => 'DL-SEED-001'], ['customer_id' => $customer->id, 'journey' => 'Hải Phòng - Hạ Long', 'run_date' => $completed->toDateString(), 'return_date' => $completed->toDateString(), 'pickup_time' => '07:00', 'return_time' => '19:00', 'pickup_location' => 'Hải Phòng', 'dropoff_location' => 'Hạ Long', 'is_company_vehicle' => true, 'license_plate' => '15B-678.90', 'driver_id' => $driver->id, 'is_hired_driver' => false, 'freight_amount' => 6000000, 'driver_wage_rate' => $driver->tourism_commission_rate, 'driver_wage' => 6000000 * (float) $driver->tourism_commission_rate, 'status' => 'completed', 'actual_started_at' => $completed->copy()->setTime(7, 0), 'actual_completed_at' => $completed->copy()->setTime(19, 0), 'completed_at' => $completed->copy()->setTime(19, 0)]);
        TouristVehicle::firstOrCreate(['run_no' => 'DL-SEED-002'], ['customer_id' => $customer->id, 'journey' => 'Hải Phòng - Ninh Bình', 'run_date' => now()->addDays(2)->toDateString(), 'return_date' => now()->addDays(2)->toDateString(), 'pickup_time' => '06:00', 'return_time' => '20:00', 'pickup_location' => 'Hải Phòng', 'dropoff_location' => 'Ninh Bình', 'is_company_vehicle' => false, 'partner_id' => $partner->id, 'license_plate' => '15B-999.01', 'is_hired_driver' => false, 'freight_amount' => 8500000, 'partner_vehicle_cost' => 5500000, 'status' => 'planned']);
        TouristVehicle::firstOrCreate(['run_no' => 'DL-SEED-003'], ['customer_id' => $customer->id, 'journey' => 'Hải Phòng - Cát Bà', 'run_date' => $completed->toDateString(), 'return_date' => $completed->toDateString(), 'pickup_time' => '08:00', 'return_time' => '18:00', 'pickup_location' => 'Hải Phòng', 'dropoff_location' => 'Cát Bà', 'is_company_vehicle' => true, 'license_plate' => '15B-678.91', 'is_hired_driver' => true, 'hired_driver_name' => 'Nguyễn Văn Thuê', 'hired_driver_cost' => 800000, 'freight_amount' => 4500000, 'status' => 'completed', 'actual_started_at' => $completed->copy()->subHours(5), 'actual_completed_at' => $completed->copy()->subHours(2), 'completed_at' => $completed->copy()->subHours(2)]);
        TouristVehicle::firstOrCreate(['run_no' => 'DL-SEED-004'], ['customer_id' => $customer->id, 'journey' => 'Hải Phòng - Sapa', 'run_date' => $completed->toDateString(), 'return_date' => $completed->toDateString(), 'pickup_time' => '05:00', 'return_time' => '21:00', 'pickup_location' => 'Hải Phòng', 'dropoff_location' => 'Sapa', 'is_company_vehicle' => false, 'partner_id' => $partner->id, 'license_plate' => '15B-999.02', 'is_hired_driver' => false, 'freight_amount' => 12000000, 'partner_vehicle_cost' => 8000000, 'status' => 'completed', 'actual_started_at' => $completed->copy()->subHours(12), 'actual_completed_at' => $completed->copy()->subHours(4), 'completed_at' => $completed->copy()->subHours(4)]);
        TouristVehicle::firstOrCreate(['run_no' => 'DL-SEED-005'], ['customer_id' => $customer->id, 'journey' => 'Hải Phòng - Tam Đảo', 'run_date' => now()->toDateString(), 'return_date' => now()->toDateString(), 'pickup_time' => '09:00', 'return_time' => '20:00', 'pickup_location' => 'Hải Phòng', 'dropoff_location' => 'Tam Đảo', 'is_company_vehicle' => true, 'license_plate' => '15B-678.92', 'driver_id' => $driverTwo->id, 'is_hired_driver' => false, 'freight_amount' => 7000000, 'driver_wage_rate' => $driverTwo->tourism_commission_rate, 'driver_wage' => 7000000 * (float) $driverTwo->tourism_commission_rate, 'status' => 'in_progress', 'actual_started_at' => now()->setTime(9, 0)]);

        DriverAttendance::firstOrCreate(['worker_vehicle_id' => $worker->id, 'driver_id' => $driver->id], ['work_date' => $completed->toDateString(), 'work_type' => 'fixed_trip', 'work_units' => 1, 'base_amount' => 300000, 'rate' => 1, 'calculated_wage' => 300000, 'status' => 'confirmed']);
        DriverAttendance::firstOrCreate(['tourist_vehicle_id' => $tourist->id, 'driver_id' => $driver->id], ['work_date' => $completed->toDateString(), 'work_type' => 'tourism_trip', 'work_units' => 1, 'base_amount' => 6000000, 'rate' => $driver->tourism_commission_rate, 'calculated_wage' => 6000000 * (float) $driver->tourism_commission_rate, 'status' => 'confirmed']);
        DriverAttendance::firstOrCreate(['worker_vehicle_id' => $workerPending->id, 'driver_id' => $driver->id], ['work_date' => $completed->toDateString(), 'work_type' => 'fixed_trip', 'work_units' => 1, 'base_amount' => 250000, 'rate' => 1, 'calculated_wage' => 250000, 'status' => 'pending']);

        Receipt::firstOrCreate(['receipt_no' => 'PT-SEED-001'], ['customer_id' => $customer->id, 'receipt_type' => 'contract_payment', 'received_at' => $completed, 'amount' => 4000000, 'payment_method' => 'bank_transfer', 'payer_name' => $customer->name, 'description' => 'Thu cước xe chạy mẫu']);
        Expense::firstOrCreate(['expense_no' => 'PC-SEED-001'], ['expense_type_id' => $fuel->id, 'expense_date' => $completed, 'amount' => 700000, 'payment_method' => 'cash', 'recipient_name' => 'Trạm nhiên liệu', 'description' => 'Chi phí nhiên liệu mẫu']);
        Expense::firstOrCreate(['expense_no' => 'PC-SEED-002'], ['expense_type_id' => $hireExpense->id, 'expense_date' => $completed->copy()->addDay(), 'amount' => 500000, 'payment_method' => 'cash', 'recipient_name' => 'Nguyễn Văn Thuê', 'hired_driver_name' => 'Nguyễn Văn Thuê', 'description' => 'Thanh toán một phần thuê lái']);
        PartnerPayment::firstOrCreate(['payment_no' => 'PCT-SEED-001'], ['partner_id' => $partner->id, 'paid_at' => $completed, 'amount' => 1000000, 'payment_method' => 'bank_transfer', 'description' => 'Chi trả đối tác mẫu']);
        Receipt::firstOrCreate(['receipt_no' => 'PT-SEED-002'], ['customer_id' => $customer->id, 'receipt_type' => 'other', 'received_at' => now(), 'amount' => 1500000, 'payment_method' => 'cash', 'payer_name' => $customer->name, 'description' => 'Thu bổ sung mẫu']);
        PartnerPayment::firstOrCreate(['payment_no' => 'PCT-SEED-002'], ['partner_id' => $partner->id, 'paid_at' => now(), 'amount' => 2000000, 'payment_method' => 'bank_transfer', 'description' => 'Chi trả chuyến xe đối tác mẫu']);

        $payroll = Payroll::firstOrCreate(['code' => 'LUONG-SEED-'.now()->format('Ym')], ['month' => now()->month, 'year' => now()->year, 'from_date' => $start->toDateString(), 'to_date' => now()->endOfMonth()->toDateString(), 'status' => 'calculated']);
        $gross = (float) $driver->responsibility_allowance + 300000 + 6000000 * (float) $driver->tourism_commission_rate;
        $advance = DriverAdvance::firstOrCreate(['advance_no' => 'TU-SEED-001'], ['driver_id' => $driver->id, 'advance_date' => $completed->toDateString(), 'amount' => 200000, 'description' => 'Tạm ứng lương mẫu', 'status' => 'confirmed']);
        $item = PayrollItem::firstOrCreate(['payroll_id' => $payroll->id, 'driver_id' => $driver->id], ['base_salary' => 0, 'responsibility_allowance' => $driver->responsibility_allowance, 'meal_allowance' => 0, 'fixed_trip_wage' => 300000, 'tourism_commission' => 6000000 * (float) $driver->tourism_commission_rate, 'other_allowance' => 0, 'advance_amount' => $advance->amount, 'deduction_amount' => 0, 'gross_salary' => $gross, 'net_salary' => $gross - (float) $advance->amount, 'note' => 'Dữ liệu lương mẫu']);
        $workerAttendance = DriverAttendance::query()->where('worker_vehicle_id', $worker->id)->firstOrFail();
        $touristAttendance = DriverAttendance::query()->where('tourist_vehicle_id', $tourist->id)->firstOrFail();
        PayrollItemDetail::firstOrCreate(['payroll_item_id' => $item->id, 'driver_attendance_id' => $workerAttendance->id], ['worker_vehicle_id' => $worker->id, 'calculation_type' => 'fixed_trip', 'base_amount' => 300000, 'rate' => 1, 'amount' => 300000, 'description' => 'Lương chuyến công nhân mẫu']);
        PayrollItemDetail::firstOrCreate(['payroll_item_id' => $item->id, 'driver_attendance_id' => $touristAttendance->id], ['tourist_vehicle_id' => $tourist->id, 'calculation_type' => 'tourism_commission', 'base_amount' => 6000000, 'rate' => $driver->tourism_commission_rate, 'amount' => 6000000 * (float) $driver->tourism_commission_rate, 'description' => 'Lương chuyến du lịch mẫu']);
    }
}
