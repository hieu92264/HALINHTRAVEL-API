<?php

namespace App\Modules\Other\Services;

use App\Modules\DriverPayroll\Models\Payroll;
use App\Modules\Finance\Models\Expense;
use App\Modules\Other\Interfaces\DebtReportServiceInterface;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use Illuminate\Support\Collection;

/**
 * Compatibility read model for the six operational reports.  Its row keys
 * deliberately match the long-lived report and Word templates, while all
 * records come from the post-refactor vehicle-operation schema.
 */
class RestoredReportService
{
    public function __construct(private readonly DebtReportServiceInterface $debts) {}

    public function businessTrips(array $filters): array
    {
        $rows = $this->workerRuns($filters)->map(fn (WorkerVehicle $run) => $this->workerRow($run));

        return $this->report($filters, $rows, ['revenue', 'driver_wage', 'external_driver_cost', 'partner_vehicle_cost', 'external_vehicle_profit']);
    }

    public function tourismTrips(array $filters): array
    {
        $rows = $this->touristRuns($filters)->map(fn (TouristVehicle $run) => $this->touristRow($run));

        return $this->report($filters, $rows, ['revenue', 'driver_wage', 'external_driver_cost', 'partner_vehicle_cost', 'driver_collected_amount', 'driver_remitted_amount', 'company_collected_amount', 'remaining_receivable']);
    }

    public function customerDebts(array $filters): array
    {
        $rows = collect($this->debts->customerDebts($filters['from_date'], $filters['to_date']));

        return $this->report($filters, $rows, ['opening_balance', 'incurred_amount', 'received_amount', 'closing_balance']);
    }

    public function customerDebtDetails(array $filters): array
    {
        $detail = $this->debts->customerDebtDetails((int) $filters['customer_id'], $filters['from_date'], $filters['to_date']);

        return $this->report($filters, collect($detail['rows']), ['debit', 'credit', 'balance'], ['subject_name' => $detail['subject_name'], 'opening_balance' => $detail['opening_balance'], 'closing_balance' => $detail['closing_balance']]);
    }

    public function partnerDebts(array $filters): array
    {
        $rows = collect($this->debts->partnerDebts($filters['from_date'], $filters['to_date']));

        return $this->report($filters, $rows, ['opening_balance', 'incurred_amount', 'paid_amount', 'closing_balance']);
    }

    public function partnerDebtDetails(array $filters): array
    {
        $detail = $this->debts->partnerDebtDetails((int) $filters['partner_id'], $filters['from_date'], $filters['to_date']);

        return $this->report($filters, collect($detail['rows']), ['debit', 'credit', 'balance'], ['subject_name' => $detail['subject_name'], 'opening_balance' => $detail['opening_balance'], 'closing_balance' => $detail['closing_balance']]);
    }

    public function driverPayroll(array $filters): array
    {
        // A later revision recalculates the whole month.  Reporting more than
        // the current revision would duplicate every driver's amount.
        $payrolls = Payroll::query()->with(['items.driver'])->where('is_active', true)->where('is_current', true)
            ->whereDate('from_date', '<=', $filters['to_date'])->whereDate('to_date', '>=', $filters['from_date'])->get();
        $rows = $payrolls->flatMap(fn (Payroll $payroll) => $payroll->items->map(fn ($item) => [
            'payroll_id' => $payroll->id, 'payroll_code' => $payroll->code, 'driver_id' => $item->driver_id,
            'driver_name' => $item->driver?->full_name, 'responsibility_allowance' => $this->money($item->responsibility_allowance),
            'fixed_trip_wage' => $this->money($item->fixed_trip_wage), 'tourism_commission' => $this->money($item->tourism_commission),
            'gross_salary' => $this->money($item->gross_salary), 'advance_amount' => $this->money($item->advance_amount),
            'net_salary' => $this->money($item->net_salary),
        ]));

        return $this->report($filters, $rows, ['responsibility_allowance', 'fixed_trip_wage', 'tourism_commission', 'gross_salary', 'advance_amount', 'net_salary']);
    }

