<?php

namespace App\Modules\DriverPayroll\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\DriverPayroll\Interfaces\DriverPayrollServiceInterface;
use App\Modules\DriverPayroll\Models\DriverAdvance;
use App\Modules\DriverPayroll\Models\DriverAttendance;
use App\Modules\DriverPayroll\Models\Payroll;
use App\Modules\DriverPayroll\Models\PayrollItem;
use App\Modules\DriverPayroll\Models\PayrollItemDetail;
use App\Modules\MasterData\Models\Driver;
use App\Shared\Enums\DispatchOrderStatusEnum;
use App\Shared\Enums\DriverAdvanceStatusEnum;
use App\Shared\Enums\DriverAttendanceStatusEnum;
use App\Shared\Enums\OwnershipTypeEnum;
use App\Shared\Enums\PayrollCalculationTypeEnum;
use App\Shared\Enums\PayrollStatusEnum;
use App\Shared\Enums\RentalServiceTypeEnum;
use App\Shared\Enums\WorkTypeEnum;
use Illuminate\Support\Facades\DB;

class DriverPayrollService implements DriverPayrollServiceInterface
{
    public function advances(): array
    {
        return DriverAdvance::query()->with(['driver', 'payroll'])->where('is_active', true)->orderByDesc('advance_date')->get()->map(fn (DriverAdvance $item) => $this->advanceData($item))->all();
    }

    public function advance(DriverAdvance $advance): array
    {
        return $this->advanceData($advance->load(['driver', 'payroll']));
    }

