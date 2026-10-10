<?php

namespace App\Modules\Dispatch\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Dispatch\DTOs\TripAssignmentData;
use App\Modules\Dispatch\Interfaces\TripAssignmentServiceInterface;
use App\Modules\Dispatch\Models\TripAssignment;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\TripAssignmentTypeEnum;
use App\Shared\Enums\TripScheduleStatusEnum;
use App\Shared\Enums\VehicleStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TripAssignmentService implements TripAssignmentServiceInterface
{
    public function getList(TripSchedule $schedule): array
    {
        return $schedule->assignments()->with($this->relations())->latest('assigned_at')->get()->map(fn (TripAssignment $assignment) => $assignment->toArray())->all();
    }

    public function assign(TripSchedule $schedule, TripAssignmentData $data, User $user): array
    {
        return DB::transaction(function () use ($schedule, $data, $user): array {
            $locked = TripSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            if ($locked->status !== TripScheduleStatusEnum::PLANNED || $locked->assignments()->where('is_current', true)->exists()) {
                abort(409, 'Lịch không sẵn sàng để phân công.');
            }
            $assignment = $this->create($locked, $data, $user, TripAssignmentTypeEnum::PRIMARY);
            $locked->forceFill(['status' => TripScheduleStatusEnum::ASSIGNED])->save();

            return $assignment->load($this->relations())->toArray();
        });
    }

    public function substitute(TripSchedule $schedule, TripAssignmentData $data, User $user): array
    {
        return DB::transaction(function () use ($schedule, $data, $user): array {
            $locked = TripSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            if ($locked->status !== TripScheduleStatusEnum::ASSIGNED || $this->hasOpenOrder($locked)) {
                abort(409, 'Chỉ được thay phân công trước khi phát hành lệnh điều xe.');
            }
            $current = $locked->assignments()->where('is_current', true)->lockForUpdate()->first();
            if ($current === null) {
                abort(409, 'Lịch chưa có phân công hiện hành.');
            }
            $current->forceFill(['is_current' => false])->save();

            return $this->create($locked, $data, $user, TripAssignmentTypeEnum::SUBSTITUTE, $current)->load($this->relations())->toArray();
        });
    }

    public function removeCurrent(TripAssignment $assignment): array
    {
        return DB::transaction(function () use ($assignment): array {
            $locked = TripAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            $schedule = TripSchedule::query()->lockForUpdate()->findOrFail($locked->trip_schedule_id);
            if (! $locked->is_current || $this->hasOpenOrder($schedule)) {
                abort(409, 'Không thể gỡ phân công sau khi phát hành lệnh điều xe.');
            }
            $locked->forceFill(['is_current' => false])->save();
            $schedule->forceFill(['status' => TripScheduleStatusEnum::PLANNED])->save();

            return $schedule->fresh()->load(['assignments' => $this->relations()])->toArray();
        });
    }

    private function create(TripSchedule $schedule, TripAssignmentData $data, User $user, TripAssignmentTypeEnum $type, ?TripAssignment $replaced = null): TripAssignment
    {
        $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($data->vehicleId);
        $driver = Driver::query()->lockForUpdate()->findOrFail($data->driverId);
        if (! $vehicle->is_active || $vehicle->vehicle_status !== VehicleStatusEnum::AVAILABLE) {
            abort(422, 'Xe không ở trạng thái sẵn sàng.');
        }
        if ($schedule->required_vehicle_type_id !== null && $vehicle->vehicle_type_id !== $schedule->required_vehicle_type_id) {
            abort(422, 'Xe không đúng loại xe yêu cầu.');
        }
        if (! $driver->is_active || $driver->joined_at?->gt($schedule->scheduled_start_at) || ($driver->left_at !== null && $driver->left_at->lte($schedule->scheduled_start_at)) || ($driver->license_expired_at !== null && $driver->license_expired_at->lt($schedule->scheduled_end_at))) {
            abort(422, 'Tài xế không còn đủ điều kiện trong thời gian chuyến.');
        }
        $this->assertNotBusy($schedule, $vehicle->id, $driver->id);

        return TripAssignment::create([
            'trip_schedule_id' => $schedule->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
            'partner_id' => $vehicle->partner_id, 'assignment_type' => $type, 'replaced_assignment_id' => $replaced?->id,
            'replace_reason' => $data->replaceReason, 'assigned_at' => now(), 'assigned_by' => $user->user_name, 'is_current' => true,
        ]);
    }

    private function assertNotBusy(TripSchedule $schedule, int $vehicleId, int $driverId): void
    {
        $conflict = TripAssignment::query()->where('is_current', true)->where(function (Builder $query) use ($vehicleId, $driverId): void {
            $query->where('vehicle_id', $vehicleId)->orWhere('driver_id', $driverId);
        })->whereHas('tripSchedule', function (Builder $query) use ($schedule): void {
            $query->whereKeyNot($schedule->id)->whereNotIn('status', [TripScheduleStatusEnum::COMPLETED->value, TripScheduleStatusEnum::CANCELLED->value])->where('scheduled_start_at', '<', $schedule->scheduled_end_at)->where('scheduled_end_at', '>', $schedule->scheduled_start_at);
        })->lockForUpdate()->exists();
        if ($conflict) {
            abort(409, 'Xe hoặc tài xế đã được phân công cho lịch giao thời gian.');
        }
    }

    private function hasOpenOrder(TripSchedule $schedule): bool
    {
        return $schedule->dispatchOrders()->whereNot('status', 'CANCELLED')->exists();
    }

    private function relations(): array
    {
        return ['vehicle:id,license_plate,vehicle_type_id,partner_id', 'driver:id,code,full_name,partner_id', 'partner:id,name', 'replacedAssignment:id,vehicle_id,driver_id'];
    }
}
