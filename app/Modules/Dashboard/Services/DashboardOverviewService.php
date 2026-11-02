<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Dashboard\DTOs\DashboardOverviewData;
use App\Modules\Dashboard\Interfaces\DashboardOverviewServiceInterface;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\MasterData\Models\Driver;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use Carbon\CarbonImmutable;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;

class DashboardOverviewService implements DashboardOverviewServiceInterface
{
    private const CACHE_TTL_SECONDS = 20;

    public function __construct(private readonly CacheRepository $cache) {}

    public function overview(User $user, DashboardOverviewData $data): array
    {
        $dayStart = CarbonImmutable::parse($data->date ?? now()->toDateString())->startOfDay();
        $dayEnd = $dayStart->addDay();
        $permissions = $this->permissions($user);
        $scope = sha1(implode('|', array_keys(array_filter($permissions))));
        $version = (string) $this->cache->get('dashboard:overview-version', 1);
        $key = "dashboard:overview:v{$version}:{$dayStart->toDateString()}:{$scope}";

        return $this->cache->remember($key, now()->addSeconds(self::CACHE_TTL_SECONDS), fn (): array => [
            'selected_date' => $dayStart->toDateString(),
            'generated_at' => now()->toISOString(),
            'operations' => $this->operations($dayStart, $permissions['operations']),
            'alerts' => $this->alerts($dayStart, $permissions),
            'fleet' => $this->fleet($dayStart, $permissions),
            'finance' => $this->finance($dayStart, $dayEnd, $permissions),
        ]);
    }

