<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Replaces only business-domain data with a compact, current operational dataset.
 * Auth users, roles, permissions, and their pivot tables are intentionally untouched.
 */
class RefreshBusinessDataSeeder extends Seeder
{
    private Carbon $now;

    public function run(): void
    {
        $this->now = now();
        $this->assertSeedAccountsExist();

        DB::transaction(function (): void {
            $this->clearBusinessData();
            $this->seedBusinessData();
        });
    }

    private function assertSeedAccountsExist(): void
    {
        foreach (['admin', 'driver'] as $userName) {
            if (! DB::table('users')->where('user_name', $userName)->exists()) {
                throw new RuntimeException("Thiếu tài khoản {$userName}; hãy seed Auth trước khi seed dữ liệu nghiệp vụ.");
            }
        }
    }

    private function clearBusinessData(): void
    {
        foreach ([
            'attachments', 'quotation_response_tokens', 'payroll_item_details', 'driver_cash_remittances',
            'driver_attendances', 'driver_advances', 'payroll_items', 'payrolls', 'expenses',
            'partner_payments', 'receipts', 'dispatch_orders', 'trip_assignments', 'trip_schedules',
            'contract_schedule_days', 'contract_schedule_rules', 'contract_items', 'contracts',
            'quotation_items', 'quotations', 'rental_request_items', 'rental_requests', 'route_rates',
            'drivers', 'vehicles', 'routes', 'expense_types', 'vehicle_types', 'partners', 'customers',
        ] as $table) {
            DB::table($table)->delete();
        }
    }

    private function seedBusinessData(): void
    {
        $customers = $this->seedCustomers();
        $partners = $this->seedPartners();
        $vehicleTypes = $this->seedVehicleTypes();
        $vehicles = $this->seedVehicles($vehicleTypes, $partners);
        $drivers = $this->seedDrivers($partners);
        $routes = $this->seedRoutes($customers);
        $this->seedRouteRates($routes, $vehicleTypes);
        $expenseTypes = $this->seedExpenseTypes();
        $requests = $this->seedRentalRequests($customers, $vehicleTypes, $routes);
        $quotations = $this->seedQuotations($requests, $customers, $vehicleTypes, $routes);
        [$contracts, $contractItems] = $this->seedContracts($customers, $requests, $quotations, $vehicleTypes, $routes);
        $rules = $this->seedScheduleRules($contractItems, $routes, $vehicles, $drivers);
        $schedules = $this->seedSchedules($contracts, $contractItems, $rules, $routes, $vehicleTypes);
        $assignments = $this->seedAssignments($schedules, $vehicles, $drivers, $partners);
        $orders = $this->seedDispatchOrders($schedules, $assignments);
        $receipts = $this->seedReceipts($customers, $contracts, $orders, $drivers);
        $this->seedExpenses($expenseTypes, $vehicles, $orders, $partners, $drivers);
        $this->seedPartnerPayments($partners, $orders);
        $this->seedRemittances($receipts, $orders, $drivers);
        $attendances = $this->seedAttendances($drivers, $orders);
        $this->seedPayroll($drivers, $attendances, $orders);
        $this->seedAdvances($drivers);
    }