    public function createAdvance(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $this->activeDriver($data['driver_id']);
            $item = DriverAdvance::create([...$data, 'advance_no' => $this->nextAdvanceNo(), 'status' => DriverAdvanceStatusEnum::PENDING]);

            return $this->advance($item);
        });
    }

    public function updateAdvance(DriverAdvance $advance, array $data): array
    {
        $this->assertAdvancePending($advance);
        if (isset($data['driver_id'])) {
            $this->activeDriver($data['driver_id']);
        } $advance->fill($data)->save();

        return $this->advance($advance->fresh());
    }

    public function deactivateAdvance(DriverAdvance $advance): void
    {
        $this->assertAdvancePending($advance);
        $advance->forceFill(['is_active' => false])->save();
    }

    public function confirmAdvance(DriverAdvance $advance, User $user): array
    {
        return DB::transaction(function () use ($advance, $user): array {
            $locked = DriverAdvance::query()->lockForUpdate()->findOrFail($advance->id);
            $this->assertAdvancePending($locked);
            $locked->forceFill(['status' => DriverAdvanceStatusEnum::CONFIRMED, 'approved_by' => $user->user_name])->save();

            return $this->advance($locked->fresh());
        });
    }

    public function attendances(): array
    {
        return DriverAttendance::query()->with(['driver', 'dispatchOrder'])->where('is_active', true)->orderByDesc('work_date')->get()->map(fn (DriverAttendance $item) => $this->attendanceData($item))->all();
    }

    public function attendance(DriverAttendance $attendance): array
    {
        return $this->attendanceData($attendance->load(['driver', 'dispatchOrder']));
    }

    public function createAttendance(DispatchOrder $order, array $data): array
    {
        return DB::transaction(function () use ($order, $data): array {
            $loaded = $order->load(['tripAssignment.driver', 'tripAssignment.vehicle.vehicleType', 'tripSchedule.contractItem']);
            if ($loaded->status !== DispatchOrderStatusEnum::COMPLETED) {
                abort(409, 'Chỉ lệnh đã hoàn thành mới tạo được công chuyến.');
            } if ($loaded->tripAssignment?->driver === null || DriverAttendance::query()->where('dispatch_order_id', $loaded->id)->exists()) {
                abort(409, 'Lệnh điều xe đã có công chuyến hoặc thiếu tài xế.');
            } $service = $loaded->tripSchedule?->service_type;
            $driver = $loaded->tripAssignment->driver;
            $units = (string) ($data['work_units'] ?? 1);
            if (in_array($service, [RentalServiceTypeEnum::FIXED, RentalServiceTypeEnum::SCHOOL], true)) {
                $type = WorkTypeEnum::FIXED_TRIP;
                $base = '1';
                $rate = (string) ($loaded->tripSchedule?->contractItem?->driver_wage ?? 0);
                $wage = (float) $units * (float) $rate;
            } elseif ($service === RentalServiceTypeEnum::TOURISM) {
                $type = WorkTypeEnum::TOURISM_TRIP;
                $base = (string) ($loaded->customer_amount ?? 0);
                $rate = (string) (($loaded->tripAssignment?->vehicle?->vehicleType?->tour_driver_commission_rate ?? 0) / 100);
                $wage = (float) $base * (float) $rate;
            } else {
                if (! array_key_exists('rate', $data)) {
                    abort(422, 'Chuyến công tác yêu cầu đơn giá công.');
                } $type = WorkTypeEnum::OTHER;
                $base = $units;
                $rate = (string) $data['rate'];
                $wage = (float) $base * (float) $rate;
            } $item = DriverAttendance::create(['driver_id' => $driver->id, 'dispatch_order_id' => $loaded->id, 'work_date' => ($loaded->actual_end_at ?? $loaded->completed_at ?? now())->toDateString(), 'work_type' => $type, 'work_units' => $units, 'base_amount' => $base, 'rate' => $rate, 'calculated_wage' => number_format($wage, 2, '.', ''), 'status' => DriverAttendanceStatusEnum::PENDING]);

            return $this->attendance($item);
        });
    }

    public function updateAttendance(DriverAttendance $attendance, array $data): array
    {
        if ($attendance->status !== DriverAttendanceStatusEnum::PENDING) {
            abort(409, 'Chỉ công chờ xác nhận mới được cập nhật.');
        } $attendance->fill($data);
        $attendance->forceFill(['calculated_wage' => number_format((float) $attendance->work_units * (float) $attendance->rate, 2, '.', '')])->save();

        return $this->attendance($attendance->fresh());
    }

    public function confirmAttendance(DriverAttendance $attendance): array
    {
        if ($attendance->status !== DriverAttendanceStatusEnum::PENDING) {
            abort(409, 'Công chuyến không thể xác nhận.');
        } $attendance->forceFill(['status' => DriverAttendanceStatusEnum::CONFIRMED])->save();

        return $this->attendance($attendance->fresh());
    }

    public function payrolls(): array
    {
        return Payroll::query()->withCount('items')->where('is_active', true)->orderByDesc('year')->orderByDesc('month')->get()->map(fn (Payroll $item) => $this->payrollData($item))->all();
    }

    public function payroll(Payroll $payroll): array
    {
        return $this->payrollData($payroll->load(['items.driver', 'items.details.driverAttendance', 'items.details.dispatchOrder']));
    }

    public function createPayroll(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            if (Payroll::query()->where('month', $data['month'])->where('year', $data['year'])->exists()) {
                abort(409, 'Kỳ lương tháng/năm này đã tồn tại.');
            } $item = Payroll::create([...$data, 'code' => sprintf('LUONG-%04d-%02d', $data['year'], $data['month']), 'status' => PayrollStatusEnum::DRAFT]);

            return $this->payroll($item);
        });
    }

    public function updatePayroll(Payroll $payroll, array $data): array
    {
        if ($payroll->status !== PayrollStatusEnum::DRAFT) {
            abort(409, 'Chỉ kỳ lương nháp mới được cập nhật.');
        } $payroll->fill($data)->save();

        return $this->payroll($payroll->fresh());
    }

    public function calculate(Payroll $payroll): array
    {
        return DB::transaction(function () use ($payroll): array {
            $locked = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);
            if (! in_array($locked->status, [PayrollStatusEnum::DRAFT, PayrollStatusEnum::CALCULATED], true)) {
                abort(409, 'Kỳ lương không thể tính lại.');
            } $locked->items()->delete();
            $drivers = Driver::query()->where('is_active', true)->where('type', OwnershipTypeEnum::COMPANY)->whereDate('joined_at', '<=', $locked->to_date)->get();
            foreach ($drivers as $driver) {
                $attendances = DriverAttendance::query()->where('driver_id', $driver->id)->where('status', DriverAttendanceStatusEnum::CONFIRMED)->whereBetween('work_date', [$locked->from_date, $locked->to_date])->get();
                $advances = DriverAdvance::query()->where('driver_id', $driver->id)->where('status', DriverAdvanceStatusEnum::CONFIRMED)->whereBetween('advance_date', [$locked->from_date, $locked->to_date])->get();
                $fixed = $attendances->where('work_type', WorkTypeEnum::FIXED_TRIP)->sum('calculated_wage');
                $tourism = $attendances->where('work_type', WorkTypeEnum::TOURISM_TRIP)->sum('calculated_wage');
                $other = $attendances->where('work_type', WorkTypeEnum::OTHER)->sum('calculated_wage');
                $advance = $advances->sum('amount');
                $gross = (float) $driver->base_salary + (float) $driver->responsibility_allowance + (float) $fixed + (float) $tourism + (float) $other;
                $item = PayrollItem::create(['payroll_id' => $locked->id, 'driver_id' => $driver->id, 'base_salary' => $driver->base_salary ?? 0, 'responsibility_allowance' => $driver->responsibility_allowance ?? 0, 'fixed_trip_wage' => $fixed, 'tourism_commission' => $tourism, 'other_allowance' => $other, 'advance_amount' => $advance, 'gross_salary' => $gross, 'net_salary' => $gross - (float) $advance]);
                foreach ($attendances as $attendance) {
                    PayrollItemDetail::create(['payroll_item_id' => $item->id, 'driver_attendance_id' => $attendance->id, 'dispatch_order_id' => $attendance->dispatch_order_id, 'calculation_type' => $attendance->work_type === WorkTypeEnum::TOURISM_TRIP ? PayrollCalculationTypeEnum::TOURISM_COMMISSION : PayrollCalculationTypeEnum::FIXED_TRIP, 'base_amount' => $attendance->base_amount, 'rate' => $attendance->rate, 'amount' => $attendance->calculated_wage]);
                }
            } $locked->forceFill(['status' => PayrollStatusEnum::CALCULATED])->save();

            return $this->payroll($locked->fresh());
        });
    }

    public function updatePayrollItem(Payroll $payroll, PayrollItem $item, array $data): array
    {
        if ($item->payroll_id !== $payroll->id || $payroll->status !== PayrollStatusEnum::CALCULATED) {
            abort(409, 'Chỉ có thể điều chỉnh item của kỳ lương đã tính.');
        } $item->fill($data);
        $gross = (float) $item->base_salary + (float) $item->responsibility_allowance + (float) $item->meal_allowance + (float) $item->fixed_trip_wage + (float) $item->tourism_commission + (float) $item->other_allowance;
        $item->forceFill(['gross_salary' => $gross, 'net_salary' => $gross - (float) $item->advance_amount - (float) $item->deduction_amount])->save();

        return $this->payroll($payroll->fresh());
    }

    public function approve(Payroll $payroll, User $user): array
    {
        if ($payroll->status !== PayrollStatusEnum::CALCULATED) {
            abort(409, 'Kỳ lương phải được tính trước khi duyệt.');
        } $payroll->forceFill(['status' => PayrollStatusEnum::APPROVED, 'approved_by' => $user->user_name, 'approved_at' => now()])->save();

        return $this->payroll($payroll->fresh());
    }

    public function markPaid(Payroll $payroll): array
    {
        if ($payroll->status !== PayrollStatusEnum::APPROVED) {
            abort(409, 'Kỳ lương phải được duyệt trước khi đánh dấu đã trả.');
        } $payroll->forceFill(['status' => PayrollStatusEnum::PAID])->save();

        return $this->payroll($payroll->fresh());
    }

    public function lock(Payroll $payroll): array
    {
        return DB::transaction(function () use ($payroll): array {
            $locked = Payroll::query()->with('items')->lockForUpdate()->findOrFail($payroll->id);
            if ($locked->status !== PayrollStatusEnum::PAID) {
                abort(409, 'Kỳ lương phải được đánh dấu đã trả trước khi khóa.');
            } foreach ($locked->items as $item) {
                $ids = $item->details()->pluck('driver_attendance_id')->filter();
                DriverAttendance::query()->whereIn('id', $ids)->update(['status' => DriverAttendanceStatusEnum::PAYROLL_LOCKED]);
                DriverAdvance::query()->where('driver_id', $item->driver_id)->where('status', DriverAdvanceStatusEnum::CONFIRMED)->whereBetween('advance_date', [$locked->from_date, $locked->to_date])->update(['status' => DriverAdvanceStatusEnum::PAYROLL_LOCKED, 'payroll_id' => $locked->id]);
            } $locked->forceFill(['status' => PayrollStatusEnum::LOCKED])->save();

            return $this->payroll($locked->fresh());
        });
    }

    private function activeDriver(int $id): Driver
    {
        return Driver::query()->whereKey($id)->where('is_active', true)->firstOrFail();
    }

    private function assertAdvancePending(DriverAdvance $advance): void
    {
        if (! $advance->is_active || $advance->status !== DriverAdvanceStatusEnum::PENDING) {
            abort(409, 'Chỉ tạm ứng chờ xác nhận mới được thay đổi.');
        }
    }

    private function nextAdvanceNo(): string
    {
        $prefix = 'TU'.now()->format('Y');
        $max = DriverAdvance::query()->lockForUpdate()->where('advance_no', 'like', $prefix.'%')->pluck('advance_no')->map(fn (string $code) => (int) substr($code, strlen($prefix)))->max() ?? 0;

        return $prefix.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }

    private function advanceData(DriverAdvance $item): array
    {
        return ['id' => $item->id, 'advance_no' => $item->advance_no, 'driver_id' => $item->driver_id, 'driver_name' => $item->driver?->full_name, 'advance_date' => $item->advance_date?->toDateString(), 'amount' => $item->amount, 'description' => $item->description, 'status' => $item->status?->value, 'payroll_id' => $item->payroll_id, 'payroll_code' => $item->payroll?->code, 'is_active' => $item->is_active];
    }

    private function attendanceData(DriverAttendance $item): array
    {
        return ['id' => $item->id, 'driver_id' => $item->driver_id, 'driver_name' => $item->driver?->full_name, 'dispatch_order_id' => $item->dispatch_order_id, 'dispatch_order_no' => $item->dispatchOrder?->order_no, 'work_date' => $item->work_date?->toDateString(), 'work_type' => $item->work_type?->value, 'work_units' => $item->work_units, 'base_amount' => $item->base_amount, 'rate' => $item->rate, 'calculated_wage' => $item->calculated_wage, 'status' => $item->status?->value];
    }

    private function payrollData(Payroll $item): array
    {
        $items = $item->relationLoaded('items') ? $item->items : collect();

        return ['id' => $item->id, 'code' => $item->code, 'month' => $item->month, 'year' => $item->year, 'from_date' => $item->from_date?->toDateString(), 'to_date' => $item->to_date?->toDateString(), 'status' => $item->status?->value, 'total_gross' => (string) $items->sum('gross_salary'), 'total_net' => (string) $items->sum('net_salary'), 'items' => $items->map(fn (PayrollItem $row) => ['id' => $row->id, 'driver_id' => $row->driver_id, 'driver_name' => $row->driver?->full_name, 'base_salary' => $row->base_salary, 'responsibility_allowance' => $row->responsibility_allowance, 'meal_allowance' => $row->meal_allowance, 'fixed_trip_wage' => $row->fixed_trip_wage, 'tourism_commission' => $row->tourism_commission, 'other_allowance' => $row->other_allowance, 'advance_amount' => $row->advance_amount, 'deduction_amount' => $row->deduction_amount, 'gross_salary' => $row->gross_salary, 'net_salary' => $row->net_salary, 'note' => $row->note, 'details' => $row->details->map(fn (PayrollItemDetail $detail) => ['id' => $detail->id, 'calculation_type' => $detail->calculation_type?->value, 'base' => $detail->base_amount, 'rate' => $detail->rate, 'amount' => $detail->amount, 'driver_attendance_id' => $detail->driver_attendance_id, 'dispatch_order_no' => $detail->dispatchOrder?->order_no, 'work_date' => $detail->driverAttendance?->work_date?->toDateString()])->all()])->all()];
    }
}
