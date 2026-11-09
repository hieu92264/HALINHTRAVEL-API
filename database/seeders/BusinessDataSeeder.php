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
use App\Modules\MasterData\Database\Seeds\VehicleDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\VehicleTypeDatabaseSeeder;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\ExpenseType;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicleRate;
use App\Modules\VehicleOperations\Models\WorkerVehicleScheduleRule;
use Illuminate\Database\Seeder;

/** A deterministic local operating dataset; dates move with the day it is seeded. */
class BusinessDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([CustomerDatabaseSeeder::class, PartnerDatabaseSeeder::class, VehicleTypeDatabaseSeeder::class, DriverDatabaseSeeder::class, VehicleDatabaseSeeder::class]);
        $this->seedCatalogExtensions();
        $this->seedWorkerRatesAndSchedules();
        [$workerRuns, $touristRuns] = $this->seedRuns();
        $this->seedFinance();
        $this->seedPayroll($workerRuns, $touristRuns);
    }

    private function seedWorkerRatesAndSchedules(): void
    {
        $routes = Route::query()->orderBy('code')->get()->values();
        $vehicles = Vehicle::query()->where('ownership_type', 'company')->orderBy('code')->get()->values();
        $partner = Partner::query()->where('code', 'DT0001')->firstOrFail();

        foreach ($routes as $index => $route) {
            $vehicle = $vehicles[$index % $vehicles->count()];
            $routeRate = $route->vehicleTypeRates()->where('vehicle_type_id', $vehicle->vehicle_type_id)->firstOrFail();
            WorkerVehicleRate::query()->updateOrCreate(
                ['route_id' => $route->id, 'vehicle_id' => $vehicle->id],
                ['license_plate' => $vehicle->license_plate, 'freight_amount' => $routeRate->freight_amount, 'driver_wage' => $routeRate->driver_wage, 'effective_from' => now()->startOfYear()->toDateString(), 'effective_to' => null, 'is_active' => true],
            );
            $rule = WorkerVehicleScheduleRule::query()->updateOrCreate(
                ['route_id' => $route->id, 'vehicle_id' => $vehicle->id],
                ['customer_id' => $route->customer_id, 'shift_name' => $route->shift_name, 'effective_from' => now()->startOfMonth()->toDateString(), 'effective_to' => null, 'pickup_location' => $route->pickup_location, 'dropoff_location' => $route->dropoff_location, 'is_company_vehicle' => true, 'partner_id' => null, 'license_plate' => $vehicle->license_plate, 'driver_id' => $vehicle->default_driver_id, 'is_hired_driver' => false, 'hired_driver_name' => null, 'hired_driver_cost' => 0, 'is_active' => true, 'note' => 'Lịch xe công nhân định kỳ'],
            );
            $rule->days()->delete();
            $rule->days()->createMany([['weekday' => $index % 2 === 0 ? 1 : 2, 'pickup_time' => $route->default_pickup_time, 'return_time' => $route->default_return_time]]);

            $externalPlate = '15B-70'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            WorkerVehicleRate::query()->updateOrCreate(
                ['route_id' => $route->id, 'vehicle_id' => null, 'license_plate' => $externalPlate],
                ['freight_amount' => $routeRate->freight_amount, 'driver_wage' => $routeRate->driver_wage, 'effective_from' => now()->startOfYear()->toDateString(), 'effective_to' => null, 'is_active' => true],
            );
            $externalRule = WorkerVehicleScheduleRule::query()->updateOrCreate(
                ['route_id' => $route->id, 'vehicle_id' => null, 'license_plate' => $externalPlate],
                ['customer_id' => $route->customer_id, 'shift_name' => $route->shift_name, 'effective_from' => now()->startOfMonth()->toDateString(), 'effective_to' => null, 'pickup_location' => $route->pickup_location, 'dropoff_location' => $route->dropoff_location, 'is_company_vehicle' => false, 'partner_id' => $partner->id, 'driver_id' => null, 'is_hired_driver' => true, 'hired_driver_name' => 'Ngô Đức Thành', 'hired_driver_cost' => 350000, 'is_active' => true, 'note' => 'Lịch xe công nhân thuê lái'],
            );
            $externalRule->days()->delete();
            $externalRule->days()->createMany([['weekday' => $index % 2 === 0 ? 3 : 4, 'pickup_time' => $route->default_pickup_time, 'return_time' => $route->default_return_time]]);
        }
    }

    private function seedCatalogExtensions(): void
    {
        Partner::query()->where('code', 'DT0001')->update(['email' => 'dieuhanh@minhphat.vn']);
        Partner::query()->where('code', 'DT0002')->update(['email' => 'vanhanh@thanhcong.vn']);
        foreach ([
            ['code' => 'DT0003', 'name' => 'Công ty Cổ phần Vận tải Hoàng Long', 'phone' => '02253881103', 'email' => 'kinhdoanh@hoanglong.vn', 'tax_code' => '0201982003', 'address' => 'Hồng Bàng, Hải Phòng', 'type' => 'transport_company'],
            ['code' => 'DT0004', 'name' => 'Hộ kinh doanh Vận tải Thành Công', 'phone' => '0904556789', 'email' => 'thanhcong.vantai@gmail.com', 'cccd' => '031084005678', 'address' => 'Thủy Nguyên, Hải Phòng', 'type' => 'vehicle_owner'],
        ] as $partner) {
            Partner::query()->updateOrCreate(['code' => $partner['code']], [...$partner, 'bank_name' => 'Vietcombank', 'bank_account' => '102010000'.substr($partner['code'], -1), 'opening_balance' => 0, 'is_active' => true]);
        }

        foreach ([
            ['code' => 'LX0005', 'full_name' => 'Lê Văn Dũng', 'phone' => '0903111223', 'license_number' => 'D-790005', 'license_class' => 'D', 'base_salary' => 11000000, 'responsibility_allowance' => 700000],
            ['code' => 'LX0006', 'full_name' => 'Đỗ Minh Tâm', 'phone' => '0912445566', 'license_number' => 'E-790006', 'license_class' => 'E', 'base_salary' => 12500000, 'responsibility_allowance' => 1000000],
            ['code' => 'LX0007', 'full_name' => 'Phạm Quốc Việt', 'phone' => '0983778899', 'license_number' => 'D-790007', 'license_class' => 'D', 'base_salary' => 10800000, 'responsibility_allowance' => 600000],
            ['code' => 'LX0008', 'full_name' => 'Bùi Anh Tuấn', 'phone' => '0974332211', 'license_number' => 'E-790008', 'license_class' => 'E', 'base_salary' => 12000000, 'responsibility_allowance' => 800000],
        ] as $driver) {
            Driver::query()->updateOrCreate(['code' => $driver['code']], [...$driver, 'type' => 'company', 'cccd' => '03109'.substr($driver['code'], -1).'001234', 'license_issued_at' => '2021-01-15', 'license_expired_at' => '2031-01-15', 'joined_at' => '2022-01-15', 'tourism_commission_rate' => 0, 'is_active' => true]);
        }

        $drivers = Driver::query()->whereIn('code', ['LX0001', 'LX0004', 'LX0005', 'LX0006', 'LX0007', 'LX0008'])->orderBy('code')->get();
        $types = VehicleType::query()->get()->keyBy('code');
        foreach ([
            ['code' => 'VX0010', 'license_plate' => '15B-888.47', 'type' => 'Xe45', 'model' => 'Thaco Bluesky 45 chỗ'],
        ] as $index => $vehicle) {
            Vehicle::query()->updateOrCreate(['code' => $vehicle['code']], ['license_plate' => $vehicle['license_plate'], 'vehicle_type_id' => $types[$vehicle['type']]->id, 'seat_count' => (int) substr($vehicle['type'], 2), 'vehicle_model' => $vehicle['model'], 'ownership_type' => 'company', 'default_driver_id' => $drivers[$index % $drivers->count()]->id, 'partner_id' => null, 'note' => 'Xe vận hành tuyến hợp đồng', 'is_active' => true]);
        }

        $customers = Customer::query()->orderBy('code')->get();
        foreach ([
            ['CNHP01', 0, 'KCN VSIP - Thủy Nguyên', 'Ca sáng', 'Cổng KCN VSIP', 'Thủy Nguyên', '06:30', '17:30'],
            ['CNHP02', 1, 'KCN Tràng Duệ - An Dương', 'Ca ngày', 'KCN Tràng Duệ', 'An Dương', '07:00', '18:00'],
            ['CNHP03', 0, 'KCN VSIP - Kiến Thụy', 'Ca chiều', 'KCN VSIP', 'Kiến Thụy', '13:00', '22:00'],
            ['CNHP04', 4, 'Hồng Bàng - Đình Vũ', 'Ca hành chính', 'Hồng Bàng', 'Đình Vũ', '07:15', '17:45'],
        ] as $routeData) {
            [$code, $customerIndex, $name, $shift, $pickup, $dropoff, $pickupTime, $returnTime] = $routeData;
            $route = Route::query()->updateOrCreate(['code' => $code], ['customer_id' => $customers[$customerIndex]->id, 'name' => $name, 'shift_name' => $shift, 'pickup_location' => $pickup, 'dropoff_location' => $dropoff, 'default_pickup_time' => $pickupTime, 'default_return_time' => $returnTime, 'estimated_distance_km' => 28]);
            foreach ($types as $type) {
                $route->vehicleTypeRates()->updateOrCreate(['vehicle_type_id' => $type->id], ['freight_amount' => 700000 + ((int) substr($type->code, 2) * 30000), 'driver_wage' => 170000 + ((int) substr($type->code, 2) * 6000)]);
            }
        }

        foreach ([['FUEL', 'Dầu', 'vehicle', 'fuel'], ['REPAIR', 'Sửa chữa', 'vehicle', 'repair'], ['INSURANCE', 'BH/ĐK', 'vehicle', 'insurance_registration'], ['VEHICLE_OTHER', 'Chi khác', 'vehicle', 'other'], ['OFFICE', 'Chi phí điều hành', 'general', null], ['GENERAL_OTHER', 'Chi phí chung khác', 'general', null]] as [$code, $name, $scope, $category]) {
            ExpenseType::query()->updateOrCreate(['code' => $code], ['name' => $name, 'scope' => $scope, 'profit_category' => $category, 'is_active' => true]);
        }
    }

    /** @return array{0: array<int, WorkerVehicle>, 1: array<int, TouristVehicle>} */
    private function seedRuns(): array
    {
        $today = now()->startOfDay();
        $vehicles = Vehicle::query()->where('ownership_type', 'company')->orderBy('code')->get();
        $drivers = Driver::query()->where('type', 'company')->orderBy('code')->get();
        $routes = Route::query()->orderBy('code')->get();
        $types = VehicleType::query()->get()->keyBy('code');
        $partners = Partner::query()->whereIn('code', ['DT0001', 'DT0003', 'DT0004'])->get()->keyBy('code');
        $customers = Customer::query()->get()->keyBy('code');
        $workerRuns = [];
        $touristRuns = [];
        for ($i = 1; $i <= 30; $i++) {
            $route = $routes[($i - 1) % $routes->count()];
            $external = $i % 5 === 0;
            $vehicle = $vehicles[($i - 1) % $vehicles->count()];
            $date = $i <= 20 ? $today->copy()->subDays(66 - $i * 3) : ($i === 21 ? $today : $today->copy()->addDays($i - 21));
            $status = $i <= 20 ? 'completed' : ($i === 21 ? 'in_progress' : ($i <= 27 ? 'planned' : 'cancelled'));
            $type = $external ? $types['Xe29'] : $vehicle->vehicleType;
            $rate = $route->vehicleTypeRates()->where('vehicle_type_id', $type->id)->firstOrFail();
            $values = ['vehicle_id' => $external ? null : $vehicle->id, 'vehicle_type_id' => $type->id, 'customer_id' => $route->customer_id, 'route_id' => $route->id, 'shift_name' => $route->shift_name, 'run_date' => $date->toDateString(), 'return_date' => $date->toDateString(), 'pickup_time' => $route->default_pickup_time, 'return_time' => $route->default_return_time, 'pickup_location' => $route->pickup_location, 'dropoff_location' => $route->dropoff_location, 'is_company_vehicle' => ! $external, 'partner_id' => $external ? $partners['DT0001']->id : null, 'license_plate' => $external ? '15B-79'.str_pad((string) $i, 3, '0', STR_PAD_LEFT) : $vehicle->license_plate, 'driver_id' => $external ? null : $drivers[($i - 1) % $drivers->count()]->id, 'is_hired_driver' => $external, 'hired_driver_name' => $external ? 'Ngô Đức Thành' : null, 'hired_driver_cost' => $external ? 350000 : 0, 'partner_vehicle_cost' => $external ? 1350000 : 0, 'freight_amount' => $rate->freight_amount, 'driver_wage' => $external ? 0 : $rate->driver_wage, 'status' => $status, 'note' => $status === 'cancelled' ? 'Khách hàng đổi lịch vận chuyển' : null];
            if ($status === 'completed') {
                $values += ['actual_started_at' => $date->copy()->setTime(6, 30), 'actual_completed_at' => $date->copy()->setTime(17, 30), 'completed_at' => $date->copy()->setTime(17, 30)];
            }
            if ($status === 'in_progress') {
                $values['actual_started_at'] = now()->subHours(2);
            }
            $workerRuns[] = WorkerVehicle::query()->updateOrCreate(['run_no' => 'CN-'.$date->format('Ymd').'-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)], $values);
        }
        $journeys = [['Hải Phòng', 'Hạ Long'], ['Hải Phòng', 'Ninh Bình'], ['Hải Phòng', 'Tam Đảo'], ['Hải Phòng', 'Cát Bà'], ['Hải Phòng', 'Sapa'], ['Hải Phòng', 'Hà Nội']];
        for ($i = 1; $i <= 24; $i++) {
            $external = $i % 4 === 0;
            $vehicle = $vehicles[($i + 3) % $vehicles->count()];
            $type = $external ? $types['Xe29'] : $vehicle->vehicleType;
            $date = $i <= 16 ? $today->copy()->subDays(64 - $i * 3) : ($i <= 18 ? $today : $today->copy()->addDays($i - 18));
            $status = $i <= 16 ? 'completed' : ($i <= 18 ? 'in_progress' : ($i <= 22 ? 'planned' : 'cancelled'));
            [$from, $to] = $journeys[($i - 1) % count($journeys)];
            $freight = 3500000 + ($i % 5) * 850000;
            $internal = ! $external && $i % 6 !== 0;
            $driver = $internal ? $drivers[($i + 1) % $drivers->count()] : null;
            $wageRate = $internal ? (float) $type->tourism_wage_rate : 0;
            $values = ['vehicle_id' => $external ? null : $vehicle->id, 'vehicle_type_id' => $type->id, 'customer_id' => $customers['KH000'.(($i % 5) + 1)]->id, 'customer_name' => null, 'journey' => "$from - $to", 'run_date' => $date->toDateString(), 'return_date' => $date->toDateString(), 'pickup_time' => '07:00', 'return_time' => '19:00', 'pickup_location' => $from, 'dropoff_location' => $to, 'is_company_vehicle' => ! $external, 'partner_id' => $external ? $partners['DT0003']->id : null, 'license_plate' => $external ? '14B-68'.str_pad((string) $i, 3, '0', STR_PAD_LEFT) : $vehicle->license_plate, 'driver_id' => $driver?->id, 'is_hired_driver' => ! $internal, 'hired_driver_name' => ! $internal ? 'Vũ Anh Khoa' : null, 'hired_driver_cost' => ! $internal ? 650000 : 0, 'partner_vehicle_cost' => $external ? 4200000 : 0, 'freight_amount' => $freight, 'driver_wage_rate' => $wageRate, 'driver_wage' => $freight * $wageRate, 'driver_wage_is_overridden' => false, 'driver_collected_amount' => $status === 'completed' && $internal ? 150000 : 0, 'company_collected_amount' => $status === 'completed' ? $freight - 150000 : 0, 'driver_remitted_amount' => $status === 'completed' && $internal ? 100000 : 0, 'driver_expense_amount' => $status === 'completed' && $internal ? 50000 : 0, 'status' => $status, 'note' => $status === 'cancelled' ? 'Đoàn thay đổi ngày khởi hành' : null];
            if ($status === 'completed') {
                $values += ['actual_started_at' => $date->copy()->setTime(7, 0), 'actual_completed_at' => $date->copy()->setTime(19, 0), 'completed_at' => $date->copy()->setTime(19, 0)];
            }
            if ($status === 'in_progress') {
                $values['actual_started_at'] = now()->subHours(3);
            }
            $touristRuns[] = TouristVehicle::query()->updateOrCreate(['run_no' => 'DL-'.$date->format('Ymd').'-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)], $values);
        }

        return [$workerRuns, $touristRuns];
    }

    private function seedFinance(): void
    {
        $today = now()->startOfDay();
        $vehicles = Vehicle::query()->where('ownership_type', 'company')->get()->values();
        $types = ExpenseType::query()->get()->keyBy('code');
        $customers = Customer::query()->get()->values();
        $partners = Partner::query()->get()->values();
        for ($i = 1; $i <= 32; $i++) {
            $date = $today->copy()->subDays(68 - $i * 2);
            $code = ['FUEL', 'REPAIR', 'INSURANCE', 'VEHICLE_OTHER'][($i - 1) % 4];
            Expense::query()->updateOrCreate(['expense_no' => 'PC'.$date->format('Ym').str_pad((string) $i, 4, '0', STR_PAD_LEFT)], ['expense_type_id' => $types[$code]->id, 'vehicle_id' => $vehicles[($i - 1) % $vehicles->count()]->id, 'expense_date' => $date, 'amount' => 450000 + ($i % 6) * 175000, 'payment_method' => $i % 2 ? 'cash' : 'bank_transfer', 'recipient_name' => $code === 'FUEL' ? 'Cửa hàng xăng dầu Đông Bắc' : 'Dịch vụ vận tải Hải Phòng', 'paid_by' => ['Nguyễn Văn Hùng', 'Đỗ Minh Tâm', 'Lê Văn Dũng'][($i - 1) % 3], 'description' => 'Chi phí vận hành xe']);
        }
        for ($i = 1; $i <= 12; $i++) {
            $date = $today->copy()->subDays(66 - $i * 5);
            Expense::query()->updateOrCreate(['expense_no' => 'PC'.$date->format('Ym').'9'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)], ['expense_type_id' => $types[$i % 2 ? 'OFFICE' : 'GENERAL_OTHER']->id, 'vehicle_id' => null, 'expense_date' => $date, 'amount' => 700000 + $i * 80000, 'payment_method' => 'bank_transfer', 'recipient_name' => 'Chi phí điều hành', 'paid_by' => 'Phòng kế toán', 'description' => $i % 2 ? 'Chi phí văn phòng và điều hành' : 'Chi phí chung hoạt động']);
        }
        for ($i = 1; $i <= 8; $i++) {
            $date = $today->copy()->subDays(60 - $i * 6);
            Receipt::query()->updateOrCreate(['receipt_no' => 'PT'.$date->format('Ym').str_pad((string) $i, 4, '0', STR_PAD_LEFT)], ['customer_id' => $customers[($i - 1) % $customers->count()]->id, 'receipt_type' => 'contract_payment', 'received_at' => $date, 'amount' => 3000000 + $i * 450000, 'payment_method' => 'bank_transfer', 'payer_name' => $customers[($i - 1) % $customers->count()]->name, 'description' => 'Thanh toán cước vận chuyển']);
            PartnerPayment::query()->updateOrCreate(['payment_no' => 'CT'.$date->format('Ym').str_pad((string) $i, 4, '0', STR_PAD_LEFT)], ['partner_id' => $partners[($i - 1) % $partners->count()]->id, 'paid_at' => $date, 'amount' => 1500000 + $i * 250000, 'payment_method' => 'bank_transfer', 'recipient_name' => ['Nguyễn Thị Hương', 'Trần Văn Nam', 'Lê Thị Mai', 'Phạm Quốc Khánh'][($i - 1) % 4], 'description' => 'Thanh toán dịch vụ vận tải']);
        }
    }

    /** @param array<int, WorkerVehicle> $workers @param array<int, TouristVehicle> $tourists */
    private function seedPayroll(array $workers, array $tourists): void
    {
        $drivers = Driver::query()->where('type', 'company')->get()->keyBy('id');
        foreach (array_merge($workers, $tourists) as $run) {
            if ($run->status === 'completed' && $run->driver_id) {
                $tourist = $run instanceof TouristVehicle;
                $attendance = DriverAttendance::query()->updateOrCreate([$tourist ? 'tourist_vehicle_id' : 'worker_vehicle_id' => $run->id, 'driver_id' => $run->driver_id], ['work_date' => $run->completed_at->toDateString(), 'work_type' => $tourist ? 'tourism_trip' : 'fixed_trip', 'work_units' => 1, 'base_amount' => $tourist ? $run->freight_amount : $run->driver_wage, 'rate' => $tourist ? $run->driver_wage_rate : 1, 'calculated_wage' => $run->driver_wage, 'status' => 'confirmed']);
            }
        }
        $current = now()->startOfMonth();
        foreach ($drivers as $driver) {
            DriverAdvance::query()->updateOrCreate(['advance_no' => 'TU'.$current->format('Ym').str_pad((string) $driver->id, 3, '0', STR_PAD_LEFT)], ['driver_id' => $driver->id, 'advance_date' => $current->copy()->addDays($driver->id % 12)->toDateString(), 'amount' => 300000 + ($driver->id % 3) * 100000, 'description' => 'Tạm ứng lương tháng '.$current->format('m/Y'), 'status' => 'confirmed']);
        }
        foreach ([$current->copy()->subMonth(), $current] as $month) {
            $payroll = Payroll::query()->updateOrCreate(['month' => $month->month, 'year' => $month->year, 'revision' => 1], ['code' => 'LUONG-'.$month->format('Y-m').'-B01', 'from_date' => $month->copy()->startOfMonth()->toDateString(), 'to_date' => $month->copy()->endOfMonth()->toDateString(), 'status' => $month->isSameMonth($current) ? 'calculated' : 'locked', 'is_current' => true, 'is_active' => true]);
            foreach ($drivers as $driver) {
                $atts = DriverAttendance::query()->where('driver_id', $driver->id)->whereBetween('work_date', [$payroll->from_date, $payroll->to_date])->get();
                $fixed = (float) $atts->where('work_type', 'fixed_trip')->sum('calculated_wage');
                $tour = (float) $atts->where('work_type', 'tourism_trip')->sum('calculated_wage');
                $advance = (float) DriverAdvance::query()->where('driver_id', $driver->id)->whereBetween('advance_date', [$payroll->from_date, $payroll->to_date])->sum('amount');
                $settlement = TouristVehicle::query()->where('driver_id', $driver->id)->where('status', 'completed')->whereBetween('completed_at', [$payroll->from_date, $payroll->to_date.' 23:59:59'])->selectRaw('COALESCE(SUM(driver_collected_amount),0) collected, COALESCE(SUM(driver_remitted_amount),0) remitted, COALESCE(SUM(driver_expense_amount),0) expense')->first();
                $gross = (float) $driver->responsibility_allowance + $fixed + $tour;
                $net = $gross - $advance - (float) $settlement->collected + (float) $settlement->remitted + (float) $settlement->expense;
                $item = PayrollItem::query()->updateOrCreate(['payroll_id' => $payroll->id, 'driver_id' => $driver->id], ['base_salary' => 0, 'responsibility_allowance' => $driver->responsibility_allowance, 'fixed_trip_wage' => $fixed, 'tourism_commission' => $tour, 'advance_amount' => $advance, 'driver_collected_amount' => $settlement->collected, 'driver_remitted_amount' => $settlement->remitted, 'driver_expense_amount' => $settlement->expense, 'gross_salary' => $gross, 'net_salary' => $net, 'note' => 'Tổng hợp lương tháng '.$month->format('m/Y')]);
                foreach ($atts as $attendance) {
                    PayrollItemDetail::query()->updateOrCreate(['payroll_item_id' => $item->id, 'driver_attendance_id' => $attendance->id], ['worker_vehicle_id' => $attendance->worker_vehicle_id, 'tourist_vehicle_id' => $attendance->tourist_vehicle_id, 'calculation_type' => $attendance->work_type === 'tourism_trip' ? 'tourism_commission' : 'fixed_trip', 'base_amount' => $attendance->base_amount, 'rate' => $attendance->rate, 'amount' => $attendance->calculated_wage, 'description' => 'Lương chuyến xe']);
                }
            } if (! $month->isSameMonth($current)) {
                DriverAttendance::query()->whereBetween('work_date', [$payroll->from_date, $payroll->to_date])->update(['status' => 'payroll_locked']);
                DriverAdvance::query()->whereBetween('advance_date', [$payroll->from_date, $payroll->to_date])->update(['status' => 'payroll_locked', 'payroll_id' => $payroll->id]);
            }
        }
        $cancelled = Payroll::query()->updateOrCreate(['month' => $current->month, 'year' => $current->year, 'revision' => 2], ['code' => 'LUONG-'.$current->format('Y-m').'-B02', 'from_date' => $current->copy()->startOfMonth()->toDateString(), 'to_date' => $current->copy()->endOfMonth()->toDateString(), 'status' => 'cancelled', 'is_current' => false, 'is_active' => false]);
    }
}
