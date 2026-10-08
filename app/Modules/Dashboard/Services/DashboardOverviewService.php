<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Dashboard\DTOs\DashboardOverviewData;
use App\Modules\Dashboard\Interfaces\DashboardOverviewServiceInterface;
use App\Modules\Dispatch\Models\TripAssignment;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\TripScheduleStatusEnum;
use App\Shared\Enums\VehicleStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;

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

        return $this->cache->remember($key, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($dayStart, $dayEnd, $permissions): array {
            return [
                'selected_date' => $dayStart->toDateString(),
                'generated_at' => now()->toISOString(),
                'operations' => $this->operations($dayStart, $dayEnd, $permissions['trip_schedules']),
                'alerts' => $this->alerts($dayStart, $dayEnd, $permissions),
                'fleet' => $this->fleet($dayStart, $dayEnd, $permissions),
                'finance' => $this->finance($dayStart, $dayEnd, $permissions),
            ];
        });
    }

    /** @return array<string, bool> */
    private function permissions(User $user): array
    {
        return [
            'trip_schedules' => $user->can('trip-schedules.view'),
            'trip_assignments' => $user->can('trip-assignments.view'),
            'dispatch_orders' => $user->can('dispatch-orders.view'),
            'vehicles' => $user->can('vehicles.view'),
            'drivers' => $user->can('drivers.view'),
            'receipts' => $user->can('receipts.view'),
            'expenses' => $user->can('expenses.view'),
            'partner_payments' => $user->can('partner-payments.view'),
        ];
    }

    /** @return array<string, mixed> */
    private function operations(CarbonImmutable $start, CarbonImmutable $end, bool $available): array
    {
        if (! $available) {
            return ['available' => false];
        }

        $counts = array_fill_keys(TripScheduleStatusEnum::values(), 0);
        $this->schedulesForDay($start, $end)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->each(function (mixed $count, string $status) use (&$counts): void {
                $counts[$status] = (int) $count;
            });

        $schedules = $this->schedulesForDay($start, $end)
            ->with([
                'route:id,name',
                'requiredVehicleType:id,name',
                'assignments' => fn ($query) => $query
                    ->select(['id', 'trip_schedule_id', 'vehicle_id', 'driver_id'])
                    ->where('is_current', true)
                    ->with(['vehicle:id,license_plate', 'driver:id,code,full_name']),
            ])
            ->orderBy('scheduled_start_at')
            ->limit(20)
            ->get()
            ->map(function (TripSchedule $schedule): array {
                $assignment = $schedule->assignments->first();

                return [
                    'id' => $schedule->id,
                    'schedule_no' => $schedule->schedule_no,
                    'scheduled_start_at' => $schedule->scheduled_start_at?->toISOString(),
                    'scheduled_end_at' => $schedule->scheduled_end_at?->toISOString(),
                    'route_name' => $schedule->route?->name,
                    'pickup_location' => $schedule->pickup_location,
                    'dropoff_location' => $schedule->dropoff_location,
                    'service_type' => $schedule->service_type?->value,
                    'vehicle_type_name' => $schedule->requiredVehicleType?->name,
                    'status' => $schedule->status?->value,
                    'vehicle_plate' => $assignment?->vehicle?->license_plate,
                    'driver_name' => $assignment?->driver?->full_name,
                ];
            })
            ->all();

        return [
            'available' => true,
            'counts' => [
                'planned' => $counts[TripScheduleStatusEnum::PLANNED->value],
                'assigned' => $counts[TripScheduleStatusEnum::ASSIGNED->value],
                'in_progress' => $counts[TripScheduleStatusEnum::IN_PROGRESS->value],
                'completed' => $counts[TripScheduleStatusEnum::COMPLETED->value],
                'cancelled' => $counts[TripScheduleStatusEnum::CANCELLED->value],
                'total' => array_sum($counts),
            ],
            'schedules' => $schedules,
        ];
    }

    /** @param array<string, bool> $permissions
     * @return array<string, mixed>
     */
    private function alerts(CarbonImmutable $start, CarbonImmutable $end, array $permissions): array
    {
        $items = [];
        if ($permissions['trip_schedules']) {
            $unassigned = $this->schedulesForDay($start, $end)
                ->whereIn('status', [TripScheduleStatusEnum::PLANNED->value, TripScheduleStatusEnum::ASSIGNED->value])
                ->whereDoesntHave('assignments', fn (Builder $query): Builder => $query->where('is_current', true))
                ->count();
            if ($unassigned > 0) {
                $items[] = $this->alert('unassigned_trips', 'warning', $unassigned, "{$unassigned} chuyến chưa được phân công xe và tài xế.");
            }

            $overdue = TripSchedule::query()
                ->where('scheduled_end_at', '<', now())
                ->whereNotIn('status', [TripScheduleStatusEnum::COMPLETED->value, TripScheduleStatusEnum::CANCELLED->value])
                ->count();
            if ($overdue > 0) {
                $items[] = $this->alert('overdue_trips', 'critical', $overdue, "{$overdue} chuyến đã quá giờ kết thúc nhưng chưa hoàn tất.");
            }
        }
        if ($permissions['vehicles']) {
            $maintenance = Vehicle::query()->where('is_active', true)->whereIn('vehicle_status', [VehicleStatusEnum::MAINTENANCE->value, VehicleStatusEnum::INACTIVE->value])->count();
            if ($maintenance > 0) {
                $items[] = $this->alert('vehicle_attention', 'warning', $maintenance, "{$maintenance} xe đang bảo dưỡng hoặc ngừng hoạt động.");
            }
        }
        if ($permissions['drivers']) {
            $licenseAttention = Driver::query()
                ->where('is_active', true)
                ->whereNotNull('license_expired_at')
                ->whereDate('license_expired_at', '<=', $start->addDays(30)->toDateString())
                ->count();
            if ($licenseAttention > 0) {
                $items[] = $this->alert('driver_license', 'warning', $licenseAttention, "{$licenseAttention} bằng lái đã hoặc sắp hết hạn trong 30 ngày.");
            }
        }

        $priority = ['critical' => 0, 'warning' => 1, 'info' => 2];
        usort($items, fn (array $left, array $right): int => $priority[$left['severity']] <=> $priority[$right['severity']]);

        return ['available' => $permissions['trip_schedules'] || $permissions['vehicles'] || $permissions['drivers'], 'items' => $items];
    }

    /** @return array<string, mixed> */
    private function fleet(CarbonImmutable $start, CarbonImmutable $end, array $permissions): array
    {
        if (! $permissions['vehicles'] && ! $permissions['drivers']) {
            return ['available' => false];
        }

        $result = ['available' => true, 'vehicles' => null, 'drivers' => null];
        if ($permissions['vehicles']) {
            $counts = array_fill_keys(VehicleStatusEnum::values(), 0);
            Vehicle::query()->where('is_active', true)->selectRaw('vehicle_status, COUNT(*) as aggregate')->groupBy('vehicle_status')->pluck('aggregate', 'vehicle_status')->each(function (mixed $count, string $status) use (&$counts): void {
                $counts[$status] = (int) $count;
            });
            $result['vehicles'] = [
                'available' => $counts[VehicleStatusEnum::AVAILABLE->value],
                'assigned' => $counts[VehicleStatusEnum::ASSIGNED->value],
                'maintenance' => $counts[VehicleStatusEnum::MAINTENANCE->value],
                'inactive' => $counts[VehicleStatusEnum::INACTIVE->value],
                'total' => array_sum($counts),
            ];
        }
        if ($permissions['drivers']) {
            $activeDrivers = $this->activeDriversForDay($start, $end);
            $assignedDrivers = TripAssignment::query()->where('is_current', true)->whereHas('tripSchedule', fn (Builder $query): Builder => $this->overlap($query, $start, $end))->distinct('driver_id')->count('driver_id');
            $result['drivers'] = [
                'active' => $activeDrivers,
                'assigned' => $assignedDrivers,
                'available' => max(0, $activeDrivers - $assignedDrivers),
            ];
        }

        return $result;
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

        return [
            'available' => true,
            'receipts' => $receipts,
            'expenses' => $expenses,
            'partner_payments' => $partnerPayments,
            'net_cash' => $receipts !== null && $expenses !== null && $partnerPayments !== null
                ? bcsub(bcsub($receipts, $expenses, 2), $partnerPayments, 2)
                : null,
        ];
    }

    private function schedulesForDay(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return $this->overlap(TripSchedule::query(), $start, $end);
    }

    private function overlap(Builder $query, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return $query->where('scheduled_start_at', '<', $end)->where('scheduled_end_at', '>', $start);
    }

    private function activeDriversForDay(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return Driver::query()
            ->where('is_active', true)
            ->whereDate('joined_at', '<=', $end->toDateString())
            ->where(fn (Builder $query): Builder => $query->whereNull('left_at')->orWhereDate('left_at', '>', $start->toDateString()))
            ->count();
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
