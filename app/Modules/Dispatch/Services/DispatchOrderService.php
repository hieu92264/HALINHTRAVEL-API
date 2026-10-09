<?php

namespace App\Modules\Dispatch\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Dispatch\DTOs\CompletionConfirmationData;
use App\Modules\Dispatch\DTOs\CompletionReportData;
use App\Modules\Dispatch\DTOs\StartDispatchOrderData;
use App\Modules\Dispatch\Interfaces\DispatchOrderServiceInterface;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\DispatchOrderStatusEnum;
use App\Shared\Enums\TripScheduleStatusEnum;
use Illuminate\Support\Facades\DB;

class DispatchOrderService implements DispatchOrderServiceInterface
{
    public function getList(): array
    {
        return DispatchOrder::query()->with($this->relations())->latest('issued_at')->get()->map(fn (DispatchOrder $order) => $this->present($order))->all();
    }

    public function getDetail(DispatchOrder $order): array
    {
        return $this->present($order->load($this->relations()));
    }

    public function issue(TripSchedule $schedule, User $user): array
    {
        return DB::transaction(function () use ($schedule, $user): array {
            $locked = TripSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            if ($locked->status !== TripScheduleStatusEnum::ASSIGNED) {
                abort(409, 'Chỉ được phát hành lệnh cho lịch đã phân công.');
            }
            if ($locked->dispatchOrders()->whereNot('status', DispatchOrderStatusEnum::CANCELLED->value)->exists()) {
                abort(409, 'Lịch đã có lệnh điều xe đang hiệu lực.');
            }
            $assignment = $locked->assignments()->where('is_current', true)->lockForUpdate()->first();
            if ($assignment === null) {
                abort(409, 'Lịch chưa có phân công hiện hành.');
            }
            $order = DispatchOrder::create(['order_no' => $this->nextNumber(), 'trip_schedule_id' => $locked->id, 'trip_assignment_id' => $assignment->id, 'issued_at' => now(), 'issued_by' => $user->user_name, 'status' => DispatchOrderStatusEnum::ISSUED, 'customer_amount' => 0, 'partner_vehicle_cost' => 0, 'external_driver_cost' => 0, 'waiting_hours' => 0]);

            return $this->present($order->load($this->relations()));
        });
    }

    public function assign(DispatchOrder $order): array
    {
        return $this->transitionAssigned($order);
    }

    public function start(DispatchOrder $order, StartDispatchOrderData $data, User $user): array
    {
        return DB::transaction(function () use ($order, $data, $user): array {
            $locked = $this->locked($order);
            $this->assertDriverOwnership($locked, $user, true);
            if ($locked->status !== DispatchOrderStatusEnum::ASSIGNED) {
                abort(409, 'Lệnh phải ở trạng thái đã phân công trước khi xuất phát.');
            }
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($locked->tripAssignment->vehicle_id);
            if ($data->startOdometer < $vehicle->current_odometer) {
                abort(422, 'ODO bắt đầu không được nhỏ hơn ODO hiện tại của xe.');
            }
            $locked->forceFill([
                'status' => DispatchOrderStatusEnum::IN_PROGRESS,
                'actual_start_at' => $data->actualStartAt,
                'start_odometer' => $data->startOdometer,
                'note' => $data->note,
            ])->save();
            $locked->tripSchedule->forceFill(['status' => TripScheduleStatusEnum::IN_PROGRESS])->save();

            return $this->present($locked->fresh()->load($this->relations()));
        });
    }

    public function reportCompletion(DispatchOrder $order, CompletionReportData $data, User $user): array
    {
        return DB::transaction(function () use ($order, $data, $user): array {
            $locked = $this->locked($order);
            $this->assertDriverOwnership($locked, $user, true);
            if ($locked->status !== DispatchOrderStatusEnum::IN_PROGRESS) {
                abort(409, 'Chỉ có thể báo hoàn tất khi chuyến đang chạy.');
            }
            if ($locked->actual_start_at === null || $locked->start_odometer === null) {
                abort(409, 'Lệnh chưa có dữ liệu bắt đầu chuyến.');
            }
            if ($data->endOdometer < $locked->start_odometer) {
                abort(422, 'ODO kết thúc không được nhỏ hơn ODO bắt đầu.');
            }
            if ($data->actualEndAt < $locked->actual_start_at->toDateTimeString()) {
                abort(422, 'Thời điểm kết thúc không được trước lúc xuất phát.');
            }
            $locked->forceFill(['actual_end_at' => $data->actualEndAt, 'end_odometer' => $data->endOdometer, 'actual_distance_km' => $data->actualDistanceKm, 'waiting_hours' => $data->waitingHours ?? 0, 'note' => $data->note ?? $locked->note, 'reported_at' => now(), 'reported_by' => $user->user_name, 'review_note' => null, 'status' => DispatchOrderStatusEnum::PENDING_CONFIRMATION])->save();

            return $this->present($locked->fresh()->load($this->relations()));
        });
    }

