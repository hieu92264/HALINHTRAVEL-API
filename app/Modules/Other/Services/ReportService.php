<?php

namespace App\Modules\Other\Services;

use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\DriverPayroll\Models\DriverAdvance;
use App\Modules\DriverPayroll\Models\Payroll;
use App\Modules\DriverPayroll\Models\PayrollItem;
use App\Modules\DriverPayroll\Models\PayrollItemDetail;
use App\Modules\Finance\Models\DriverCashRemittance;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\Other\Interfaces\ReportServiceInterface;
use App\Shared\Enums\DispatchOrderStatusEnum;
use App\Shared\Enums\ExpenseSettlementTypeEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ReportService implements ReportServiceInterface
{
    public function fixedTrips(array $filters): array
    {
        $orders = $this->tripOrders($filters, ['fixed', 'school'])->get()->map(fn(DispatchOrder $order) => $this->tripRow($order));

        return $this->result($filters, $orders->all(), [
            'trips' => $orders->count(),
            'completed_trips' => $orders->where('status', DispatchOrderStatusEnum::COMPLETED->value)->count(),
            'revenue' => $this->money($orders->sum('revenue')),
            'distance_km' => $this->money($orders->sum('actual_distance_km')),
        ]);
    }

    public function tourismTrips(array $filters): array
    {
        /** @var EloquentCollection<int, DispatchOrder> $sourceOrders */
        $sourceOrders = $this->tripOrders($filters, ['tourism', 'business'])->get();
        $orderIds = $sourceOrders->pluck('id');
        /** @var Collection<int, Collection<int, Receipt>> $receipts */
        $receipts = Receipt::query()->where('is_active', true)->whereIn('dispatch_order_id', $orderIds)->get()->groupBy('dispatch_order_id');
        /** @var Collection<int, Collection<int, DriverCashRemittance>> $remittances */
        $remittances = DriverCashRemittance::query()->where('is_active', true)->whereIn('dispatch_order_id', $orderIds)->get()->groupBy('dispatch_order_id');
        $orders = $sourceOrders->map(function (DispatchOrder $order) use ($receipts, $remittances): array {
            $row = $this->tripRow($order);
            $expenses = $order->expenses->sum('amount');
            $partnerPayments = $order->partnerPayments->sum('amount');
            $row['expense_amount'] = $this->money($expenses + $partnerPayments);
            $row['expense_breakdown'] = ['expenses' => $this->money($expenses), 'partner_payments' => $this->money($partnerPayments)];
            /** @var Collection<int, Receipt> $orderReceipts */
            $orderReceipts = $receipts->get($order->id, collect());
            $row['driver_collected_amount'] = $this->money($orderReceipts
                ->filter(fn(Receipt $receipt): bool => $receipt->settlement_type?->value === 'driver_direct_collection')
                ->sum('amount'));
            $row['company_collected_amount'] = $this->money($orderReceipts
                ->filter(fn(Receipt $receipt): bool => $receipt->settlement_type?->value === 'standard')
                ->sum('amount'));
            /** @var Collection<int, DriverCashRemittance> $orderRemittances */
            $orderRemittances = $remittances->get($order->id, collect());
            $row['driver_remitted_amount'] = $this->money($orderRemittances->sum('amount'));
            $row['remaining_receivable'] = $this->money((float) $order->customer_amount - (float) $row['driver_collected_amount'] - (float) $row['company_collected_amount']);

            return $row;
        });

        return $this->result($filters, $orders->all(), [
            'trips' => $orders->count(),
            'revenue' => $this->money($orders->sum('revenue')),
            'expense_amount' => $this->money($orders->sum('expense_amount')),
        ]);
    }

    public function cashflow(array $filters): array
    {
        [$from, $to] = $this->dates($filters);
        /** @var list<array<string, mixed>> $records */
        $records = [];

        foreach (Receipt::query()->where('is_active', true)->where('settlement_type', 'standard')->whereBetween('received_at', [$from, $to])->get() as $item) {
            /** @var Receipt $item */
            $records[] = $this->cashRow($item->received_at, 'receipt', 'Thu', $item->receipt_no, $item->amount, $item->payment_method?->value, 1);
        }
        foreach (DriverCashRemittance::query()->where('is_active', true)->whereBetween('remitted_at', [$from, $to])->get() as $item) {
            /** @var DriverCashRemittance $item */
            $records[] = $this->cashRow($item->remitted_at, 'driver_remittance', 'Lái xe nộp tiền', $item->remittance_no, $item->amount, $item->payment_method?->value, 1);
        }
        foreach (Expense::query()->where('is_active', true)->whereBetween('expense_date', [$from, $to])->when($filters['payment_method'] ?? null, fn (Builder $q, string $value) => $q->where('payment_method', $value))->get() as $item) {
            /** @var Expense $item */
            $records[] = $this->cashRow($item->expense_date, 'expense', 'Chi phí', $item->expense_no, $item->amount, $item->payment_method?->value, -1);
        }
        foreach (PartnerPayment::query()->where('is_active', true)->whereBetween('paid_at', [$from, $to])->get() as $item) {
            /** @var PartnerPayment $item */
            $records[] = $this->cashRow($item->paid_at, 'partner_payment', 'Chi đối tác', $item->payment_no, $item->amount, $item->payment_method?->value, -1);
        }
        foreach (DriverAdvance::query()->where('is_active', true)->whereBetween('advance_date', [$from, $to])->get() as $item) {
            /** @var DriverAdvance $item */
            $records[] = $this->cashRow($item->advance_date, 'driver_advance', 'Tạm ứng', $item->advance_no, $item->amount, null, -1);
        }

        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = collect($records)->sortBy('date')->values();

        return $this->result($filters, $rows->all(), [
            'inflow' => $this->money($rows->where('direction', 'in')->sum('amount')),
            'outflow' => $this->money($rows->where('direction', 'out')->sum('amount')),
            'net_cash' => $this->money($rows->sum('signed_amount')),
        ]);
    }

    public function expenses(array $filters): array
    {
        [$from, $to] = $this->dates($filters);
        /** @var EloquentCollection<int, Expense> $expenses */
        $expenses = Expense::query()->with(['expenseType:id,name', 'vehicle:id,license_plate', 'partner:id,name', 'driver:id,full_name', 'dispatchOrder:id,order_no'])
            ->where('is_active', true)
            ->whereBetween('expense_date', [$from, $to])
            ->when($filters['expense_type_id'] ?? null, fn(Builder $q, int $id) => $q->where('expense_type_id', $id))
            ->when($filters['scope'] ?? null, fn(Builder $q, string $scope) => $q->where('scope', $scope))
            ->when($filters['vehicle_id'] ?? null, fn(Builder $q, int $id) => $q->where('vehicle_id', $id))
            ->when($filters['partner_id'] ?? null, fn (Builder $q, int $id) => $q->where('partner_id', $id))
            ->get();
        $rows = $expenses->map(fn (Expense $item) => ['id' => $item->id, 'expense_no' => $item->expense_no, 'date' => $item->expense_date?->toDateString(), 'expense_type' => $item->expenseType?->name, 'scope' => $item->scope?->value, 'vehicle_plate' => $item->vehicle?->license_plate, 'partner_name' => $item->partner?->name, 'driver_name' => $item->driver?->full_name, 'order_no' => $item->dispatchOrder?->order_no, 'amount' => $item->amount, 'payment_method' => $item->payment_method?->value, 'document_no' => $item->document_no, 'description' => $item->description]);

        return $this->result($filters, $rows->all(), ['expense_amount' => $this->money($rows->sum('amount')), 'count' => $rows->count()]);
    }

    public function payroll(array $filters): array
    {
        [$from, $to] = $this->dates($filters);
        /** @var EloquentCollection<int, Payroll> $payrolls */
        $payrolls = Payroll::query()->with(['items.driver', 'items.details.dispatchOrder'])
            ->where('is_active', true)
            ->whereBetween('to_date', [$from, $to])
            ->when($filters['status'] ?? null, fn(Builder $q, string $status) => $q->where('status', $status))
            ->get();
        $rows = $payrolls->flatMap(fn (Payroll $payroll) => $payroll->items->filter(fn (PayrollItem $item) => ! isset($filters['driver_id']) || $item->driver_id === (int) $filters['driver_id'])->map(fn (PayrollItem $item) => ['id' => $item->id, 'payroll_id' => $payroll->id, 'payroll_code' => $payroll->code, 'period' => $payroll->from_date?->toDateString() . ' - ' . $payroll->to_date?->toDateString(), 'status' => $payroll->status?->value, 'paid_at' => $payroll->paid_at?->toDateString(), 'driver_id' => $item->driver_id, 'driver_name' => $item->driver?->full_name, 'responsibility_allowance' => $item->responsibility_allowance, 'fixed_trip_wage' => $item->fixed_trip_wage, 'tourism_commission' => $item->tourism_commission, 'driver_reimbursement' => $item->driver_reimbursement, 'direct_customer_collection' => $item->direct_customer_collection, 'driver_remittance_amount' => $item->driver_remittance_amount, 'gross_salary' => $item->gross_salary, 'net_salary' => $item->net_salary, 'advance_amount' => $item->advance_amount, 'deduction_amount' => $item->deduction_amount, 'details' => $item->details->map(fn (PayrollItemDetail $detail) => ['order_no' => $detail->dispatchOrder?->order_no, 'amount' => $detail->amount, 'calculation_type' => $detail->calculation_type?->value])->all()]));

        return $this->result($filters, $rows->values()->all(), ['gross_salary' => $this->money($rows->sum('gross_salary')), 'net_salary' => $this->money($rows->sum('net_salary')), 'advance_amount' => $this->money($rows->sum('advance_amount'))]);
    }

    public function profitLoss(array $filters): array
    {
        [$from, $to] = $this->dates($filters);
        $basis = $filters['basis'] ?? 'accrual';
        /** @var Collection<int, array<string, string>> $months */
        $months = collect();
        for ($cursor = CarbonImmutable::parse($from)->startOfMonth(); $cursor->lte(CarbonImmutable::parse($to)->startOfMonth()); $cursor = $cursor->addMonth()) {
            $start = $cursor->startOfMonth();
            $end = $cursor->endOfMonth();
            $revenue = $basis === 'cash'
                ? Receipt::query()->where('is_active', true)->whereBetween('received_at', [$start, $end])->sum('amount')
                : DispatchOrder::query()->where('is_active', true)->where('status', DispatchOrderStatusEnum::COMPLETED->value)->whereBetween('completed_at', [$start, $end])->sum('customer_amount');
            $expenses = Expense::query()->where('is_active', true)->whereBetween('expense_date', [$start, $end])->sum('amount');
            $partnerPayments = PartnerPayment::query()->where('is_active', true)->whereBetween('paid_at', [$start, $end])->sum('amount');
            $payroll = Payroll::query()->where('is_active', true)->when($basis === 'cash', fn(Builder $q) => $q->whereBetween('paid_at', [$start, $end]), fn(Builder $q) => $q->whereBetween('to_date', [$start, $end]))->with('items')->get()->sum(fn(Payroll $item) => $item->items->sum($basis === 'cash' ? 'net_salary' : 'gross_salary'));
            $cost = $expenses + $partnerPayments + $payroll;
            $months->push(['month' => $cursor->format('Y-m'), 'revenue' => $this->money($revenue), 'expenses' => $this->money($expenses), 'partner_payments' => $this->money($partnerPayments), 'payroll' => $this->money($payroll), 'profit' => $this->money($revenue - $cost)]);
        }

        return $this->result([...$filters, 'basis' => $basis], $months->all(), ['revenue' => $this->money($months->sum('revenue')), 'cost' => $this->money($months->sum(fn($row) => (float) $row['expenses'] + (float) $row['partner_payments'] + (float) $row['payroll'])), 'profit' => $this->money($months->sum('profit'))], $months->all());
    }

    public function vehicleProfit(array $filters): array
    {
        [$from, $to] = $this->dates($filters);
        /** @var EloquentCollection<int, DispatchOrder> $orders */
        $orders = DispatchOrder::query()->with(['tripAssignment.vehicle.vehicleType', 'driverAttendance'])
            ->where('is_active', true)->where('status', DispatchOrderStatusEnum::COMPLETED->value)
            ->whereBetween('completed_at', [$from, $to])->get();
        $vehicleIds = $orders->pluck('tripAssignment.vehicle_id')->filter()->unique();
        $orderVehicleIds = $orders->mapWithKeys(fn(DispatchOrder $order) => [$order->id => $order->tripAssignment?->vehicle_id]);
        /** @var EloquentCollection<int, Expense> $expenses */
        $expenses = Expense::query()->with('expenseType')->where('is_active', true)->whereBetween('expense_date', [$from, $to])
            ->where('settlement_type', '!=', ExpenseSettlementTypeEnum::EXTERNAL_DRIVER_PAYMENT->value)->get();
        $general = $expenses->filter(fn(Expense $expense) => $expense->scope?->value === 'general')->sum('amount');
        $allocation = $vehicleIds->count() ? (float) $general / $vehicleIds->count() : 0;
        /** @var Collection<int, EloquentCollection<int, DispatchOrder>> $ordersByVehicle */
        $ordersByVehicle = $orders->groupBy(fn (DispatchOrder $order) => $order->tripAssignment?->vehicle_id);
        $rows = $ordersByVehicle->map(function (EloquentCollection $vehicleOrders, int|string $vehicleId) use ($expenses, $orderVehicleIds, $allocation): array {
            $vehicle = $vehicleOrders->first()->tripAssignment?->vehicle;
            $vehicleExpenses = $expenses->filter(fn(Expense $expense) => (int) $expense->vehicle_id === (int) $vehicleId || (int) $orderVehicleIds->get($expense->dispatch_order_id) === (int) $vehicleId);
            $byCategory = $vehicleExpenses->groupBy(fn(Expense $expense) => $expense->expenseType?->profit_category?->value ?? 'other');
            $revenue = (float) $vehicleOrders->sum('customer_amount');
            $fuel = (float) $byCategory->get('fuel', collect())->sum('amount');
            $repair = (float) $byCategory->get('repair', collect())->sum('amount');
            $insurance = (float) $byCategory->get('insurance_registration', collect())->sum('amount');
            $other = (float) $byCategory->get('other', collect())->sum('amount');
            $driverWage = (float) $vehicleOrders->sum(fn(DispatchOrder $order) => $order->driverAttendance?->calculated_wage ?? 0);
            $externalVehicle = (float) $vehicleOrders->sum('partner_vehicle_cost');
            $externalDriver = (float) $vehicleOrders->sum('external_driver_cost');
            $cost = $fuel + $repair + $insurance + $other + $driverWage + $allocation + $externalVehicle + $externalDriver;

            return ['vehicle_id' => $vehicleId, 'vehicle_plate' => $vehicle?->license_plate, 'vehicle_type' => $vehicle?->vehicleType?->name, 'revenue' => $this->money($revenue), 'fuel_cost' => $this->money($fuel), 'repair_cost' => $this->money($repair), 'insurance_registration_cost' => $this->money($insurance), 'other_cost' => $this->money($other), 'driver_wage' => $this->money($driverWage), 'general_cost_allocated' => $this->money($allocation), 'partner_vehicle_cost' => $this->money($externalVehicle), 'external_driver_cost' => $this->money($externalDriver), 'total_cost' => $this->money($cost), 'profit' => $this->money($revenue - $cost)];
        })->values();

        return $this->result($filters, $rows->all(), ['revenue' => $this->money($rows->sum('revenue')), 'cost' => $this->money($rows->sum('total_cost')), 'profit' => $this->money($rows->sum('profit'))]);
    }

    public function payrollDetails(array $filters): array
    {
        $payrollId = (int) $filters['payroll_id'];
        /** @var Payroll $payroll */
        $payroll = Payroll::query()->with(['items.driver', 'items.details.driverAttendance', 'items.details.dispatchOrder.tripSchedule', 'items.details.receipt', 'items.details.expense', 'items.details.driverCashRemittance'])
            ->whereKey($payrollId)
            ->firstOrFail();
        $items = $payroll->items->filter(fn (PayrollItem $item) => empty($filters['driver_id']) || $item->driver_id === (int) $filters['driver_id']);
        $rows = $items->flatMap(function (PayrollItem $item) {
            return $item->details->map(fn (PayrollItemDetail $detail) => [
                'driver_name' => $item->driver?->full_name,
                'calculation_type' => $detail->calculation_type?->value,
                'date' => $detail->driverAttendance?->work_date?->toDateString()
                    ?? $detail->receipt?->received_at?->toDateString()
                    ?? $detail->expense?->expense_date?->toDateString()
                    ?? $detail->driverCashRemittance?->remitted_at?->toDateString(),
                'order_no' => $detail->dispatchOrder?->order_no,
                'journey' => $detail->dispatchOrder?->tripSchedule?->journey,
                'receipt_no' => $detail->receipt?->receipt_no,
                'expense_no' => $detail->expense?->expense_no,
                'remittance_no' => $detail->driverCashRemittance?->remittance_no,
                'base_amount' => $detail->base_amount,
                'rate' => $detail->rate,
                'amount' => $detail->amount,
                'description' => $detail->description,
            ]);
        })->values();

        $result = $this->result($filters, $rows->all(), ['gross_salary' => $this->money($items->sum('gross_salary')), 'net_salary' => $this->money($items->sum('net_salary'))]);
        $result['payroll'] = [
            'id' => $payroll->id,
            'code' => $payroll->code,
            'from_date' => $payroll->from_date?->toDateString(),
            'to_date' => $payroll->to_date?->toDateString(),
        ];

        return $result;
    }

    /** @return Builder<DispatchOrder> */
    private function tripOrders(array $filters, array $types): Builder
    {
        [$from, $to] = $this->dates($filters);

        return DispatchOrder::query()->with(['tripSchedule.route:id,name', 'tripSchedule.contract.customer:id,name', 'tripAssignment.vehicle:id,license_plate,vehicle_type_id', 'tripAssignment.vehicle.vehicleType:id,name', 'tripAssignment.driver:id,full_name', 'tripAssignment.partner:id,name', 'expenses', 'partnerPayments', 'driverAttendance'])
            ->where('is_active', true)->where('status', DispatchOrderStatusEnum::COMPLETED->value)->whereBetween('completed_at', [$from, $to])
            ->whereHas('tripSchedule', function (Builder $query) use ($types, $filters): void {
                $query->whereIn('service_type', $types)
                    ->when($filters['route_id'] ?? null, fn(Builder $q, int $id) => $q->where('route_id', $id))
                    ->when($filters['customer_id'] ?? null, fn(Builder $q, int $id) => $q->whereHas('contract', fn(Builder $c) => $c->where('customer_id', $id)));
            })->when($filters['vehicle_id'] ?? null, fn(Builder $q, int $id) => $q->whereHas('tripAssignment', fn(Builder $a) => $a->where('vehicle_id', $id)))
            ->when($filters['driver_id'] ?? null, fn(Builder $q, int $id) => $q->whereHas('tripAssignment', fn(Builder $a) => $a->where('driver_id', $id)))
            ->when($filters['partner_id'] ?? null, fn(Builder $q, int $id) => $q->whereHas('tripAssignment', fn(Builder $a) => $a->where('partner_id', $id)));
    }

    private function tripRow(DispatchOrder $order): array
    {
        return ['id' => $order->id, 'schedule_id' => $order->trip_schedule_id, 'date' => $order->completed_at?->toDateString(), 'order_no' => $order->order_no, 'route_name' => $order->tripSchedule?->route?->name, 'journey' => $order->tripSchedule?->journey, 'pickup_time' => $order->tripSchedule?->scheduled_start_at?->format('H:i'), 'return_time' => $order->tripSchedule?->scheduled_end_at?->format('H:i'), 'customer_name' => $order->tripSchedule?->contract?->customer?->name, 'vehicle_plate' => $order->tripAssignment?->vehicle?->license_plate, 'driver_name' => $order->tripAssignment?->driver?->full_name, 'partner_name' => $order->tripAssignment?->partner?->name, 'driver_wage' => $this->money($order->driverAttendance?->calculated_wage ?? 0), 'partner_vehicle_cost' => $this->money($order->partner_vehicle_cost), 'external_driver_cost' => $this->money($order->external_driver_cost), 'external_vehicle_profit' => $this->money((float) $order->customer_amount - (float) $order->partner_vehicle_cost), 'actual_distance_km' => $order->actual_distance_km, 'revenue' => $order->customer_amount, 'status' => $order->status?->value];
    }

    private function cashRow(mixed $date, string $type, string $label, string $documentNo, mixed $amount, ?string $method, int $sign): array
    {
        return ['date' => $date?->toDateString(), 'type' => $type, 'label' => $label, 'document_no' => $documentNo, 'amount' => $this->money($amount), 'signed_amount' => $sign * (float) $amount, 'direction' => $sign > 0 ? 'in' : 'out', 'payment_method' => $method];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function dates(array $filters): array
    {
        return [CarbonImmutable::parse($filters['from_date'])->startOfDay(), CarbonImmutable::parse($filters['to_date'])->endOfDay()];
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function result(array $filters, array $rows, array $totals, ?array $chart = null): array
    {
        return ['filters' => $filters, 'generated_at' => now()->toISOString(), 'totals' => $totals, 'rows' => $rows, 'chart' => $chart];
    }
}
