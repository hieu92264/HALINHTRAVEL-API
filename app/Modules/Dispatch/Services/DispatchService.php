<?php

namespace App\Modules\Dispatch\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Contract\Models\Contract;
use App\Modules\Contract\Models\ContractItem;
use App\Modules\Dispatch\Interfaces\DispatchServiceInterface;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\Dispatch\Models\TripAssignment;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\ContractStatusEnum;
use App\Shared\Enums\DispatchOrderStatusEnum;
use App\Shared\Enums\OwnershipTypeEnum;
use App\Shared\Enums\TripAssignmentTypeEnum;
use App\Shared\Enums\TripScheduleStatusEnum;
use App\Shared\Enums\VehicleStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DispatchService implements DispatchServiceInterface
{
    private const SCHEDULE_RELATIONS = [
        'contract.customer', 'contractItem.vehicleType', 'route', 'requiredVehicleType',
        'assignments.vehicle.partner', 'assignments.driver.partner', 'assignments.partner', 'dispatchOrder.tripAssignment',
    ];

    private const ORDER_RELATIONS = [
        'tripSchedule.contract.customer', 'tripSchedule.route', 'tripSchedule.requiredVehicleType',
        'tripAssignment.vehicle.partner', 'tripAssignment.driver.partner', 'tripAssignment.partner',
    ];

    public function schedules(): array
    {
        return TripSchedule::query()->with(self::SCHEDULE_RELATIONS)->where('is_active', true)
            ->orderBy('scheduled_start_at')->get()->map(fn (TripSchedule $schedule) => $this->scheduleData($schedule))->all();
    }

    public function schedule(TripSchedule $schedule): array
    {
        return $this->scheduleData($schedule->load(self::SCHEDULE_RELATIONS));
    }

    public function createSchedule(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $this->assertScheduleRelations($data);
            $schedule = TripSchedule::create(array_merge($this->scheduleFields($data), [
                'schedule_no' => 'LC'.now()->format('YmdHis').random_int(100, 999),
                'status' => TripScheduleStatusEnum::PLANNED,
            ]));

            return $this->scheduleData($schedule->fresh(self::SCHEDULE_RELATIONS));
        });
    }

    public function updateSchedule(TripSchedule $schedule, array $data): array
    {
        return DB::transaction(function () use ($schedule, $data): array {
            $locked = TripSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            $this->assertPlannedWithoutOrder($locked);
            $merged = array_merge($locked->only(array_keys($this->scheduleFields($data))), $data, [
                'contract_id' => $data['contract_id'] ?? $locked->contract_id,
                'required_vehicle_type_id' => $data['required_vehicle_type_id'] ?? $locked->required_vehicle_type_id,
            ]);
            $this->assertScheduleRelations($merged);
            $locked->fill($this->scheduleFields($merged))->save();

            return $this->scheduleData($locked->fresh(self::SCHEDULE_RELATIONS));
        });
    }

    public function cancelSchedule(TripSchedule $schedule, ?string $note): array
    {
        return DB::transaction(function () use ($schedule, $note): array {
            $locked = TripSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            if (! in_array($locked->status, [TripScheduleStatusEnum::PLANNED, TripScheduleStatusEnum::ASSIGNED], true)) {
                abort(409, 'Lịch chuyến không thể hủy ở trạng thái hiện tại.');
            }
            if ($locked->dispatchOrder()->exists()) {
                abort(409, 'Không thể hủy lịch đã có lệnh điều xe.');
            }
            $locked->forceFill(['status' => TripScheduleStatusEnum::CANCELLED, 'note' => $note ?? $locked->note])->save();

            return $this->scheduleData($locked->fresh(self::SCHEDULE_RELATIONS));
        });
    }

    public function assignments(TripSchedule $schedule): array
    {
        return $schedule->assignments()->with(['vehicle.partner', 'driver.partner', 'partner', 'replacedAssignment'])->orderByDesc('assigned_at')
            ->get()->map(fn (TripAssignment $assignment) => $this->assignmentData($assignment))->all();
    }

    public function assign(TripSchedule $schedule, array $data, User $actor): array
    {
        return DB::transaction(function () use ($schedule, $data, $actor): array {
            $locked = TripSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            if ($locked->status !== TripScheduleStatusEnum::PLANNED || $locked->assignments()->where('is_current', true)->exists()) {
                abort(409, 'Lịch chuyến không thể phân công ở trạng thái hiện tại.');
            }
            $assignment = $this->createAssignment($locked, $data, $actor, TripAssignmentTypeEnum::PRIMARY);
            $locked->forceFill(['status' => TripScheduleStatusEnum::ASSIGNED])->save();

            return $this->assignmentData($assignment->fresh(['vehicle.partner', 'driver.partner', 'partner']));
        });
    }

    public function substitute(TripSchedule $schedule, array $data, User $actor): array
    {
        return DB::transaction(function () use ($schedule, $data, $actor): array {
            $locked = TripSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            if ($locked->status !== TripScheduleStatusEnum::ASSIGNED || $locked->dispatchOrder()->exists()) {
                abort(409, 'Chỉ thay thế phân công cho lịch chưa có lệnh điều xe.');
            }
            $current = $locked->assignments()->where('is_current', true)->lockForUpdate()->first();
            if ($current === null) {
                abort(409, 'Lịch chuyến chưa có phân công hiện hành.');
            }
            if (blank($data['replace_reason'] ?? null)) {
                abort(422, 'Vui lòng nhập lý do thay thế.');
            }
            $current->forceFill(['is_current' => false])->save();
            $data['replaced_assignment_id'] = $current->id;
            $assignment = $this->createAssignment($locked, $data, $actor, TripAssignmentTypeEnum::SUBSTITUTE);

            return $this->assignmentData($assignment->fresh(['vehicle.partner', 'driver.partner', 'partner', 'replacedAssignment']));
        });
    }

    public function removeAssignment(int $assignmentId): void
    {
        DB::transaction(function () use ($assignmentId): void {
            $assignment = TripAssignment::query()->with('tripSchedule')->lockForUpdate()->findOrFail($assignmentId);
            if (! $assignment->is_current || $assignment->tripSchedule->dispatchOrder()->exists()) {
                abort(409, 'Không thể bỏ phân công này.');
            }
            $assignment->forceFill(['is_current' => false])->save();
            $assignment->tripSchedule->forceFill(['status' => TripScheduleStatusEnum::PLANNED])->save();
        });
    }

    public function orders(): array
    {
        return DispatchOrder::query()->with(self::ORDER_RELATIONS)->where('is_active', true)->orderByDesc('issued_at')
            ->get()->map(fn (DispatchOrder $order) => $this->orderData($order))->all();
    }

    public function order(DispatchOrder $order): array
    {
        return $this->orderData($order->load(self::ORDER_RELATIONS));
    }

    public function createOrder(TripSchedule $schedule, User $actor): array
    {
        return DB::transaction(function () use ($schedule, $actor): array {
            $locked = TripSchedule::query()->with('assignments')->lockForUpdate()->findOrFail($schedule->id);
            if ($locked->status !== TripScheduleStatusEnum::ASSIGNED || $locked->dispatchOrder()->exists()) {
                abort(409, 'Lịch chuyến chưa sẵn sàng tạo lệnh điều xe.');
            }
            $assignment = $locked->assignments->firstWhere('is_current', true);
            if ($assignment === null) {
                abort(409, 'Lịch chuyến chưa có phân công hiện hành.');
            }
            $order = DispatchOrder::create([
                'order_no' => 'LDX'.now()->format('YmdHis').random_int(100, 999),
                'trip_schedule_id' => $locked->id, 'trip_assignment_id' => $assignment->id,
                'issued_at' => now(), 'issued_by' => $actor->user_name, 'status' => DispatchOrderStatusEnum::ISSUED,
            ]);

            return $this->orderData($order->fresh(self::ORDER_RELATIONS));
        });
    }

    public function assignOrder(DispatchOrder $order): array
    {
        return $this->orderTransition($order, DispatchOrderStatusEnum::ISSUED, DispatchOrderStatusEnum::ASSIGNED);
    }

    public function start(DispatchOrder $order, array $data, User $actor): array
    {
        return DB::transaction(function () use ($order, $data, $actor): array {
            $locked = DispatchOrder::query()->with('tripAssignment.driver', 'tripSchedule')->lockForUpdate()->findOrFail($order->id);
            $this->assertActionActor($locked, $actor);
            if ($locked->status !== DispatchOrderStatusEnum::ASSIGNED) {
                abort(409, 'Lệnh điều xe chưa thể bắt đầu.');
            }
            $locked->fill(['actual_start_at' => $data['actual_start_at'], 'start_odometer' => $data['start_odometer'], 'note' => $data['note'] ?? $locked->note, 'status' => DispatchOrderStatusEnum::IN_PROGRESS])->save();
            $locked->tripSchedule->forceFill(['status' => TripScheduleStatusEnum::IN_PROGRESS])->save();

            return $this->orderData($locked->fresh(self::ORDER_RELATIONS));
        });
    }

    public function complete(DispatchOrder $order, array $data, User $actor): array
    {
        return DB::transaction(function () use ($order, $data, $actor): array {
            $locked = DispatchOrder::query()->with(['tripAssignment.vehicle', 'tripAssignment.driver', 'tripSchedule'])->lockForUpdate()->findOrFail($order->id);
            $this->assertActionActor($locked, $actor);
            if ($locked->status !== DispatchOrderStatusEnum::IN_PROGRESS) {
                abort(409, 'Chỉ lệnh đang chạy mới được hoàn thành.');
            }
            if (CarbonImmutable::parse($data['actual_end_at'])->lt(CarbonImmutable::parse($locked->actual_start_at)) || (int) $data['end_odometer'] < (int) $locked->start_odometer) {
                abort(422, 'Thời gian hoặc ODO kết thúc không hợp lệ.');
            }
            $locked->fill(array_merge($data, ['waiting_hours' => $data['waiting_hours'] ?? 0, 'customer_amount' => $data['customer_amount'] ?? 0, 'partner_vehicle_cost' => $data['partner_vehicle_cost'] ?? 0, 'external_driver_cost' => $data['external_driver_cost'] ?? 0, 'status' => DispatchOrderStatusEnum::COMPLETED, 'completed_at' => now()]))->save();
            $locked->tripSchedule->forceFill(['status' => TripScheduleStatusEnum::COMPLETED])->save();
            $vehicle = $locked->tripAssignment->vehicle;
            if ($vehicle !== null && (int) $data['end_odometer'] > (int) ($vehicle->current_odometer ?? 0)) {
                $vehicle->forceFill(['current_odometer' => $data['end_odometer']])->save();
            }

            return $this->orderData($locked->fresh(self::ORDER_RELATIONS));
        });
    }

    public function cancelOrder(DispatchOrder $order, string $note): array
    {
        return DB::transaction(function () use ($order, $note): array {
            $locked = DispatchOrder::query()->with('tripSchedule')->lockForUpdate()->findOrFail($order->id);
            if ($locked->status === DispatchOrderStatusEnum::COMPLETED || $locked->status === DispatchOrderStatusEnum::CANCELLED) {
                abort(409, 'Lệnh điều xe không thể hủy.');
            }
            $locked->forceFill(['status' => DispatchOrderStatusEnum::CANCELLED, 'note' => $note])->save();
            $locked->tripSchedule->forceFill(['status' => TripScheduleStatusEnum::CANCELLED])->save();

            return $this->orderData($locked->fresh(self::ORDER_RELATIONS));
        });
    }

    public function myOrders(User $actor): array
    {
        $driver = $this->driverFor($actor);

        return DispatchOrder::query()->with(self::ORDER_RELATIONS)->whereHas('tripAssignment', fn (Builder $query) => $query->where('driver_id', $driver->id))
            ->where('is_active', true)->orderBy('issued_at')->get()->map(fn (DispatchOrder $order) => $this->orderData($order, true))->all();
    }

    public function myOrder(DispatchOrder $order, User $actor): array
    {
        $driver = $this->driverFor($actor);
        $loaded = $order->load(self::ORDER_RELATIONS);
        if ($loaded->tripAssignment?->driver_id !== $driver->id) {
            abort(404, 'Không tìm thấy lệnh điều xe.');
        }

        return $this->orderData($loaded, true);
    }

    private function assertScheduleRelations(array $data): void
    {
        $contract = Contract::query()->findOrFail($data['contract_id']);
        if (! $contract->is_active || $contract->status !== ContractStatusEnum::ACTIVE) {
            abort(409, 'Hợp đồng chưa hiệu lực.');
        }
        if (isset($data['contract_item_id']) && $data['contract_item_id'] !== null && ! ContractItem::query()->whereKey($data['contract_item_id'])->where('contract_id', $contract->id)->exists()) {
            abort(422, 'Hạng mục không thuộc hợp đồng.');
        }
    }

    private function assertPlannedWithoutOrder(TripSchedule $schedule): void
    {
        if ($schedule->status !== TripScheduleStatusEnum::PLANNED || $schedule->dispatchOrder()->exists()) {
            abort(409, 'Chỉ lịch chờ phân công chưa có lệnh mới được chỉnh sửa.');
        }
    }

    private function createAssignment(TripSchedule $schedule, array $data, User $actor, TripAssignmentTypeEnum $type): TripAssignment
    {
        $vehicle = Vehicle::query()->findOrFail($data['vehicle_id']);
        $driver = Driver::query()->findOrFail($data['driver_id']);
        if (! $vehicle->is_active || $vehicle->vehicle_status !== VehicleStatusEnum::AVAILABLE || $vehicle->vehicle_type_id !== $schedule->required_vehicle_type_id) {
            abort(422, 'Xe không khả dụng hoặc không đúng loại xe yêu cầu.');
        }
        if (! $driver->is_active || ($driver->left_at !== null && $driver->left_at->isPast()) || ($driver->license_expired_at !== null && $driver->license_expired_at->isPast())) {
            abort(422, 'Tài xế không khả dụng.');
        }
        if ($vehicle->ownership_type === OwnershipTypeEnum::PARTNER && (int) $data['partner_id'] !== (int) $vehicle->partner_id) {
            abort(422, 'Đối tác phải trùng với đối tác của xe.');
        }
        $conflicts = TripAssignment::query()->where('is_current', true)->where(function (Builder $query) use ($vehicle, $driver): void {
            $query->where('vehicle_id', $vehicle->id)->orWhere('driver_id', $driver->id);
        })->whereHas('tripSchedule', function (Builder $query) use ($schedule): void {
            $query->where('scheduled_start_at', '<', $schedule->scheduled_end_at)->where('scheduled_end_at', '>', $schedule->scheduled_start_at)->whereNotIn('status', [TripScheduleStatusEnum::COMPLETED, TripScheduleStatusEnum::CANCELLED]);
        })->exists();
        if ($conflicts) {
            abort(409, 'Xe hoặc tài xế đã được phân công cho lịch giao thời gian.');
        }

        return TripAssignment::create(['trip_schedule_id' => $schedule->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'partner_id' => $data['partner_id'] ?? $vehicle->partner_id, 'assignment_type' => $type, 'replaced_assignment_id' => $data['replaced_assignment_id'] ?? null, 'replace_reason' => $data['replace_reason'] ?? null, 'assigned_at' => now(), 'assigned_by' => $actor->user_name, 'is_current' => true]);
    }

    private function assertActionActor(DispatchOrder $order, User $actor): void
    {
        if ($actor->can('dispatch-orders.manage')) {
            return;
        }
        $driver = $this->driverFor($actor);
        if (! $actor->can('driver-orders.manage') || $order->tripAssignment?->driver_id !== $driver->id) {
            abort(403, 'Bạn không có quyền thao tác lệnh điều xe này.');
        }
    }

    private function driverFor(User $actor): Driver
    {
        $driver = $actor->driver;
        if ($driver === null || ! $driver->is_active) {
            abort(403, 'Tài khoản chưa được liên kết với hồ sơ tài xế đang hoạt động.');
        }

        return $driver;
    }

    private function orderTransition(DispatchOrder $order, DispatchOrderStatusEnum $from, DispatchOrderStatusEnum $to): array
    {
        return DB::transaction(function () use ($order, $from, $to): array {
            $locked = DispatchOrder::query()->with('tripSchedule')->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== $from) {
                abort(409, 'Lệnh điều xe không thể chuyển trạng thái hiện tại.');
            }
            $locked->forceFill(['status' => $to])->save();

            return $this->orderData($locked->fresh(self::ORDER_RELATIONS));
        });
    }

    private function scheduleFields(array $data): array
    {
        return collect(['contract_id', 'contract_item_id', 'schedule_rule_id', 'service_type', 'route_id', 'scheduled_start_at', 'scheduled_end_at', 'pickup_location', 'dropoff_location', 'journey', 'required_vehicle_type_id', 'note'])->only(array_keys($data))->all();
    }

    private function scheduleData(TripSchedule $schedule): array
    {
        return ['id' => $schedule->id, 'schedule_no' => $schedule->schedule_no, 'status' => $schedule->status?->value, 'service_type' => $schedule->service_type?->value, 'scheduled_start_at' => $schedule->scheduled_start_at?->toISOString(), 'scheduled_end_at' => $schedule->scheduled_end_at?->toISOString(), 'pickup_location' => $schedule->pickup_location, 'dropoff_location' => $schedule->dropoff_location, 'journey' => $schedule->journey, 'note' => $schedule->note, 'contract_id' => $schedule->contract_id, 'contract_no' => $schedule->contract?->contract_no, 'customer_id' => $schedule->contract?->customer_id, 'customer_name' => $schedule->contract?->customer?->name, 'route_id' => $schedule->route_id, 'route_name' => $schedule->route?->name, 'required_vehicle_type_id' => $schedule->required_vehicle_type_id, 'required_vehicle_type_name' => $schedule->requiredVehicleType?->name, 'assignments' => $schedule->assignments->map(fn (TripAssignment $assignment) => $this->assignmentData($assignment))->all(), 'dispatch_order' => $schedule->dispatchOrder ? $this->orderData($schedule->dispatchOrder->load(self::ORDER_RELATIONS)) : null];
    }

    private function assignmentData(TripAssignment $assignment): array
    {
        return ['id' => $assignment->id, 'assignment_type' => $assignment->assignment_type?->value, 'is_current' => $assignment->is_current, 'assigned_at' => $assignment->assigned_at?->toISOString(), 'replace_reason' => $assignment->replace_reason, 'replaced_assignment_id' => $assignment->replaced_assignment_id, 'vehicle_id' => $assignment->vehicle_id, 'license_plate' => $assignment->vehicle?->license_plate, 'driver_id' => $assignment->driver_id, 'driver_name' => $assignment->driver?->full_name, 'driver_phone' => $assignment->driver?->phone, 'partner_id' => $assignment->partner_id, 'partner_name' => $assignment->partner?->name ?? $assignment->vehicle?->partner?->name];
    }

    private function orderData(DispatchOrder $order, bool $driverView = false): array
    {
        $schedule = $order->tripSchedule;

        return ['id' => $order->id, 'order_no' => $order->order_no, 'status' => $order->status?->value, 'issued_at' => $order->issued_at?->toISOString(), 'actual_start_at' => $order->actual_start_at?->toISOString(), 'actual_end_at' => $order->actual_end_at?->toISOString(), 'start_odometer' => $order->start_odometer, 'end_odometer' => $order->end_odometer, 'actual_distance_km' => $order->actual_distance_km, 'waiting_hours' => $order->waiting_hours, 'customer_amount' => $order->customer_amount, 'partner_vehicle_cost' => $order->partner_vehicle_cost, 'external_driver_cost' => $order->external_driver_cost, 'note' => $order->note, 'trip_schedule_id' => $order->trip_schedule_id, 'schedule_no' => $schedule?->schedule_no, 'service_type' => $schedule?->service_type?->value, 'scheduled_start_at' => $schedule?->scheduled_start_at?->toISOString(), 'scheduled_end_at' => $schedule?->scheduled_end_at?->toISOString(), 'pickup_location' => $schedule?->pickup_location, 'dropoff_location' => $schedule?->dropoff_location, 'journey' => $schedule?->journey, 'route_name' => $schedule?->route?->name, 'customer_name' => $schedule?->contract?->customer?->name, 'customer_phone' => $driverView ? $schedule?->contract?->customer?->phone : null, 'contact_name' => $driverView ? $schedule?->contract?->customer?->contact_name : null, 'assignment' => $order->tripAssignment ? $this->assignmentData($order->tripAssignment) : null];
    }
}