    public function confirmCompletion(DispatchOrder $order, CompletionConfirmationData $data, User $user): array
    {
        return DB::transaction(function () use ($order, $data, $user): array {
            $locked = $this->locked($order);
            if ($locked->status !== DispatchOrderStatusEnum::PENDING_CONFIRMATION) {
                abort(409, 'Lệnh chưa có báo cáo chờ xác nhận.');
            }
            if ($locked->actual_end_at === null || $locked->end_odometer === null || $locked->start_odometer === null || $locked->end_odometer < $locked->start_odometer) {
                abort(422, 'Báo cáo vận hành không hợp lệ.');
            }
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($locked->tripAssignment->vehicle_id);
            if ($locked->end_odometer < $vehicle->current_odometer) {
                abort(422, 'ODO kết thúc không được nhỏ hơn ODO hiện tại của xe.');
            }
            $vehicle->forceFill(['current_odometer' => $locked->end_odometer])->save();
            $locked->forceFill(['customer_amount' => $data->customerAmount, 'partner_vehicle_cost' => $data->partnerVehicleCost, 'external_driver_cost' => $data->externalDriverCost, 'note' => $data->note ?? $locked->note, 'confirmed_at' => now(), 'confirmed_by' => $user->user_name, 'completed_at' => now(), 'status' => DispatchOrderStatusEnum::COMPLETED])->save();
            $locked->tripSchedule->forceFill(['status' => TripScheduleStatusEnum::COMPLETED])->save();

            return $this->present($locked->fresh()->load($this->relations()));
        });
    }

    public function returnCompletion(DispatchOrder $order, string $note): array
    {
        return DB::transaction(function () use ($order, $note): array {
            $locked = $this->locked($order);
            if ($locked->status !== DispatchOrderStatusEnum::PENDING_CONFIRMATION) {
                abort(409, 'Chỉ có thể trả lại báo cáo đang chờ xác nhận.');
            }
            $locked->forceFill(['status' => DispatchOrderStatusEnum::IN_PROGRESS, 'review_note' => $note])->save();

            return $this->present($locked->fresh()->load($this->relations()));
        });
    }

    public function cancel(DispatchOrder $order, string $note): array
    {
        return DB::transaction(function () use ($order, $note): array {
            $locked = $this->locked($order);
            if (! in_array($locked->status, [DispatchOrderStatusEnum::ISSUED, DispatchOrderStatusEnum::ASSIGNED], true)) {
                abort(409, 'Không thể hủy lệnh khi chuyến đã bắt đầu hoặc đã hoàn tất.');
            }
            $locked->forceFill(['status' => DispatchOrderStatusEnum::CANCELLED, 'note' => $note])->save();
            $locked->tripSchedule->forceFill(['status' => TripScheduleStatusEnum::ASSIGNED])->save();

            return $this->present($locked->fresh()->load($this->relations()));
        });
    }

    public function getMyOrders(User $user): array
    {
        $driver = $user->driver;
        if ($driver === null) {
            abort(403, 'Tài khoản chưa liên kết với tài xế.');
        }

        return DispatchOrder::query()->whereHas('tripAssignment', fn ($query) => $query->where('driver_id', $driver->id))->with($this->relations())->latest('issued_at')->get()->map(fn (DispatchOrder $order) => $this->driverPresent($order))->all();
    }

    public function getMyOrder(DispatchOrder $order, User $user): array
    {
        $this->assertDriverOwnership($order->load('tripAssignment.driver'), $user);

        return $this->driverPresent($order->load($this->relations()));
    }

    private function transitionAssigned(DispatchOrder $order): array
    {
        return DB::transaction(function () use ($order): array {
            $locked = $this->locked($order);
            if ($locked->status !== DispatchOrderStatusEnum::ISSUED) {
                abort(409, 'Chỉ có thể nhận lệnh mới phát hành.');
            }
            $locked->forceFill(['status' => DispatchOrderStatusEnum::ASSIGNED])->save();

            return $this->present($locked->fresh()->load($this->relations()));
        });
    }

    private function locked(DispatchOrder $order): DispatchOrder
    {
        return DispatchOrder::query()->with(['tripSchedule', 'tripAssignment.driver', 'tripAssignment.vehicle'])->lockForUpdate()->findOrFail($order->id);
    }

    private function assertDriverOwnership(DispatchOrder $order, User $user, bool $requireCurrentAssignment = false): void
    {
        $driver = $user->driver;
        if ($driver === null || $order->tripAssignment->driver_id !== $driver->id || ($requireCurrentAssignment && ! $order->tripAssignment->is_current)) {
            abort(403, 'Bạn không sở hữu lệnh điều xe này.');
        }
    }

    private function nextNumber(): string
    {
        $prefix = 'LDX'.now()->format('Ymd');
        $highest = DispatchOrder::query()->where('order_no', 'like', $prefix.'%')->lockForUpdate()->pluck('order_no')->map(fn (string $number) => (int) substr($number, strlen($prefix)))->max() ?? 0;

        return $prefix.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }

    private function relations(): array
    {
        return ['tripSchedule.contract:id,contract_no,customer_id', 'tripSchedule.contract.customer:id,name,contact_name,phone,email', 'tripSchedule.route:id,name', 'tripSchedule.requiredVehicleType:id,name', 'tripAssignment.vehicle:id,license_plate,current_odometer', 'tripAssignment.driver:id,code,full_name,user_name', 'tripAssignment.partner:id,name', 'issuedBy:user_name,email', 'reportedBy:user_name,email', 'confirmedBy:user_name,email'];
    }

    private function present(DispatchOrder $order): array
    {
        return $order->toArray();
    }

    private function driverPresent(DispatchOrder $order): array
    {
        $data = $this->present($order);
        unset($data['customer_amount'], $data['partner_vehicle_cost'], $data['external_driver_cost'], $data['confirmed_at'], $data['confirmed_by'], $data['confirmed_by']);

        return $data;
    }
}