    /** @return list<int> */
    private function seedCustomers(): array
    {
        $ids = [];
        foreach (['Công ty FPT Hải Phòng', 'Công ty Du lịch Hạ Long', 'Công ty VSIP', 'Nguyễn Thị Thu', 'Công ty Sự kiện Việt'] as $index => $name) {
            $ids[] = $this->insert('customers', ['code' => 'KH'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT), 'type' => $index < 3 || $index === 4 ? 'company' : 'individual', 'name' => $name, 'phone' => '09010000'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT), 'email' => null, 'tax_code' => $index === 3 ? null : '02000000'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT), 'cccd' => $index === 3 ? '001200000004' : null, 'address' => 'Hải Phòng', 'contact_name' => $name, 'opening_balance' => 0]);
        }

        return $ids;
    }

    /** @return list<int> */
    private function seedPartners(): array
    {
        $types = ['transport_company', 'vehicle_owner', 'garage', 'fuel_supplier', 'other'];
        $ids = [];
        foreach ($types as $index => $type) {
            $ids[] = $this->insert('partners', ['code' => 'DT'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT), 'type' => $type, 'name' => ['Vận tải Minh Phát', 'Chủ xe Thành Công', 'Garage Thành Đạt', 'Nhiên liệu Hải Phòng', 'Dịch vụ An Phú'][$index], 'phone' => '09020000'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT), 'email' => null, 'tax_code' => '02010000'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT), 'address' => 'Hải Phòng', 'bank_name' => 'Vietcombank', 'bank_account' => '00110000000'.($index + 1), 'opening_balance' => 0]);
        }

        return $ids;
    }

    /** @return list<int> */
    private function seedVehicleTypes(): array
    {
        $ids = [];
        foreach ([[4, 10], [7, 10], [16, 12.5], [29, 15], [45, 15]] as $index => [$seats, $rate]) {
            $ids[] = $this->insert('vehicle_types', ['code' => 'XE'.$seats, 'name' => "Xe {$seats} chỗ", 'seats' => $seats, 'tour_driver_commission_rate' => $rate]);
        }

        return $ids;
    }

    /** @param list<int> $types @param list<int> $partners @return list<int> */
    private function seedVehicles(array $types, array $partners): array
    {
        $ids = [];
        foreach (range(1, 5) as $index) {
            $ids[] = $this->insert('vehicles', ['license_plate' => '15E-0'.str_pad((string) $index, 4, '0', STR_PAD_LEFT), 'vehicle_type_id' => $types[$index - 1], 'ownership_type' => $index === 4 ? 'partner' : 'company', 'partner_id' => $index === 4 ? $partners[1] : null, 'brand' => $index === 5 ? 'Hyundai' : 'Ford', 'model' => $index === 5 ? 'Solati' : 'Transit', 'manufacture_year' => 2021 + $index, 'current_odometer' => 10000 * $index, 'vehicle_status' => $index === 5 ? 'maintenance' : 'available', 'notes' => $index === 5 ? 'Bảo dưỡng định kỳ' : 'Đủ điều kiện vận hành']);
        }

        return $ids;
    }

    /** @param list<int> $partners @return list<int> */
    private function seedDrivers(array $partners): array
    {
        $ids = [];
        foreach (range(1, 5) as $index) {
            $ids[] = $this->insert('drivers', ['code' => 'LX'.str_pad((string) $index, 4, '0', STR_PAD_LEFT), 'user_name' => $index === 1 ? 'driver' : null, 'partner_id' => $index === 4 ? $partners[0] : null, 'type' => $index === 4 ? 'partner' : 'company', 'full_name' => ['Nguyễn Văn Hà', 'Trần Văn Cường', 'Hoàng Văn Tú', 'Vũ Anh Tuấn', 'Phạm Minh Đức'][$index - 1], 'phone' => '09030000'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'cccd' => '00130000000'.$index, 'license_number' => 'GPLX-HP-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT), 'license_class' => 'D', 'license_issued_at' => $this->now->copy()->subYears(3)->toDateString(), 'license_expired_at' => $this->now->copy()->addYears(3)->toDateString(), 'base_salary' => 0, 'responsibility_allowance' => 500000, 'joined_at' => $this->now->copy()->subYears(2)->toDateString()]);
        }

        return $ids;
    }

    /** @param list<int> $customers @return list<int> */
    private function seedRoutes(array $customers): array
    {
        $names = [['Nhà xe → Nhà máy', '06:30', '17:30'], ['Hải Phòng → Hạ Long', '07:00', '18:00'], ['VSIP → Thủy Nguyên', '06:00', '17:00'], ['Nội thành Hải Phòng', '08:00', '16:00'], ['Hải Phòng → Hà Nội', '05:30', '20:00']];
        $ids = [];
        foreach ($names as $index => [$name, $pickup, $return]) {
            $ids[] = $this->insert('routes', ['code' => 'T'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT), 'customer_id' => $customers[$index], 'name' => $name, 'shift_name' => 'Ca ngày', 'pickup_location' => explode(' → ', $name)[0], 'dropoff_location' => explode(' → ', $name)[1] ?? 'Điểm trả', 'default_pickup_time' => $pickup, 'default_return_time' => $return, 'estimated_distance_km' => 20 + $index * 15]);
        }

        return $ids;
    }

    /** @param list<int> $routes @param list<int> $types */
    private function seedRouteRates(array $routes, array $types): void
    {
        foreach (range(0, 4) as $index) {
            $this->insert('route_rates', ['route_id' => $routes[$index], 'vehicle_type_id' => $types[$index], 'customer_price' => 2000000 + $index * 500000, 'driver_wage' => 200000 + $index * 25000, 'effective_from' => $this->now->copy()->startOfMonth()->toDateString(), 'effective_to' => null]);
        }
    }

    /** @return list<int> */
    private function seedExpenseTypes(): array
    {
        $ids = [];
        foreach ([['FUEL', 'Xăng dầu', 'vehicle'], ['REPAIR', 'Sửa chữa', 'vehicle'], ['TOLL', 'Phí cầu đường', 'trip'], ['MEAL', 'Ăn ca', 'trip'], ['OFFICE', 'Chi phí chung', 'general']] as [$code, $name, $scope]) {
            $ids[] = $this->insert('expense_types', ['code' => $code, 'name' => $name, 'scope' => $scope]);
        }

        return $ids;
    }

    /** @param list<int> $customers @param list<int> $types @param list<int> $routes @return list<int> */
    private function seedRentalRequests(array $customers, array $types, array $routes): array
    {
        $statuses = ['new', 'quoted', 'accepted', 'rejected', 'converted'];
        $ids = [];
        foreach ($statuses as $index => $status) {
            $service = $index % 2 === 0 ? 'business' : 'tourism';
            $ids[] = $this->insert('rental_requests', ['request_no' => 'YC'.$this->now->format('Y').str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT), 'customer_id' => $customers[$index], 'source' => 'direct', 'requested_at' => $this->now->copy()->subDays(10 - $index), 'service_type' => $service, 'pickup_location' => 'Trụ sở khách hàng', 'dropoff_location' => 'Điểm đến theo hợp đồng', 'start_at' => $this->now->copy()->addDays($index - 2)->startOfHour(), 'end_at' => $this->now->copy()->addDays($index - 2)->startOfHour()->addHours(8), 'note' => $service === 'business' ? 'Phục vụ đưa đón công nhân' : 'Phục vụ chương trình du lịch', 'status' => $status]);
            $this->insert('rental_request_items', ['rental_request_id' => $ids[$index], 'vehicle_type_id' => $types[$index], 'quantity' => 1, 'route_id' => $routes[$index], 'note' => null]);
        }

        return $ids;
    }

    /** @param list<int> $requests @param list<int> $customers @param list<int> $types @param list<int> $routes @return list<int> */
    private function seedQuotations(array $requests, array $customers, array $types, array $routes): array
    {
        $statuses = ['draft', 'sent', 'approved', 'rejected', 'expired'];
        $ids = [];
        foreach ($statuses as $index => $status) {
            $amount = 3000000 + $index * 500000;
            $ids[] = $this->insert('quotations', ['quotation_no' => 'BG'.$this->now->format('Y').str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT), 'rental_request_id' => $requests[$index], 'customer_id' => $customers[$index], 'quotation_date' => $this->now->copy()->subDays(8)->toDateString(), 'valid_until' => $this->now->copy()->addDays($index === 4 ? -1 : 7)->toDateString(), 'subtotal' => $amount, 'discount_amount' => 0, 'total_amount' => $amount, 'payment_terms' => 'Thanh toán chuyển khoản', 'status' => $status, 'approved_by' => $status === 'approved' ? 'admin' : null, 'approved_at' => $status === 'approved' ? $this->now->copy()->subDays(5) : null]);
            $this->insert('quotation_items', ['quotation_id' => $ids[$index], 'route_id' => $routes[$index], 'vehicle_type_id' => $types[$index], 'description' => 'Dịch vụ vận chuyển theo yêu cầu', 'quantity' => 1, 'unit_price' => $amount, 'amount' => $amount]);
        }

        return $ids;
    }

    /** @param list<int> $customers @param list<int> $requests @param list<int> $quotations @param list<int> $types @param list<int> $routes @return array{list<int>, list<int>} */
    private function seedContracts(array $customers, array $requests, array $quotations, array $types, array $routes): array
    {
        $statuses = ['active', 'active', 'completed', 'draft', 'cancelled'];
        $contracts = [];
        $items = [];
        foreach ($statuses as $index => $status) {
            $service = $index % 2 === 0 ? 'business' : 'tourism';
            $amount = 5000000 + $index * 1000000;
            $contracts[] = $this->insert('contracts', ['contract_no' => 'HD'.$this->now->format('Y').str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT), 'customer_id' => $customers[$index], 'rental_request_id' => $requests[$index], 'quotation_id' => $quotations[$index], 'contract_type' => $index < 3 ? 'trip' : 'principle', 'signed_date' => $this->now->copy()->subDays(8)->toDateString(), 'effective_from' => $this->now->copy()->subDays(5)->toDateString(), 'effective_to' => $this->now->copy()->addDays(10)->toDateString(), 'total_amount' => $amount, 'deposit_required' => 0, 'payment_terms' => 'Thanh toán sau chuyến', 'terms' => 'Các bên thực hiện theo thỏa thuận trong hợp đồng.', 'status' => $status]);
            $items[] = $this->insert('contract_items', ['contract_id' => $contracts[$index], 'route_id' => $routes[$index], 'vehicle_type_id' => $types[$index], 'service_type' => $service, 'quantity' => 1, 'unit_price' => $amount, 'driver_wage' => 250000, 'pickup_location' => 'Trụ sở khách hàng', 'dropoff_location' => 'Điểm đến theo hợp đồng', 'note' => null]);
        }

        return [$contracts, $items];
    }

    /** @param list<int> $items @param list<int> $routes @param list<int> $vehicles @param list<int> $drivers @return list<int> */
    private function seedScheduleRules(array $items, array $routes, array $vehicles, array $drivers): array
    {
        $ids = [];
        foreach (range(0, 4) as $index) {
            $ids[] = $this->insert('contract_schedule_rules', ['contract_item_id' => $items[$index], 'route_id' => $routes[$index], 'effective_from' => $this->now->copy()->subDays(5)->toDateString(), 'effective_to' => $this->now->copy()->addDays(20)->toDateString(), 'default_vehicle_id' => $vehicles[$index], 'default_driver_id' => $drivers[$index], 'note' => 'Lịch vận hành đã thỏa thuận']);
            $this->insert('contract_schedule_days', ['schedule_rule_id' => $ids[$index], 'weekday' => 'Mon', 'pickup_time' => '07:00', 'return_time' => '17:00', 'shift_name' => 'Ca sáng']);
        }

        return $ids;
    }

    /** @param list<int> $contracts @param list<int> $items @param list<int> $rules @param list<int> $routes @param list<int> $types @return list<int> */
    private function seedSchedules(array $contracts, array $items, array $rules, array $routes, array $types): array
    {
        $statuses = ['COMPLETED', 'COMPLETED', 'ASSIGNED', 'PLANNED', 'CANCELLED'];
        $ids = [];
        foreach ($statuses as $index => $status) {
            $start = $index < 2 ? $this->now->copy()->subDays($index + 1)->setTime(7, 0) : $this->now->copy()->addDays($index)->setTime(7, 0);
            $ids[] = $this->insert('trip_schedules', ['schedule_no' => 'LT'.$this->now->format('Ymd').str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT), 'contract_id' => $contracts[$index], 'contract_item_id' => $items[$index], 'schedule_rule_id' => $rules[$index], 'service_type' => $index % 2 === 0 ? 'business' : 'tourism', 'route_id' => $routes[$index], 'scheduled_start_at' => $start, 'scheduled_end_at' => $start->copy()->addHours(8), 'pickup_location' => 'Trụ sở khách hàng', 'dropoff_location' => 'Điểm đến theo hợp đồng', 'journey' => $index % 2 === 0 ? 'Đưa đón công nhân theo tuyến' : 'Chương trình du lịch theo hợp đồng', 'required_vehicle_type_id' => $types[$index], 'status' => $status, 'note' => null]);
        }

        return $ids;
    }

    /** @param list<int> $schedules @param list<int> $vehicles @param list<int> $drivers @param list<int> $partners @return list<int> */
    private function seedAssignments(array $schedules, array $vehicles, array $drivers, array $partners): array
    {
        $ids = [];
        foreach (range(0, 4) as $index) {
            $ids[] = $this->insert('trip_assignments', ['trip_schedule_id' => $schedules[$index], 'vehicle_id' => $vehicles[$index], 'driver_id' => $drivers[$index], 'partner_id' => $index === 3 ? $partners[1] : null, 'assignment_type' => 'PRIMARY', 'replaced_assignment_id' => null, 'replace_reason' => null, 'assigned_at' => $this->now->copy()->subDays(2), 'assigned_by' => 'admin', 'is_current' => true]);
        }

        return $ids;
    }

    /** @param list<int> $schedules @param list<int> $assignments @return list<int> */
    private function seedDispatchOrders(array $schedules, array $assignments): array
    {
        $statuses = ['COMPLETED', 'COMPLETED', 'ASSIGNED', 'ISSUED', 'CANCELLED'];
        $ids = [];
        foreach ($statuses as $index => $status) {
            // Hour offsets deliberately keep both completed services within this calendar month,
            // including when the seeder is run on the first day of a month.
            $completed = $status === 'COMPLETED' ? $this->now->copy()->subHours($index + 2) : null;
            $ids[] = $this->insert('dispatch_orders', ['order_no' => 'LDX'.$this->now->format('Ymd').str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT), 'trip_schedule_id' => $schedules[$index], 'trip_assignment_id' => $assignments[$index], 'issued_at' => $this->now->copy()->subDays(3), 'issued_by' => 'admin', 'actual_start_at' => $completed?->copy()->subHours(8), 'actual_end_at' => $completed, 'start_odometer' => 10000 + $index * 100, 'end_odometer' => 10050 + $index * 100, 'actual_distance_km' => 50, 'waiting_hours' => 1, 'customer_amount' => 5000000 + $index * 500000, 'partner_vehicle_cost' => $index === 1 ? 800000 : 0, 'external_driver_cost' => 0, 'status' => $status, 'completed_at' => $completed, 'reported_at' => $completed, 'reported_by' => $completed ? 'driver' : null, 'confirmed_at' => $completed, 'confirmed_by' => $completed ? 'admin' : null, 'review_note' => null, 'note' => 'Lệnh điều xe theo kế hoạch vận hành']);
        }

        return $ids;
    }

    /** @param list<int> $customers @param list<int> $contracts @param list<int> $orders @param list<int> $drivers @return list<int> */
    private function seedReceipts(array $customers, array $contracts, array $orders, array $drivers): array
    {
        $ids = [];
        foreach (range(0, 4) as $index) {
            $direct = $index === 0;
            $ids[] = $this->insert('receipts', ['receipt_no' => 'PT'.$this->now->format('Y').str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT), 'customer_id' => $customers[$index], 'contract_id' => $contracts[$index], 'receipt_type' => $index === 0 ? 'contract_payment' : 'deposit', 'received_at' => $this->now->copy()->subDays($index + 1), 'amount' => 1000000 + $index * 100000, 'payment_method' => $index % 2 ? 'bank_transfer' : 'cash', 'payer_name' => 'Khách hàng', 'description' => 'Thanh toán theo hợp đồng', 'is_locked' => false, 'settlement_type' => $direct ? 'driver_direct_collection' : 'standard', 'dispatch_order_id' => $index < 2 ? $orders[$index] : null, 'driver_id' => $direct ? $drivers[0] : null]);
        }

        return $ids;
    }

    /** @param list<int> $types @param list<int> $vehicles @param list<int> $orders @param list<int> $partners @param list<int> $drivers */
    private function seedExpenses(array $types, array $vehicles, array $orders, array $partners, array $drivers): void
    {
        foreach (range(0, 4) as $index) {
            $this->insert('expenses', ['expense_no' => 'PC'.$this->now->format('Y').str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT), 'expense_type_id' => $types[$index], 'scope' => ['vehicle', 'vehicle', 'trip', 'trip', 'general'][$index], 'vehicle_id' => $index < 2 ? $vehicles[$index] : null, 'dispatch_order_id' => $index === 2 ? $orders[0] : null, 'partner_id' => $index === 1 ? $partners[2] : null, 'driver_id' => $index === 3 ? $drivers[0] : null, 'expense_date' => $this->now->copy()->subDays($index + 1), 'amount' => 100000 + $index * 50000, 'payment_method' => 'cash', 'document_no' => 'CT-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT), 'description' => 'Chi phí phục vụ vận hành', 'is_locked' => false, 'settlement_type' => $index === 3 ? 'driver_reimbursement' : 'standard']);
        }
    }

    /** @param list<int> $partners @param list<int> $orders */
    private function seedPartnerPayments(array $partners, array $orders): void
    {
        foreach (range(0, 4) as $index) {
            $this->insert('partner_payments', ['payment_no' => 'CTDT'.$this->now->format('Y').str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT), 'partner_id' => $partners[$index], 'dispatch_order_id' => $index < 2 ? $orders[$index] : null, 'paid_at' => $this->now->copy()->subDays($index + 1), 'amount' => 300000 + $index * 100000, 'payment_method' => 'bank_transfer', 'description' => 'Thanh toán dịch vụ đối tác', 'is_locked' => false]);
        }
    }

    /** @param list<int> $receipts @param list<int> $orders @param list<int> $drivers */
    private function seedRemittances(array $receipts, array $orders, array $drivers): void
    {
        $this->insert('driver_cash_remittances', ['remittance_no' => 'NTLX'.$this->now->format('Y').str_pad('1', 4, '0', STR_PAD_LEFT), 'receipt_id' => $receipts[0], 'dispatch_order_id' => $orders[0], 'driver_id' => $drivers[0], 'remitted_at' => $this->now->copy()->subDay(), 'amount' => 500000, 'payment_method' => 'cash', 'description' => 'Tài xế nộp tiền cước', 'is_locked' => false]);
    }

    /** @param list<int> $drivers @param list<int> $orders @return list<int> */
    private function seedAttendances(array $drivers, array $orders): array
    {
        $ids = [];
        foreach (range(0, 4) as $index) {
            $ids[] = $this->insert('driver_attendances', ['driver_id' => $drivers[$index], 'dispatch_order_id' => $orders[$index], 'work_date' => $this->now->copy()->subDays(min($index + 1, 2))->toDateString(), 'work_type' => $index % 2 ? 'tourism_trip' : 'fixed_trip', 'work_units' => 1, 'base_amount' => 1, 'rate' => 250000, 'calculated_wage' => 250000, 'status' => 'confirmed']);
        }

        return $ids;
    }

    /** @param list<int> $drivers @param list<int> $attendances @param list<int> $orders */
    private function seedPayroll(array $drivers, array $attendances, array $orders): void
    {
        $payroll = $this->insert('payrolls', ['code' => 'LUONG-'.$this->now->format('Y-m'), 'month' => $this->now->month, 'year' => $this->now->year, 'from_date' => $this->now->copy()->startOfMonth()->toDateString(), 'to_date' => $this->now->copy()->endOfMonth()->toDateString(), 'status' => 'calculated', 'approved_by' => null, 'approved_at' => null, 'paid_at' => null]);
        foreach ($drivers as $index => $driver) {
            $item = $this->insert('payroll_items', ['payroll_id' => $payroll, 'driver_id' => $driver, 'base_salary' => 0, 'responsibility_allowance' => 500000, 'meal_allowance' => 0, 'fixed_trip_wage' => $index % 2 === 0 ? 250000 : 0, 'tourism_commission' => $index % 2 ? 250000 : 0, 'other_allowance' => 0, 'advance_amount' => 0, 'deduction_amount' => 0, 'direct_customer_collection' => 0, 'driver_reimbursement' => 0, 'driver_remittance_amount' => 0, 'gross_salary' => 750000, 'net_salary' => 750000, 'note' => 'Lương kỳ hiện tại']);
            $this->insert('payroll_item_details', ['payroll_item_id' => $item, 'driver_attendance_id' => $attendances[$index], 'dispatch_order_id' => $orders[$index], 'receipt_id' => null, 'expense_id' => null, 'driver_cash_remittance_id' => null, 'calculation_type' => $index % 2 ? 'tourism_commission' : 'fixed_trip', 'base_amount' => 1, 'rate' => 250000, 'amount' => 250000, 'description' => 'Tiền công chuyến']);
        }
    }

    /** @param list<int> $drivers */
    private function seedAdvances(array $drivers): void
    {
        foreach ($drivers as $index => $driver) {
            $this->insert('driver_advances', ['advance_no' => 'TU'.$this->now->format('Y').str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT), 'driver_id' => $driver, 'advance_date' => $this->now->copy()->subDays($index + 1)->toDateString(), 'amount' => 100000 + $index * 50000, 'description' => 'Tạm ứng chi phí công tác', 'status' => 'confirmed', 'approved_by' => 'admin', 'payroll_id' => null]);
        }
    }

    /** @param array<string, mixed> $values */
    private function insert(string $table, array $values): int
    {
        return (int) DB::table($table)->insertGetId([
            ...$this->metadata(),
            ...$values,
        ]);
    }

    /** @return array<string, mixed> */
    private function metadata(): array
    {
        return ['is_active' => true, 'user_name_created' => 'admin', 'user_name_updated' => 'admin', 'created_at' => $this->now, 'updated_at' => $this->now];
    }
}
