<?php

namespace App\Modules\Other\Services;

use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\DriverPayroll\Models\DriverAdvance;
use App\Modules\DriverPayroll\Models\Payroll;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\Other\Interfaces\ReportServiceInterface;
use App\Shared\Enums\DispatchOrderStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ReportService implements ReportServiceInterface
{
    public function fixedTrips(array $filters): array
    {
        $orders = $this->tripOrders($filters, ['fixed', 'school'])->get()->map(fn (DispatchOrder $order) => $this->tripRow($order));

        return $this->result($filters, $orders->all(), [
            'trips' => $orders->count(),
            'completed_trips' => $orders->where('status', DispatchOrderStatusEnum::COMPLETED->value)->count(),
            'revenue' => $this->money($orders->sum('revenue')),
            'distance_km' => $this->money($orders->sum('actual_distance_km')),
        ]);
    }

    public function tourismTrips(array $filters): array
    {
        $orders = $this->tripOrders($filters, ['tourism', 'business'])->get()->map(function (DispatchOrder $order): array {
            $row = $this->tripRow($order);
            $expenses = $order->expenses->sum('amount');
            $partnerPayments = $order->partnerPayments->sum('amount');
            $row['expense_amount'] = $this->money($expenses + $partnerPayments);
            $row['expense_breakdown'] = ['expenses' => $this->money($expenses), 'partner_payments' => $this->money($partnerPayments)];

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
        $rows = collect()
            ->concat(Receipt::query()->whereBetween('received_at', [$from, $to])->get()->map(fn (Receipt $item) => $this->cashRow($item->received_at, 'receipt', 'Thu', $item->receipt_no, $item->amount, $item->payment_method?->value, 1)))
            ->concat(Expense::query()->whereBetween('expense_date', [$from, $to])->when($filters['payment_method'] ?? null, fn (Builder $q, string $value) => $q->where('payment_method', $value))->get()->map(fn (Expense $item) => $this->cashRow($item->expense_date, 'expense', 'Chi phí', $item->expense_no, $item->amount, $item->payment_method?->value, -1)))
            ->concat(PartnerPayment::query()->whereBetween('paid_at', [$from, $to])->get()->map(fn (PartnerPayment $item) => $this->cashRow($item->paid_at, 'partner_payment', 'Chi đối tác', $item->payment_no, $item->amount, $item->payment_method?->value, -1)))
            ->concat(DriverAdvance::query()->whereBetween('advance_date', [$from, $to])->get()->map(fn (DriverAdvance $item) => $this->cashRow($item->advance_date, 'driver_advance', 'Tạm ứng', $item->advance_no, $item->amount, null, -1)))
            ->sortBy('date')->values();

        return $this->result($filters, $rows->all(), [
            'inflow' => $this->money($rows->where('direction', 'in')->sum('amount')),
            'outflow' => $this->money($rows->where('direction', 'out')->sum('amount')),
            'net_cash' => $this->money($rows->sum('signed_amount')),
        ]);
    }

    public function expenses(array $filters): array
    {
        [$from, $to] = $this->dates($filters);
        $rows = Expense::query()->with(['expenseType:id,name', 'vehicle:id,license_plate', 'partner:id,name', 'driver:id,full_name', 'dispatchOrder:id,order_no'])
            ->whereBetween('expense_date', [$from, $to])
            ->when($filters['expense_type_id'] ?? null, fn (Builder $q, int $id) => $q->where('expense_type_id', $id))
            ->when($filters['scope'] ?? null, fn (Builder $q, string $scope) => $q->where('scope', $scope))
            ->when($filters['vehicle_id'] ?? null, fn (Builder $q, int $id) => $q->where('vehicle_id', $id))
            ->when($filters['partner_id'] ?? null, fn (Builder $q, int $id) => $q->where('partner_id', $id))
            ->get()->map(fn (Expense $item) => ['id' => $item->id, 'expense_no' => $item->expense_no, 'date' => $item->expense_date?->toDateString(), 'expense_type' => $item->expenseType?->name, 'scope' => $item->scope?->value, 'vehicle_plate' => $item->vehicle?->license_plate, 'partner_name' => $item->partner?->name, 'driver_name' => $item->driver?->full_name, 'order_no' => $item->dispatchOrder?->order_no, 'amount' => $item->amount, 'payment_method' => $item->payment_method?->value, 'document_no' => $item->document_no, 'description' => $item->description]);

        return $this->result($filters, $rows->all(), ['expense_amount' => $this->money($rows->sum('amount')), 'count' => $rows->count()]);
    }

    public function payroll(array $filters): array
    {
        [$from, $to] = $this->dates($filters);
        $payrolls = Payroll::query()->with(['items.driver', 'items.details.dispatchOrder'])
            ->whereBetween('to_date', [$from, $to])
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->get();
        $rows = $payrolls->flatMap(fn (Payroll $payroll) => $payroll->items->filter(fn ($item) => ! isset($filters['driver_id']) || $item->driver_id === (int) $filters['driver_id'])->map(fn ($item) => ['id' => $item->id, 'payroll_id' => $payroll->id, 'payroll_code' => $payroll->code, 'period' => $payroll->from_date?->toDateString().' - '.$payroll->to_date?->toDateString(), 'status' => $payroll->status?->value, 'paid_at' => $payroll->paid_at?->toDateString(), 'driver_id' => $item->driver_id, 'driver_name' => $item->driver?->full_name, 'gross_salary' => $item->gross_salary, 'net_salary' => $item->net_salary, 'advance_amount' => $item->advance_amount, 'deduction_amount' => $item->deduction_amount, 'details' => $item->details->map(fn ($detail) => ['order_no' => $detail->dispatchOrder?->order_no, 'amount' => $detail->amount, 'calculation_type' => $detail->calculation_type?->value])->all()]));

        return $this->result($filters, $rows->values()->all(), ['gross_salary' => $this->money($rows->sum('gross_salary')), 'net_salary' => $this->money($rows->sum('net_salary')), 'advance_amount' => $this->money($rows->sum('advance_amount'))]);
    }

    public function profitLoss(array $filters): array
    {
        [$from, $to] = $this->dates($filters);
        $basis = $filters['basis'] ?? 'accrual';
        $months = collect();
        for ($cursor = CarbonImmutable::parse($from)->startOfMonth(); $cursor->lte(CarbonImmutable::parse($to)->startOfMonth()); $cursor = $cursor->addMonth()) {
            $start = $cursor->startOfMonth();
            $end = $cursor->endOfMonth();
            $revenue = $basis === 'cash'
                ? Receipt::query()->whereBetween('received_at', [$start, $end])->sum('amount')
                : DispatchOrder::query()->where('status', DispatchOrderStatusEnum::COMPLETED->value)->whereBetween('completed_at', [$start, $end])->sum('customer_amount');
            $expenses = Expense::query()->whereBetween('expense_date', [$start, $end])->sum('amount');
            $partnerPayments = PartnerPayment::query()->whereBetween('paid_at', [$start, $end])->sum('amount');
            $payroll = Payroll::query()->when($basis === 'cash', fn (Builder $q) => $q->whereBetween('paid_at', [$start, $end]), fn (Builder $q) => $q->whereBetween('to_date', [$start, $end]))->with('items')->get()->sum(fn (Payroll $item) => $item->items->sum($basis === 'cash' ? 'net_salary' : 'gross_salary'));
            $cost = $expenses + $partnerPayments + $payroll;
            $months->push(['month' => $cursor->format('Y-m'), 'revenue' => $this->money($revenue), 'expenses' => $this->money($expenses), 'partner_payments' => $this->money($partnerPayments), 'payroll' => $this->money($payroll), 'profit' => $this->money($revenue - $cost)]);
        }

        return $this->result([...$filters, 'basis' => $basis], $months->all(), ['revenue' => $this->money($months->sum('revenue')), 'cost' => $this->money($months->sum(fn ($row) => (float) $row['expenses'] + (float) $row['partner_payments'] + (float) $row['payroll'])), 'profit' => $this->money($months->sum('profit'))], $months->all());
    }

    private function tripOrders(array $filters, array $types): Builder
    {
        [$from, $to] = $this->dates($filters);

        return DispatchOrder::query()->with(['tripSchedule.route:id,name', 'tripSchedule.contract.customer:id,name', 'tripAssignment.vehicle:id,license_plate', 'tripAssignment.driver:id,full_name', 'tripAssignment.partner:id,name', 'expenses', 'partnerPayments'])
            ->whereHas('tripSchedule', function (Builder $query) use ($from, $to, $types, $filters): void {
                $query->whereIn('service_type', $types)->whereBetween('scheduled_start_at', [$from, $to])
                    ->when($filters['route_id'] ?? null, fn (Builder $q, int $id) => $q->where('route_id', $id))
                    ->when($filters['customer_id'] ?? null, fn (Builder $q, int $id) => $q->whereHas('contract', fn (Builder $c) => $c->where('customer_id', $id)));
            })->when($filters['vehicle_id'] ?? null, fn (Builder $q, int $id) => $q->whereHas('tripAssignment', fn (Builder $a) => $a->where('vehicle_id', $id)))
            ->when($filters['driver_id'] ?? null, fn (Builder $q, int $id) => $q->whereHas('tripAssignment', fn (Builder $a) => $a->where('driver_id', $id)))
            ->when($filters['partner_id'] ?? null, fn (Builder $q, int $id) => $q->whereHas('tripAssignment', fn (Builder $a) => $a->where('partner_id', $id)));
    }

    private function tripRow(DispatchOrder $order): array
    {
        return ['id' => $order->id, 'schedule_id' => $order->trip_schedule_id, 'date' => $order->tripSchedule?->scheduled_start_at?->toDateString(), 'shift_name' => $order->tripSchedule?->scheduleRule?->days?->first()?->shift_name, 'order_no' => $order->order_no, 'route_name' => $order->tripSchedule?->route?->name, 'journey' => $order->tripSchedule?->journey, 'customer_name' => $order->tripSchedule?->contract?->customer?->name, 'vehicle_plate' => $order->tripAssignment?->vehicle?->license_plate, 'driver_name' => $order->tripAssignment?->driver?->full_name, 'partner_name' => $order->tripAssignment?->partner?->name, 'start_odometer' => $order->start_odometer, 'end_odometer' => $order->end_odometer, 'actual_distance_km' => $order->actual_distance_km, 'revenue' => $order->status === DispatchOrderStatusEnum::COMPLETED ? $order->customer_amount : '0.00', 'status' => $order->status?->value];
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