    public function driverPayrollDetails(array $filters): array
    {
        $payroll = Payroll::query()->with(['items.driver', 'items.details.workerVehicle', 'items.details.touristVehicle', 'items.details.driverAttendance'])->findOrFail($filters['payroll_id']);
        $rows = $payroll->items->flatMap(function ($item) {
            return $item->details->map(function ($detail) use ($item): array {
                $run = $detail->workerVehicle ?? $detail->touristVehicle;

                return ['driver_name' => $item->driver?->full_name, 'date' => $detail->driverAttendance?->work_date?->toDateString(),
                    'calculation_type' => $detail->calculation_type?->value, 'worker_run_no' => $detail->workerVehicle?->run_no,
                    'tourist_run_no' => $detail->touristVehicle?->run_no, 'journey' => $detail->workerVehicle?->route?->name ?? $detail->touristVehicle?->journey,
                    'description' => $detail->description, 'base_amount' => $this->money($detail->base_amount),
                    'rate' => $this->money($detail->rate), 'amount' => $this->money($detail->amount),
                    'worker_vehicle_id' => $detail->worker_vehicle_id, 'tourist_vehicle_id' => $detail->tourist_vehicle_id,
                    'license_plate' => $run?->license_plate];
            });
        });

        return $this->report($filters, $rows, ['amount'], ['gross_salary' => $this->money($payroll->items->sum('gross_salary')), 'net_salary' => $this->money($payroll->items->sum('net_salary'))]);
    }

    public function vehicleProfit(array $filters): array
    {
        $runs = $this->workerRuns($filters)->map(fn (WorkerVehicle $run) => $this->workerRow($run))
            ->concat($this->touristRuns($filters)->map(fn (TouristVehicle $run) => $this->touristRow($run)));
        $generalCost = (float) Expense::query()->where('is_active', true)->whereBetween('expense_date', [$filters['from_date'], $filters['to_date']])->sum('amount');
        $groups = $runs->groupBy(fn (array $row) => $row['vehicle_plate'] ?: 'Chưa có biển số');
        $allocation = $groups->isEmpty() ? 0 : $generalCost / $groups->count();
        $rows = $groups->map(function (Collection $items, string $plate) use ($allocation): array {
            $revenue = (float) $items->sum('revenue');
            $wage = (float) $items->sum('driver_wage');
            $partner = (float) $items->sum('partner_vehicle_cost');
            $external = (float) $items->sum('external_driver_cost');
            $cost = $wage + $partner + $external + $allocation;

            return ['vehicle_plate' => $plate, 'vehicle_type' => $items->pluck('trip_type')->unique()->join(', '), 'revenue' => $this->money($revenue),
                'driver_wage' => $this->money($wage), 'partner_vehicle_cost' => $this->money($partner), 'external_driver_cost' => $this->money($external),
                'fuel_cost' => '0.00', 'repair_cost' => '0.00', 'insurance_registration_cost' => '0.00', 'other_cost' => '0.00',
                'general_cost_allocated' => $this->money($allocation), 'total_cost' => $this->money($cost), 'profit' => $this->money($revenue - $cost)];
        })->values();

        return $this->report($filters, $rows, ['revenue', 'driver_wage', 'partner_vehicle_cost', 'external_driver_cost', 'general_cost_allocated', 'total_cost', 'profit']);
    }

    private function workerRuns(array $filters): Collection
    {
        $query = WorkerVehicle::query()->with(['customer:id,name', 'route:id,name', 'driver:id,full_name', 'partner:id,name'])->where('is_active', true)->where('status', 'completed');
        $this->date($query, $filters);
        $this->runFilters($query, $filters, true);

        return $query->get();
    }

    private function touristRuns(array $filters): Collection
    {
        $query = TouristVehicle::query()->with(['customer:id,name', 'driver:id,full_name', 'partner:id,name'])->where('is_active', true)->where('status', 'completed');
        $this->date($query, $filters);
        $this->runFilters($query, $filters, false);

        return $query->get();
    }

