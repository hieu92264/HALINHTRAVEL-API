<?php

namespace App\Modules\Other\Services;

use App\Modules\DriverPayroll\Models\DriverAttendance;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Other\Interfaces\OperationalReportServiceInterface;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OperationalReportService implements OperationalReportServiceInterface
{
    public function workerVehicles(array $filters): array
    {
        $runs = $this->applyRunFilters(WorkerVehicle::query()->with(['customer:id,name', 'route:id,name', 'driver:id,full_name', 'partner:id,name']), $filters, true)
            ->orderByDesc('run_date')->orderBy('pickup_time')->get()
            ->map(fn (WorkerVehicle $run): array => $this->workerRow($run));

        return $this->runReport($runs, 'Xe chạy công nhân', $filters);
    }

    public function touristVehicles(array $filters): array
    {
        $runs = $this->applyRunFilters(TouristVehicle::query()->with(['customer:id,name', 'driver:id,full_name', 'partner:id,name']), $filters, false)
            ->orderByDesc('run_date')->orderBy('pickup_time')->get()
            ->map(fn (TouristVehicle $run): array => $this->touristRow($run));

        return $this->runReport($runs, 'Xe chạy du lịch', $filters);
    }

    public function profit(array $filters): array
    {
        $worker = $this->completedInPeriod(WorkerVehicle::query()->with(['customer:id,name', 'driver:id,full_name']), $filters)->get()
            ->map(fn (WorkerVehicle $run): array => $this->workerRow($run));
        $tourist = $this->completedInPeriod(TouristVehicle::query()->with(['customer:id,name', 'driver:id,full_name']), $filters)->get()
            ->map(fn (TouristVehicle $run): array => $this->touristRow($run));
        $expenses = $this->documentPeriod(Expense::query()->where('is_active', true), 'expense_date', $filters)->get();
        $partnerPayments = $this->documentPeriod(PartnerPayment::query()->where('is_active', true), 'paid_at', $filters)->get();
        $revenueWorker = $worker->sum('freight_amount');
        $revenueTourist = $tourist->sum('freight_amount');
        $generalExpenses = $expenses->sum('amount');
        $partnerPaid = $partnerPayments->sum('amount');
        $hiredDrivers = $this->hiredDriverSummary($worker->concat($tourist), $expenses);

        return [
            'title' => 'Báo cáo lợi nhuận',
            'filters' => $this->period($filters),
            'summary' => $this->moneyValues([
                'worker_revenue' => $revenueWorker,
                'tourist_revenue' => $revenueTourist,
                'total_revenue' => $revenueWorker + $revenueTourist,
                'general_expenses' => $generalExpenses,
                'partner_payments' => $partnerPaid,
                'total_costs' => $generalExpenses + $partnerPaid,
                'profit' => $revenueWorker + $revenueTourist - $generalExpenses - $partnerPaid,
            ]),
            'hired_drivers' => $hiredDrivers->all(),
            'revenue_runs' => $worker->concat($tourist)->sortByDesc('completed_at')->values()->all(),
        ];
    }

    public function payrollSources(array $filters): array
    {
        $query = DriverAttendance::query()->with(['driver:id,full_name', 'workerVehicle:id,run_no,run_date,license_plate', 'touristVehicle:id,run_no,run_date,license_plate'])
            ->where('is_active', true);
        if (! empty($filters['from_date'])) {
            $query->whereDate('work_date', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->whereDate('work_date', '<=', $filters['to_date']);
        }
        if (! empty($filters['driver_id'])) {
            $query->where('driver_id', $filters['driver_id']);
        }
        $rows = $query->orderByDesc('work_date')->get()->map(function (DriverAttendance $attendance): array {
            $run = $attendance->workerVehicle ?? $attendance->touristVehicle;

            return [
                'id' => $attendance->id,
                'work_date' => $attendance->work_date?->toDateString(),
                'driver_name' => $attendance->driver?->full_name,
                'source_type' => $attendance->worker_vehicle_id ? 'worker' : 'tourist',
                'run_no' => $run?->run_no,
                'license_plate' => $run?->license_plate,
                'base_amount' => $this->money($attendance->base_amount),
                'rate' => $this->money($attendance->rate),
                'calculated_wage' => $this->money($attendance->calculated_wage),
                'status' => $attendance->status?->value,
            ];
        });

        return ['title' => 'Nguồn công lái xe', 'filters' => $this->period($filters), 'summary' => $this->moneyValues(['worker_wage' => $rows->where('source_type', 'worker')->sum(fn (array $row) => (float) $row['calculated_wage']), 'tourist_wage' => $rows->where('source_type', 'tourist')->sum(fn (array $row) => (float) $row['calculated_wage']), 'total_wage' => $rows->sum(fn (array $row) => (float) $row['calculated_wage'])]), 'rows' => $rows->all()];
    }

    private function applyRunFilters(Builder $query, array $filters, bool $worker): Builder
    {
        $this->applyDateFilters($query, 'run_date', $filters);
        foreach (['status', 'customer_id', 'driver_id'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== null) {
                $query->where($key, $filters[$key]);
            }
        }
        if ($worker && ! empty($filters['route_id'])) {
            $query->where('route_id', $filters['route_id']);
        }
        if (! empty($filters['license_plate'])) {
            $query->where('license_plate', 'like', '%'.$filters['license_plate'].'%');
        }

        return $query->where('is_active', true);
    }

    private function completedInPeriod(Builder $query, array $filters): Builder
    {
        $query->where('is_active', true)->where('status', 'completed');
        if (! empty($filters['from_date']) || ! empty($filters['to_date'])) {
            $query->where(function (Builder $dates) use ($filters): void {
                $dates->where(function (Builder $actual) use ($filters): void {
                    $actual->whereNotNull('actual_completed_at');
                    $this->applyDateFilters($actual, 'actual_completed_at', $filters);
                })->orWhere(function (Builder $legacy) use ($filters): void {
                    $legacy->whereNull('actual_completed_at');
                    $this->applyDateFilters($legacy, 'completed_at', $filters);
                });
            });
        }

        return $query;
    }

    private function documentPeriod(Builder $query, string $column, array $filters): Builder
    {
        $this->applyDateFilters($query, $column, $filters);

        return $query;
    }

    private function applyDateFilters(Builder $query, string $column, array $filters): void
    {
        if (! empty($filters['from_date'])) {
            $query->whereDate($column, '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->whereDate($column, '<=', $filters['to_date']);
        }
    }

    /** @param Collection<int, array<string, mixed>> $runs */
    private function runReport(Collection $runs, string $title, array $filters): array
    {
        $completed = $runs->where('status', 'completed');

        return [
            'title' => $title,
            'filters' => $this->period($filters),
            'summary' => $this->moneyValues([
                'total_runs' => $runs->count(),
                'completed_runs' => $completed->count(),
                'freight_amount' => $completed->sum('freight_amount'),
                'driver_wage' => $completed->sum('driver_wage'),
                'partner_vehicle_cost' => $completed->sum('partner_vehicle_cost'),
                'hired_driver_cost' => $completed->sum('hired_driver_cost'),
            ]),
            'rows' => $runs->all(),
        ];
    }

    private function workerRow(WorkerVehicle $run): array
    {
        return $this->runRow($run, 'worker', $run->route?->name ?? $run->shift_name, $run->customer?->name, $run->driver?->full_name, $run->partner?->name);
    }

    private function touristRow(TouristVehicle $run): array
    {
        return $this->runRow($run, 'tourist', $run->journey, $run->customer?->name, $run->driver?->full_name, $run->partner?->name);
    }

    private function runRow(WorkerVehicle|TouristVehicle $run, string $kind, ?string $journey, ?string $customerName, ?string $driverName, ?string $partnerName): array
    {
        return [
            'id' => $run->id,
            'kind' => $kind,
            'run_no' => $run->run_no,
            'run_date' => $run->run_date?->toDateString(),
            'return_date' => $run->return_date?->toDateString(),
            'pickup_time' => $run->pickup_time,
            'return_time' => $run->return_time,
            'journey' => $journey,
            'customer_name' => $customerName,
            'license_plate' => $run->license_plate,
            'driver_name' => $driverName ?? $run->hired_driver_name,
            'partner_name' => $partnerName,
            'status' => $run->status,
            'completed_at' => ($run->actual_completed_at ?? $run->completed_at)?->toISOString(),
            'freight_amount' => $this->money($run->freight_amount),
            'driver_wage' => $this->money($run->driver_wage),
            'partner_vehicle_cost' => $this->money($run->partner_vehicle_cost),
            'hired_driver_cost' => $this->money($run->hired_driver_cost),
        ];
    }

    /** @param Collection<int, array<string, mixed>> $runs
     * @param  Collection<int, Expense>  $expenses
     */
    private function hiredDriverSummary(Collection $runs, Collection $expenses): Collection
    {
        $names = $runs->filter(fn (array $run): bool => ! empty($run['driver_name']) && (float) $run['hired_driver_cost'] > 0)->pluck('driver_name')->merge($expenses->pluck('hired_driver_name'))->filter()->unique()->sort()->values();

        return $names->map(function (string $name) use ($runs, $expenses): array {
            $incurred = $runs->where('driver_name', $name)->sum(fn (array $run) => (float) $run['hired_driver_cost']);
            $paid = $expenses->where('hired_driver_name', $name)->sum('amount');

            return $this->moneyValues(['name' => $name, 'incurred_amount' => $incurred, 'paid_amount' => $paid, 'outstanding_amount' => $incurred - $paid]);
        });
    }

    private function period(array $filters): array
    {
        return ['from_date' => $filters['from_date'] ?? null, 'to_date' => $filters['to_date'] ?? null];
    }

    private function moneyValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_numeric($value)) {
                $values[$key] = $this->money($value);
            }
        }

        return $values;
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