    /** @return array<string, bool> */
    private function permissions(User $user): array
    {
        return [
            'operations' => $user->can('worker-vehicles.view') || $user->can('tourist-vehicles.view'),
            'drivers' => $user->can('drivers.view'),
            'receipts' => $user->can('receipts.view'),
            'expenses' => $user->can('expenses.view'),
            'partner_payments' => $user->can('partner-payments.view'),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function runsForDay(CarbonImmutable $day): Collection
    {
        $worker = WorkerVehicle::query()->with(['route:id,name', 'driver:id,full_name'])->where('is_active', true)->whereDate('run_date', $day)->get()->map(fn (WorkerVehicle $run): array => $this->runData($run, 'worker'));
        $tourist = TouristVehicle::query()->with('driver:id,full_name')->where('is_active', true)->whereDate('run_date', '<=', $day)->where(fn ($query) => $query->whereNull('return_date')->orWhereDate('return_date', '>=', $day))->get()->map(fn (TouristVehicle $run): array => $this->runData($run, 'tourist'));

        return $worker->concat($tourist)->sortBy('scheduled_start_at')->values();
    }

    /** @return array<string, mixed> */
    private function operations(CarbonImmutable $day, bool $available): array
    {
        if (! $available) {
            return ['available' => false];
        }

        $runs = $this->runsForDay($day);
        $counts = array_fill_keys(['planned', 'assigned', 'in_progress', 'completed', 'cancelled'], 0);
        $runs->each(function (array $run) use (&$counts): void {
            $status = strtolower((string) $run['status']);
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        });

        return ['available' => true, 'counts' => [...$counts, 'total' => $runs->count()], 'schedules' => $runs->take(20)->all()];
    }

    /** @param array<string, bool> $permissions
     * @return array<string, mixed>
     */
    private function alerts(CarbonImmutable $day, array $permissions): array
    {
        $items = [];
        if ($permissions['operations']) {
            $runs = $this->runsForDay($day);
            $unassigned = $runs->filter(fn (array $run): bool => $run['status'] === 'PLANNED' && (! $run['vehicle_plate'] || ! $run['driver_name']))->count();
            if ($unassigned > 0) {
                $items[] = $this->alert('unassigned_trips', 'warning', $unassigned, "{$unassigned} chuyến chưa đủ biển số hoặc tài xế.");
            }
            $overdue = $runs->filter(fn (array $run): bool => $run['scheduled_end_at'] && $run['scheduled_end_at'] < now()->toISOString() && ! in_array($run['status'], ['COMPLETED', 'CANCELLED'], true))->count();
            if ($overdue > 0) {
                $items[] = $this->alert('overdue_trips', 'critical', $overdue, "{$overdue} chuyến đã quá giờ kết thúc nhưng chưa hoàn tất.");
            }
        }
        if ($permissions['drivers']) {
            $licenseAttention = Driver::query()->where('is_active', true)->whereNotNull('license_expired_at')->whereDate('license_expired_at', '<=', $day->addDays(30)->toDateString())->count();
            if ($licenseAttention > 0) {
                $items[] = $this->alert('driver_license', 'warning', $licenseAttention, "{$licenseAttention} bằng lái đã hoặc sắp hết hạn trong 30 ngày.");
            }
        }
        $priority = ['critical' => 0, 'warning' => 1, 'info' => 2];
        usort($items, fn (array $left, array $right): int => $priority[$left['severity']] <=> $priority[$right['severity']]);

        return ['available' => $permissions['operations'] || $permissions['drivers'], 'items' => $items];
    }

    /** @param array<string, bool> $permissions
     * @return array<string, mixed>
     */
    private function fleet(CarbonImmutable $day, array $permissions): array
    {
        if (! $permissions['operations'] && ! $permissions['drivers']) {
            return ['available' => false];
        }
        $runs = $permissions['operations'] ? $this->runsForDay($day) : collect();
        $assignedPlates = $runs->pluck('vehicle_plate')->filter()->unique()->count();
        $assignedDrivers = $runs->pluck('driver_id')->filter()->unique()->count();
        $activeDrivers = $permissions['drivers'] ? Driver::query()->where('is_active', true)->count() : null;

        return [
            'available' => true,
            'vehicles' => $permissions['operations'] ? ['available' => 0, 'assigned' => $assignedPlates, 'maintenance' => 0, 'inactive' => 0, 'total' => $assignedPlates] : null,
            'drivers' => $permissions['drivers'] ? ['active' => $activeDrivers, 'assigned' => $assignedDrivers, 'available' => max(0, (int) $activeDrivers - $assignedDrivers)] : null,
        ];
    }

    /** @param array<string, bool> $permissions
     * @return array<string, mixed>
     */
    private function finance(CarbonImmutable $start, CarbonImmutable $end, array $permissions): array
    {
        $available = $permissions['receipts'] || $permissions['expenses'] || $permissions['partner_payments'];
        if (! $available) {
            return ['available' => false];
        }
        $receipts = $permissions['receipts'] ? $this->money(Receipt::query()->where('received_at', '>=', $start)->where('received_at', '<', $end)->sum('amount')) : null;
        $expenses = $permissions['expenses'] ? $this->money(Expense::query()->where('expense_date', '>=', $start)->where('expense_date', '<', $end)->sum('amount')) : null;
        $partnerPayments = $permissions['partner_payments'] ? $this->money(PartnerPayment::query()->where('paid_at', '>=', $start)->where('paid_at', '<', $end)->sum('amount')) : null;

        return ['available' => true, 'receipts' => $receipts, 'expenses' => $expenses, 'partner_payments' => $partnerPayments, 'net_cash' => $receipts !== null && $expenses !== null && $partnerPayments !== null ? bcsub(bcsub($receipts, $expenses, 2), $partnerPayments, 2) : null];
    }

    /** @return array<string, mixed> */
    private function runData(WorkerVehicle|TouristVehicle $run, string $kind): array
    {
        $start = $run->run_date?->toDateString().' '.($run->pickup_time ?? '00:00:00');
        $end = ($run->return_date ?? $run->run_date)?->toDateString().' '.($run->return_time ?? $run->pickup_time ?? '23:59:59');

        return ['id' => $kind === 'worker' ? $run->id : 1000000000 + $run->id, 'schedule_no' => $run->run_no, 'scheduled_start_at' => CarbonImmutable::parse($start)->toISOString(), 'scheduled_end_at' => CarbonImmutable::parse($end)->toISOString(), 'route_name' => $kind === 'worker' ? $run->route?->name : $run->journey, 'pickup_location' => $run->pickup_location, 'dropoff_location' => $run->dropoff_location, 'service_type' => $kind === 'worker' ? 'Xe công nhân' : 'Xe du lịch', 'vehicle_type_name' => $kind === 'worker' ? 'Công nhân' : 'Du lịch', 'status' => strtoupper((string) $run->status), 'vehicle_plate' => $run->license_plate, 'driver_id' => $run->driver_id, 'driver_name' => $run->driver?->full_name ?? $run->hired_driver_name];
    }

    /** @return array<string, int|string> */
    private function alert(string $type, string $severity, int $count, string $message): array
    {
        return compact('type', 'severity', 'count', 'message');
    }

    private function money(mixed $value): string
    {
        return bcadd((string) $value, '0', 2);
    }
}