    private function runFilters($query, array $filters, bool $worker): void
    {
        foreach (['customer_id', 'vehicle_id', 'driver_id'] as $key) {
            if (filled($filters[$key] ?? null)) {
                $query->where($key, $filters[$key]);
            }
        }
        if (filled($filters['license_plate'] ?? null)) {
            $query->where('license_plate', 'like', '%'.$filters['license_plate'].'%');
        }
        if (! $worker) {
            return;
        }
        if (filled($filters['partner_id'] ?? null)) {
            $query->where('partner_id', $filters['partner_id']);
        }
        if (filled($filters['route_id'] ?? null)) {
            $query->where('route_id', $filters['route_id']);
        }
        if (filled($filters['shift_name'] ?? null)) {
            $query->where('shift_name', 'like', '%'.$filters['shift_name'].'%');
        }
    }

    private function date($query, array $filters): void
    {
        $query->where(function ($dates) use ($filters): void {
            $dates->whereBetween('actual_completed_at', [$filters['from_date'], $filters['to_date'].' 23:59:59'])
                ->orWhere(function ($fallback) use ($filters): void {
                    $fallback->whereNull('actual_completed_at')->whereBetween('completed_at', [$filters['from_date'], $filters['to_date'].' 23:59:59']);
                });
        });
    }

    private function workerRow(WorkerVehicle $run): array
    {
        $revenue = (float) $run->freight_amount;
        $cost = (float) $run->driver_wage + (float) $run->hired_driver_cost + (float) $run->partner_vehicle_cost;

        return ['worker_vehicle_id' => $run->id, 'run_no' => $run->run_no, 'date' => ($run->actual_completed_at ?? $run->completed_at)?->toDateString(), 'vehicle_plate' => $run->license_plate,
            'customer_name' => $run->customer?->name, 'route_name' => $run->route?->name, 'journey' => $run->shift_name, 'pickup_time' => $run->pickup_time, 'return_time' => $run->return_time,
            'driver_name' => $run->driver?->full_name ?? $run->hired_driver_name, 'partner_name' => $run->partner?->name, 'trip_type' => 'Công nhân', 'revenue' => $this->money($revenue),
            'driver_wage' => $this->money($run->driver_wage), 'external_driver_cost' => $this->money($run->hired_driver_cost), 'partner_vehicle_cost' => $this->money($run->partner_vehicle_cost), 'external_vehicle_profit' => $this->money($revenue - $cost)];
    }

    private function touristRow(TouristVehicle $run): array
    {
        $revenue = (float) $run->freight_amount;
        $received = (float) $run->driver_collected_amount + (float) $run->company_collected_amount;

        return ['tourist_vehicle_id' => $run->id, 'run_no' => $run->run_no, 'date' => ($run->actual_completed_at ?? $run->completed_at)?->toDateString(), 'vehicle_plate' => $run->license_plate,
            'customer_name' => $run->customer?->name, 'journey' => $run->journey, 'pickup_time' => $run->pickup_time, 'return_time' => $run->return_time, 'driver_name' => $run->driver?->full_name,
            'driver_wage_rate' => (string) $run->driver_wage_rate, 'hired_driver_name' => $run->hired_driver_name,
            'partner_name' => $run->partner?->name, 'trip_type' => 'Du lịch', 'revenue' => $this->money($revenue), 'driver_wage' => $this->money($run->driver_wage),
            'external_driver_cost' => $this->money($run->hired_driver_cost), 'partner_vehicle_cost' => $this->money($run->partner_vehicle_cost), 'driver_collected_amount' => $this->money($run->driver_collected_amount),
            'driver_remitted_amount' => $this->money($run->driver_remitted_amount), 'company_collected_amount' => $this->money($run->company_collected_amount), 'remaining_receivable' => $this->money($revenue - $received)];
    }

    private function report(array $filters, Collection $rows, array $totalKeys, array $extra = []): array
    {
        $totals = [];
        foreach ($totalKeys as $key) {
            $totals[$key] = $this->money($rows->sum(fn (array $row) => (float) ($row[$key] ?? 0)));
        }

        return [...$extra, 'filters' => $filters, 'generated_at' => now()->toISOString(), 'totals' => $totals, 'rows' => $rows->values()->map(fn (array $row, int $index): array => ['stt' => $index + 1, ...$row])->all()];
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
