<?php

namespace App\Modules\VehicleOperations\Services;

use App\Modules\DriverPayroll\Models\DriverAttendance;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicleRate;
use App\Modules\VehicleOperations\Models\WorkerVehicleScheduleRule;
use App\Shared\Enums\DriverAttendanceStatusEnum;
use App\Shared\Enums\OwnershipTypeEnum;
use App\Shared\Enums\WorkTypeEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VehicleOperationService
{
    /** @return list<array<string, mixed>> */
    public function runs(string $kind, array $filters = []): array
    {
        $query = $this->modelClass($kind)::query()->with($kind === 'worker' ? ['customer:id,name', 'route:id,name', 'partner:id,name', 'driver:id,full_name'] : ['customer:id,name', 'partner:id,name', 'driver:id,full_name']);
        foreach (['status', 'customer_id', 'route_id', 'driver_id', 'license_plate'] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->where($field, $filters[$field]);
            }
        }
        if (filled($filters['from_date'] ?? null)) {
            $query->whereDate('run_date', '>=', $filters['from_date']);
        }
        if (filled($filters['to_date'] ?? null)) {
            $query->whereDate('run_date', '<=', $filters['to_date']);
        }

        return $query->latest('run_date')->latest('pickup_time')->get()->map(fn (Model $run) => $this->run($run))->all();
    }

    /** @return array<string, mixed> */
    public function workerRun(WorkerVehicle $run): array
    {
        return $this->run($run);
    }

    /** @return array<string, mixed> */
    public function touristRun(TouristVehicle $run): array
    {
        return $this->run($run);
    }

    /** @return array<string, mixed> */
    public function startWorkerRun(WorkerVehicle $run, ?string $startedAt = null): array
    {
        if ($run->status !== 'planned') {
            abort(409, 'Chỉ xe chạy theo kế hoạch mới được bắt đầu.');
        }
        $run->forceFill(['status' => 'in_progress', 'actual_started_at' => $startedAt ?? now()])->save();

        return $this->run($run->fresh());
    }

    /** @return array<string, mixed> */
    public function completeWorkerRun(WorkerVehicle $run, ?string $completedAt = null): array
    {
        return DB::transaction(function () use ($run, $completedAt): array {
            $locked = WorkerVehicle::query()->lockForUpdate()->findOrFail($run->id);
            if ($locked->status === 'cancelled') {
                abort(409, 'Không thể hoàn thành chuyến đã hủy.');
            }
            $at = $completedAt ?? now()->toDateTimeString();
            $locked->forceFill(['status' => 'completed', 'completed_at' => $at, 'actual_completed_at' => $at, 'actual_started_at' => $locked->actual_started_at ?? $locked->run_date->toDateString().' '.$locked->pickup_time])->save();
            $driver = $locked->driver_id ? Driver::query()->find($locked->driver_id) : null;
            if ($locked->is_company_vehicle && ! $locked->is_hired_driver && $driver?->type === OwnershipTypeEnum::COMPANY) {
                DriverAttendance::query()->firstOrCreate(['worker_vehicle_id' => $locked->id, 'driver_id' => $driver->id], ['work_date' => $locked->actual_completed_at->toDateString(), 'work_type' => WorkTypeEnum::FIXED_TRIP, 'work_units' => 1, 'base_amount' => $locked->driver_wage, 'rate' => 1, 'calculated_wage' => $locked->driver_wage, 'status' => DriverAttendanceStatusEnum::PENDING]);
            }

            return $this->run($locked->fresh());
        });
    }

    /** @return array<string, mixed> */
    public function startTouristRun(TouristVehicle $run, ?string $startedAt = null): array
    {
        if ($run->status !== 'planned') {
            abort(409, 'Chỉ xe chạy theo kế hoạch mới được bắt đầu.');
        }
        $run->forceFill(['status' => 'in_progress', 'actual_started_at' => $startedAt ?? now()])->save();

        return $this->run($run->fresh());
    }

    /** @return array<string, mixed> */
    public function completeTouristRun(TouristVehicle $run, ?string $completedAt = null): array
    {
        return DB::transaction(function () use ($run, $completedAt): array {
            $locked = TouristVehicle::query()->lockForUpdate()->findOrFail($run->id);
            if ($locked->status === 'cancelled') {
                abort(409, 'Không thể hoàn thành chuyến đã hủy.');
            }
            $at = $completedAt ?? now()->toDateTimeString();
            $locked->forceFill(['status' => 'completed', 'completed_at' => $at, 'actual_completed_at' => $at, 'actual_started_at' => $locked->actual_started_at ?? $locked->run_date->toDateString().' '.$locked->pickup_time])->save();
            $driver = $locked->driver_id ? Driver::query()->find($locked->driver_id) : null;
            if ($locked->is_company_vehicle && ! $locked->is_hired_driver && $driver?->type === OwnershipTypeEnum::COMPANY) {
                DriverAttendance::query()->firstOrCreate(['tourist_vehicle_id' => $locked->id, 'driver_id' => $driver->id], ['work_date' => $locked->actual_completed_at->toDateString(), 'work_type' => WorkTypeEnum::TOURISM_TRIP, 'work_units' => 1, 'base_amount' => $locked->freight_amount, 'rate' => $locked->driver_wage_rate, 'calculated_wage' => $locked->driver_wage, 'status' => DriverAttendanceStatusEnum::PENDING]);
            }

            return $this->run($locked->fresh());
        });
    }

    /** @return array<string, mixed> */
    public function createRun(string $kind, array $input): array
    {
        $data = $this->validatedRun($kind, $input, true);

        return DB::transaction(function () use ($kind, $data): array {
            $this->assertNoConflict($kind, $data);
            if ($kind === 'tourist') {
                $this->syncTouristWage($data, $data);
            }
            $data['run_no'] = $this->nextRunNo($kind);

            return $this->run($this->modelClass($kind)::query()->create($data));
        });
    }

    /** @return array<string, mixed> */
    public function updateRun(string $kind, Model $run, array $input): array
    {
        $data = $this->validatedRun($kind, [...$run->getAttributes(), ...$input], false);
        // A generated worker run must remain attached to its source rule when edited individually.
        if ($kind === 'worker' && $run instanceof WorkerVehicle) {
            $data['schedule_rule_id'] = $run->schedule_rule_id;
        }

        return DB::transaction(function () use ($kind, $run, $data, $input): array {
            $this->assertNoConflict($kind, $data, $run->id);
            if ($kind === 'tourist') {
                $this->syncTouristWage($data, $input, $run instanceof TouristVehicle ? $run : null);
            }
            $run->fill($data)->save();

            return $this->run($run->fresh());
        });
    }

    public function deleteRun(Model $run): void
    {
        DB::transaction(function () use ($run): void {
            $locked = $run->newQuery()->lockForUpdate()->findOrFail($run->id);
            if ($locked->status !== 'planned') {
                abort(409, 'Chỉ có thể hủy chuyến đang ở trạng thái Kế hoạch.');
            }
            $locked->forceFill(['status' => 'cancelled'])->save();
        });
    }

    /** @return list<array<string, mixed>> */
    public function rates(): array
    {
        return WorkerVehicleRate::query()->with(['route:id,name', 'vehicle:id,license_plate,seat_count,vehicle_model'])->orderByDesc('effective_from')->get()->map(fn (WorkerVehicleRate $rate) => $this->rate($rate))->all();
    }

    /** @return array<string, mixed> */
    public function saveRate(array $input, ?WorkerVehicleRate $rate = null): array
    {
        $data = Validator::make($input, ['route_id' => ['required', 'integer', Rule::exists('routes', 'id')], 'vehicle_id' => ['nullable', 'integer', Rule::exists('vehicles', 'id')], 'license_plate' => ['nullable', 'string', 'max:50'], 'freight_amount' => ['required', 'numeric', 'min:0'], 'driver_wage' => ['required', 'numeric', 'min:0'], 'effective_from' => ['required', 'date'], 'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from']])->validate();
        $this->applyVehicleSnapshot($data, false);
        $this->assertRatePeriod($data, $rate?->id);
        $rate ??= new WorkerVehicleRate;
        $rate->fill($data)->save();

        return $this->rate($rate->fresh());
    }

    /** @return list<array<string, mixed>> */
    public function rules(): array
    {
        return WorkerVehicleScheduleRule::query()->with(['days', 'runs', 'vehicle:id,license_plate,seat_count,vehicle_model', 'customer:id,name', 'route:id,name', 'partner:id,name', 'driver:id,full_name'])->latest()->get()->map(fn (WorkerVehicleScheduleRule $rule) => $this->rule($rule))->all();
    }

    /** @return array<string, mixed> */
    public function saveRule(array $input, ?WorkerVehicleScheduleRule $rule = null): array
    {
        $data = $this->validatedRule($input);

        return DB::transaction(function () use ($data, $rule): array {
            $this->assertRuleHasRate($data);
            $this->assertRuleDoesNotConflictWithTouristRun($data);
            $rule ??= new WorkerVehicleScheduleRule;
            $days = $data['days'];
            unset($data['days']);
            $rule->fill($data)->save();
            $rule->days()->delete();
            $rule->days()->createMany($days);
            $this->syncPlannedRuns($rule);

            return $this->rule($rule->fresh()->load(['days', 'runs', 'vehicle:id,license_plate,seat_count,vehicle_model', 'customer:id,name', 'route:id,name', 'partner:id,name', 'driver:id,full_name']));
        });
    }

    public function deactivateRule(WorkerVehicleScheduleRule $rule): void
    {
        DB::transaction(function () use ($rule): void {
            $rule->forceFill(['is_active' => false])->save();
            $this->syncPlannedRuns($rule->fresh());
        });
    }

    /** @return array{created: list<array<string,mixed>>, skipped: list<string>} */
    public function generateRuns(WorkerVehicleScheduleRule $rule, string $from, string $to): array
    {
        $rule->loadMissing('days');
        if (! $rule->is_active) {
            abort(409, 'Quy tắc lịch đã ngừng hoạt động.');
        }
        $created = [];
        $skipped = [];
        for ($date = CarbonImmutable::parse($from)->startOfDay(), $end = CarbonImmutable::parse($to)->startOfDay(); $date->lte($end); $date = $date->addDay()) {
            foreach ($rule->days->where('weekday', $date->dayOfWeekIso) as $day) {
                if ($date->lt($rule->effective_from) || ($rule->effective_to && $date->gt($rule->effective_to))) {
                    continue;
                }
                $existing = WorkerVehicle::query()->where('schedule_rule_id', $rule->id)->whereDate('run_date', $date)->where('pickup_time', $day->pickup_time)->first();
                if ($existing && $existing->status !== 'planned') {
                    $skipped[] = $date->toDateString();

                    continue;
                }
                $rate = $this->applicableRate($rule->route_id, $rule->vehicle_id, $rule->license_plate, $date->toDateString());
                if (! $rate) {
                    $skipped[] = $date->toDateString();

                    continue;
                }
                $data = ['vehicle_id' => $rule->vehicle_id, 'customer_id' => $rule->customer_id, 'route_id' => $rule->route_id, 'shift_name' => $rule->shift_name, 'run_date' => $date->toDateString(), 'return_date' => $date->toDateString(), 'pickup_time' => $day->pickup_time, 'return_time' => $day->return_time, 'pickup_location' => $rule->pickup_location, 'dropoff_location' => $rule->dropoff_location, 'is_company_vehicle' => $rule->is_company_vehicle, 'partner_id' => $rule->partner_id, 'license_plate' => $rule->license_plate, 'driver_id' => $rule->driver_id, 'is_hired_driver' => $rule->is_hired_driver, 'hired_driver_name' => $rule->hired_driver_name, 'hired_driver_cost' => $rule->hired_driver_cost, 'freight_amount' => $rate->freight_amount, 'driver_wage' => $rate->driver_wage, 'status' => 'planned'];
                $this->applyWorkerHiredDriverRules($data);
                try {
                    $this->assertNoConflict('worker', $data, $existing?->id);
                    if ($existing) {
                        $existing->fill($data)->save();
                        $skipped[] = $date->toDateString();
                    } else {
                        $data += ['run_no' => $this->nextRunNo('worker'), 'schedule_rule_id' => $rule->id];
                        $created[] = $this->run(WorkerVehicle::create($data));
                    }
                } catch (ValidationException) {
                    $skipped[] = $date->toDateString();
                }
            }
        }

        return compact('created', 'skipped');
    }

    /** Sync only planned runs; completed/in-progress history is immutable. */
    public function syncPlannedRuns(WorkerVehicleScheduleRule $rule): array
    {
        $from = now()->startOfDay();
        $to = now()->addDays(90)->startOfDay();
        $rule->loadMissing('days');
        foreach ($rule->runs()->where('status', 'planned')->whereBetween('run_date', [$from->toDateString(), $to->toDateString()])->get() as $run) {
            $matches = $rule->is_active && $run->run_date->gte($rule->effective_from) && (! $rule->effective_to || $run->run_date->lte($rule->effective_to)) && $rule->days->contains(fn ($day) => $day->weekday === $run->run_date->dayOfWeekIso && $day->pickup_time === $run->pickup_time);
            if (! $matches) {
                $run->forceFill(['status' => 'cancelled'])->save();
            }
        }

        return $rule->is_active ? $this->generateRuns($rule, $from->toDateString(), $to->toDateString()) : ['created' => [], 'skipped' => []];
    }

    /** @return array<string, mixed> */
    private function run(Model $run): array
    {
        $run->loadMissing($run instanceof WorkerVehicle ? ['vehicle:id,license_plate,seat_count,vehicle_model', 'vehicleType:id,code,name', 'customer:id,name', 'route:id,name,shift_name', 'partner:id,name', 'driver:id,full_name'] : ['vehicle:id,license_plate,seat_count,vehicle_model', 'vehicleType:id,code,name', 'customer:id,name', 'partner:id,name', 'driver:id,full_name']);

        $attendance = $run instanceof WorkerVehicle
            ? DriverAttendance::query()->where('worker_vehicle_id', $run->id)->first()
            : DriverAttendance::query()->where('tourist_vehicle_id', $run->id)->first();

        return [...$run->only(['id', 'vehicle_id', 'vehicle_type_id', 'run_no', 'schedule_rule_id', 'customer_id', 'customer_name', 'route_id', 'shift_name', 'journey', 'pickup_location', 'dropoff_location', 'is_company_vehicle', 'partner_id', 'license_plate', 'driver_id', 'is_hired_driver', 'hired_driver_name', 'hired_driver_cost', 'partner_vehicle_cost', 'freight_amount', 'driver_wage_rate', 'driver_wage', 'driver_wage_is_overridden', 'driver_collected_amount', 'company_collected_amount', 'driver_remitted_amount', 'driver_expense_amount', 'status', 'completed_at', 'actual_started_at', 'actual_completed_at', 'note', 'is_active']), 'run_date' => $run->run_date?->toDateString(), 'return_date' => $run->return_date?->toDateString(), 'pickup_time' => $this->timeValue($run->pickup_time), 'return_time' => $this->timeValue($run->return_time), 'shift_name' => $run->shift_name ?: ($run instanceof WorkerVehicle ? $run->route?->shift_name : null), 'vehicle_type_code' => $run->vehicleType?->code, 'vehicle_type_name' => $run->vehicleType?->name, 'customer_name' => $run->customer_name ?? $run->customer?->name, 'route_name' => $run instanceof WorkerVehicle ? $run->route?->name : null, 'partner_name' => $run->partner?->name, 'driver_name' => $run->driver?->full_name, 'attendance' => $attendance?->only(['id', 'status', 'calculated_wage'])];
    }

    /** @return array<string, mixed> */
    private function rate(WorkerVehicleRate $rate): array
    {
        $rate->loadMissing(['route:id,name', 'vehicle:id,license_plate,seat_count,vehicle_model']);

        return [...$rate->only(['id', 'vehicle_id', 'route_id', 'license_plate', 'freight_amount', 'driver_wage', 'is_active']), 'effective_from' => $rate->effective_from?->toDateString(), 'effective_to' => $rate->effective_to?->toDateString(), 'vehicle_model' => $rate->vehicle?->vehicle_model, 'seat_count' => $rate->vehicle?->seat_count, 'route_name' => $rate->route?->name];
    }

    /** @return array<string, mixed> */
    private function rule(WorkerVehicleScheduleRule $rule): array
    {
        $rule->loadMissing('vehicle:id,license_plate,seat_count,vehicle_model');

        return [...$rule->only(['id', 'vehicle_id', 'customer_id', 'route_id', 'shift_name', 'pickup_location', 'dropoff_location', 'is_company_vehicle', 'partner_id', 'license_plate', 'driver_id', 'is_hired_driver', 'hired_driver_name', 'hired_driver_cost', 'note', 'is_active']), 'effective_from' => $rule->effective_from?->toDateString(), 'effective_to' => $rule->effective_to?->toDateString(), 'vehicle_model' => $rule->vehicle?->vehicle_model, 'seat_count' => $rule->vehicle?->seat_count, 'days' => $rule->days->map(fn ($day) => ['weekday' => $day->weekday, 'pickup_time' => $this->timeValue($day->pickup_time), 'return_time' => $this->timeValue($day->return_time)])->values(), 'runs_count' => $rule->runs->count(), 'customer_name' => $rule->customer?->name, 'route_name' => $rule->route?->name, 'partner_name' => $rule->partner?->name, 'driver_name' => $rule->driver?->full_name];
    }

    private function modelClass(string $kind): string
    {
        return $kind === 'worker' ? WorkerVehicle::class : TouristVehicle::class;
    }

    private function nextRunNo(string $kind): string
    {
        $prefix = $kind === 'worker' ? 'CN' : 'DL';

        return $prefix.now()->format('Ymd').str_pad((string) ($this->modelClass($kind)::query()->where('run_no', 'like', $prefix.now()->format('Ymd').'%')->count() + 1), 3, '0', STR_PAD_LEFT);
    }

    private function applicableRate(int $routeId, ?int $vehicleId, string $plate, string $date): ?WorkerVehicleRate
    {
        return WorkerVehicleRate::query()->where('is_active', true)->where('route_id', $routeId)->when($vehicleId, fn ($query) => $query->where('vehicle_id', $vehicleId), fn ($query) => $query->whereNull('vehicle_id')->where('license_plate', $plate))->whereDate('effective_from', '<=', $date)->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))->latest('effective_from')->first();
    }

    private function assertRatePeriod(array $data, ?int $except = null): void
    {
        $q = WorkerVehicleRate::query()->where('is_active', true)->where('route_id', $data['route_id'])->when($data['vehicle_id'] ?? null, fn ($query) => $query->where('vehicle_id', $data['vehicle_id']), fn ($query) => $query->whereNull('vehicle_id')->where('license_plate', $data['license_plate']))->whereDate('effective_from', '<=', $data['effective_to'] ?? '9999-12-31')->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $data['effective_from']));
        if ($except) {
            $q->whereKeyNot($except);
        } if ($q->exists()) {
            throw ValidationException::withMessages(['effective_from' => 'Khoảng hiệu lực giá bị chồng lấn cho cùng tuyến và biển số.']);
        }
    }

    private function assertRuleHasRate(array $data): void
    {
        if (! $data['is_active'] || $this->applicableRate($data['route_id'], $data['vehicle_id'] ?? null, $data['license_plate'], $data['effective_from'])) {
            return;
        }
        throw ValidationException::withMessages(['license_plate' => 'Quy tắc hoạt động cần có bảng giá hiệu lực tại ngày bắt đầu.']);
    }

    /** @param array<string, mixed> $data */
    private function assertRuleDoesNotConflictWithTouristRun(array $data): void
    {
        if (! $data['is_active']) {
            return;
        }
        $from = CarbonImmutable::parse($data['effective_from'])->startOfDay();
        $to = CarbonImmutable::parse($data['effective_to'] ?? $from->addDays(90))->startOfDay()->min($from->addDays(90));
        $touristRuns = TouristVehicle::query()->where('status', '!=', 'cancelled')->where(fn ($query) => ($data['vehicle_id'] ?? null) ? $query->where('vehicle_id', $data['vehicle_id'])->orWhere('driver_id', $data['driver_id']) : $query->where('license_plate', $data['license_plate'])->orWhere('driver_id', $data['driver_id']))->whereDate('run_date', '<=', $to->toDateString())->where(fn ($query) => $query->whereNull('return_date')->orWhereDate('return_date', '>=', $from->toDateString()))->get();
        foreach ($data['days'] as $day) {
            for ($date = $from; $date->lte($to); $date = $date->addDay()) {
                if ($date->dayOfWeekIso !== (int) $day['weekday']) {
                    continue;
                }
                $start = CarbonImmutable::parse($date->toDateString().' '.$day['pickup_time']);
                $end = CarbonImmutable::parse($date->toDateString().' '.($day['return_time'] ?? '23:59:59'));
                foreach ($touristRuns as $touristRun) {
                    $touristStart = CarbonImmutable::parse($touristRun->run_date->toDateString().' '.$touristRun->pickup_time);
                    $touristEnd = CarbonImmutable::parse(($touristRun->return_date?->toDateString() ?: $touristRun->run_date->toDateString()).' '.($touristRun->return_time ?: '23:59:59'));
                    if ($start->lt($touristEnd) && $end->gt($touristStart)) {
                        throw ValidationException::withMessages(['days' => 'Quy tắc lịch trùng biển số hoặc tài xế với xe chạy du lịch.']);
                    }
                }
            }
        }
    }

    private function assertNoConflict(string $kind, array $data, ?int $except = null): void
    {
        if (($data['status'] ?? 'planned') === 'cancelled') {
            return;
        } $start = CarbonImmutable::parse($data['run_date'].' '.$data['pickup_time']);
        $end = CarbonImmutable::parse(($data['return_date'] ?: $data['run_date']).' '.($data['return_time'] ?: '23:59:59'));
        foreach ([WorkerVehicle::class, TouristVehicle::class] as $class) {
            $q = $class::query()->where('status', '!=', 'cancelled')->whereDate('run_date', '<=', $end->toDateString())->where(fn ($q) => $q->whereNull('return_date')->orWhereDate('return_date', '>=', $start->toDateString()));
            if ($class === $this->modelClass($kind) && $except) {
                $q->whereKeyNot($except);
            } foreach ($q->get() as $other) {
                $otherStart = CarbonImmutable::parse($other->run_date->toDateString().' '.$other->pickup_time);
                $otherEnd = CarbonImmutable::parse(($other->return_date?->toDateString() ?: $other->run_date->toDateString()).' '.($other->return_time ?: '23:59:59'));
                if ($start->lt($otherEnd) && $end->gt($otherStart) && (((! empty($data['vehicle_id']) && (int) $data['vehicle_id'] === (int) $other->vehicle_id) || (empty($data['vehicle_id']) && $data['license_plate'] === $other->license_plate)) || (! $data['is_hired_driver'] && $data['driver_id'] && $data['driver_id'] === $other->driver_id))) {
                    throw ValidationException::withMessages(['run_date' => 'Biển số hoặc tài xế đã có chuyến trùng thời gian.']);
                }
            }
        }
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $input */
    private function syncTouristWage(array &$data, array $input, ?TouristVehicle $existing = null): void
    {
        $hasCompanyDriver = ! $data['is_hired_driver'] && ! empty($data['driver_id']);
        if (! $hasCompanyDriver) {
            $data['driver_wage_rate'] = 0;
            $data['driver_wage'] = 0;
            $data['driver_wage_is_overridden'] = false;

            return;
        }

        $type = VehicleType::query()->active()->find($data['vehicle_type_id'] ?? null);
        if (! $type) {
            throw ValidationException::withMessages(['vehicle_type_id' => 'Chuyến du lịch cần loại xe đang hoạt động để tính lương.']);
        }
        $vehicleOrTypeChanged = $existing && ((int) $existing->vehicle_id !== (int) ($data['vehicle_id'] ?? 0) || (int) $existing->vehicle_type_id !== (int) ($data['vehicle_type_id'] ?? 0));
        $isOverridden = ! $vehicleOrTypeChanged && filter_var($data['driver_wage_is_overridden'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $rate = $isOverridden ? (float) $data['driver_wage_rate'] : (float) $type->tourism_wage_rate;
        $wage = number_format((float) $data['freight_amount'] * $rate, 2, '.', '');

        if ($existing && in_array($existing->status, ['completed', 'cancelled'], true) && ((float) $existing->driver_wage_rate !== $rate || (float) $existing->driver_wage !== (float) $wage)) {
            abort(409, 'Không thể thay đổi tỷ lệ hoặc lương lái xe của chuyến đã hoàn thành hoặc đã hủy.');
        }

        $data['driver_wage_rate'] = number_format($rate, 4, '.', '');
        $data['driver_wage_is_overridden'] = $isOverridden;
        $data['driver_wage'] = $wage;
    }

    private function validatedRun(string $kind, array $input, bool $create): array
    {
        $input = $this->normalizeTimeInput($input, ['pickup_time', 'return_time']);
        foreach (['run_date', 'return_date'] as $field) {
            if (! empty($input[$field])) {
                $input[$field] = CarbonImmutable::parse($input[$field])->toDateString();
            }
        }
        if ($kind === 'worker' && filter_var($input['is_company_vehicle'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true && empty($input['vehicle_id'])) {
            throw ValidationException::withMessages(['vehicle_id' => 'Xe công ty phải được chọn từ danh mục xe.']);
        }
        if ($kind === 'worker' && ! empty($input['route_id'])) {
            $this->applyWorkerRouteSnapshot($input);
        }
        $rules = ['vehicle_id' => ['nullable', 'integer', Rule::exists('vehicles', 'id')], 'vehicle_type_id' => ['nullable', 'integer', Rule::exists('vehicle_types', 'id')], 'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')], 'run_date' => ['required', 'date'], 'return_date' => ['nullable', 'date', 'after_or_equal:run_date'], 'pickup_time' => ['required', 'date_format:H:i'], 'return_time' => ['nullable', 'date_format:H:i'], 'pickup_location' => ['required', 'string', 'max:500'], 'dropoff_location' => ['required', 'string', 'max:500'], 'is_company_vehicle' => ['nullable', 'boolean', 'required_without:vehicle_id'], 'partner_id' => ['nullable', 'integer', Rule::exists('partners', 'id')], 'license_plate' => ['nullable', 'string', 'max:50', 'required_without:vehicle_id'], 'driver_id' => ['nullable', 'integer', Rule::exists('drivers', 'id')], 'is_hired_driver' => ['required', 'boolean'], 'hired_driver_name' => ['nullable', 'string', 'max:255'], 'hired_driver_cost' => ['nullable', 'numeric', 'min:0'], 'partner_vehicle_cost' => ['nullable', 'numeric', 'min:0'], 'freight_amount' => ['nullable', 'numeric', 'min:0'], 'driver_wage' => ['nullable', 'numeric', 'min:0'], 'driver_wage_rate' => ['nullable', 'numeric', 'between:0,1'], 'driver_wage_is_overridden' => ['nullable', 'boolean'], 'status' => ['nullable', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])], 'completed_at' => ['nullable', 'date'], 'note' => ['nullable', 'string']];
        if ($kind === 'worker') {
            $rules['is_company_vehicle'] = ['required', 'boolean'];
            $rules += ['route_id' => ['required', 'integer', Rule::exists('routes', 'id')], 'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')], 'shift_name' => ['required', 'string', 'max:100'], 'schedule_rule_id' => ['nullable', 'integer', Rule::exists('worker_vehicle_schedule_rules', 'id')]];
        } else {
            $rules += ['customer_name' => ['required', 'string', 'max:255'], 'journey' => ['required', 'string', 'max:1000'], 'driver_collected_amount' => ['nullable', 'numeric', 'min:0'], 'company_collected_amount' => ['nullable', 'numeric', 'min:0'], 'driver_remitted_amount' => ['nullable', 'numeric', 'min:0'], 'driver_expense_amount' => ['nullable', 'numeric', 'min:0']];
        }
        $data = Validator::make($input, $rules)->validate();
        if ($kind === 'worker') {
            if ($data['is_company_vehicle'] && empty($data['vehicle_id'])) {
                throw ValidationException::withMessages(['vehicle_id' => 'Xe công ty phải được chọn từ danh mục xe.']);
            }
            if (! $data['is_company_vehicle'] && ! empty($data['vehicle_id'])) {
                throw ValidationException::withMessages(['vehicle_id' => 'Xe ngoài không được liên kết xe trong danh mục.']);
            }
        }
        $this->applyVehicleSnapshot($data, false);
        if ($kind === 'worker') {
            $this->applyWorkerHiredDriverRules($data);
        }
        if ($kind === 'worker' && ! $data['is_company_vehicle'] && ! empty($data['vehicle_id'])) {
            throw ValidationException::withMessages(['vehicle_id' => 'Xe công ty phải được chọn từ danh mục xe.']);
        }
        if (! $data['is_company_vehicle'] && empty($data['partner_id'])) {
            throw ValidationException::withMessages(['partner_id' => 'Xe đối tác phải có đối tác.']);
        }
        if ($data['is_hired_driver'] && (empty($data['hired_driver_name']) || (float) ($data['hired_driver_cost'] ?? 0) <= 0)) {
            throw ValidationException::withMessages(['hired_driver_name' => 'Thuê lái phải có tên và chi phí thuê.']);
        }

        if (! $data['is_company_vehicle'] && (float) ($data['partner_vehicle_cost'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['partner_vehicle_cost' => 'Xe đối tác phải có tiền thuê xe lớn hơn 0.']);
        }

        if (! $data['is_company_vehicle'] && empty($data['vehicle_type_id'])) {
            throw ValidationException::withMessages(['vehicle_type_id' => 'Xe đối tác phải chọn loại xe.']);
        }
        if ($kind === 'tourist') {
            if ((float) ($data['driver_collected_amount'] ?? 0) + (float) ($data['company_collected_amount'] ?? 0) > (float) ($data['freight_amount'] ?? 0)) {
                throw ValidationException::withMessages(['driver_collected_amount' => 'Tổng tiền lái xe thu và công ty thu không được vượt cước xe.']);
            }
            if ((float) ($data['driver_remitted_amount'] ?? 0) > (float) ($data['driver_collected_amount'] ?? 0)) {
                throw ValidationException::withMessages(['driver_remitted_amount' => 'Lái xe nộp không được vượt số tiền đã thu.']);
            }
        }

        // The database keeps a non-null numeric cost even when this trip does not hire a driver.
        if (! $data['is_hired_driver']) {
            $data['hired_driver_name'] = null;
            $data['hired_driver_cost'] = 0;
        }

        return $data + ['return_date' => null, 'return_time' => null, 'partner_id' => null, 'driver_id' => null, 'hired_driver_name' => null, 'status' => 'planned', 'hired_driver_cost' => 0, 'partner_vehicle_cost' => 0, 'freight_amount' => 0, 'driver_wage' => 0, 'driver_wage_rate' => 0, 'driver_wage_is_overridden' => false];
    }

    private function validatedRule(array $input): array
    {
        $input = $this->normalizeTimeInput($input, ['days.*.pickup_time', 'days.*.return_time']);
        $rules = ['vehicle_id' => ['nullable', 'integer', Rule::exists('vehicles', 'id')], 'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')], 'route_id' => ['required', 'integer', Rule::exists('routes', 'id')], 'shift_name' => ['required', 'string', 'max:100'], 'effective_from' => ['required', 'date'], 'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'], 'pickup_location' => ['required', 'string', 'max:500'], 'dropoff_location' => ['required', 'string', 'max:500'], 'is_company_vehicle' => ['nullable', 'boolean', 'required_without:vehicle_id'], 'partner_id' => ['nullable', 'integer', Rule::exists('partners', 'id')], 'license_plate' => ['nullable', 'string', 'max:50', 'required_without:vehicle_id'], 'driver_id' => ['nullable', 'integer', Rule::exists('drivers', 'id')], 'is_hired_driver' => ['required', 'boolean'], 'hired_driver_name' => ['nullable', 'string', 'max:255'], 'hired_driver_cost' => ['nullable', 'numeric', 'min:0'], 'note' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean'], 'days' => ['required', 'array', 'min:1'], 'days.*.weekday' => ['required', 'integer', 'between:1,7'], 'days.*.pickup_time' => ['required', 'date_format:H:i'], 'days.*.return_time' => ['nullable', 'date_format:H:i']];
        $data = Validator::make($input, $rules)->validate();
        $this->applyVehicleSnapshot($data, false);
        if ($data['is_hired_driver']) {
            $data['driver_id'] = null;
        }
        if (! $data['is_company_vehicle'] && empty($data['partner_id'])) {
            throw ValidationException::withMessages(['partner_id' => 'Xe đối tác phải có đối tác.']);
        } if ($data['is_hired_driver'] && (empty($data['hired_driver_name']) || (float) ($data['hired_driver_cost'] ?? 0) <= 0)) {
            throw ValidationException::withMessages(['hired_driver_name' => 'Thuê lái phải có tên và chi phí thuê.']);
        }

        // The database keeps a non-null numeric cost even when this schedule does not hire a driver.
        if (! $data['is_hired_driver']) {
            $data['hired_driver_name'] = null;
            $data['hired_driver_cost'] = 0;
        }

        return $data + ['is_active' => true, 'hired_driver_cost' => 0];
    }

    /** Convert database TIME values (HH:mm:ss) to the public/API form value (HH:mm). */
    private function timeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 5);
    }

    /** Accept legacy TIME values while keeping the API validation contract at HH:mm. */
    private function normalizeTimeInput(array $input, array $fields): array
    {
        foreach ($fields as $field) {
            if (str_starts_with($field, 'days.*.')) {
                $key = substr($field, 7);
                $days = $input['days'] ?? [];
                if ($days instanceof Collection) {
                    $days = $days->all();
                }
                foreach ($days as $index => $day) {
                    if (array_key_exists($key, $day)) {
                        $days[$index][$key] = $this->timeValue($day[$key]);
                    }
                }
                $input['days'] = $days;

                continue;
            }

            if (array_key_exists($field, $input)) {
                $input[$field] = $this->timeValue($input[$field]);
            }
        }

        return $input;
    }

    /** Derive immutable run snapshots from an active vehicle, never from client input. */
    private function applyVehicleSnapshot(array &$data, bool $required): void
    {
        $vehicleId = $data['vehicle_id'] ?? null;
        if (! $vehicleId) {
            if ($required) {
                throw ValidationException::withMessages(['vehicle_id' => 'Vui lòng chọn xe từ danh mục.']);
            }

            return;
        }

        $vehicle = Vehicle::query()->active()->find($vehicleId);
        if (! $vehicle) {
            throw ValidationException::withMessages(['vehicle_id' => 'Xe đã ngừng hoạt động hoặc không tồn tại.']);
        }

        $data['license_plate'] = $vehicle->license_plate;
        $data['is_company_vehicle'] = $vehicle->ownership_type === OwnershipTypeEnum::COMPANY;
        $data['partner_id'] = $vehicle->ownership_type === OwnershipTypeEnum::PARTNER ? $vehicle->partner_id : null;
        $data['vehicle_type_id'] = $vehicle->vehicle_type_id;
        if (empty($data['driver_id'])) {
            $data['driver_id'] = $vehicle->default_driver_id;
        }
        if (! $vehicle->vehicle_type_id) {
            throw ValidationException::withMessages(['vehicle_id' => 'Xe chưa được gán loại xe, cần cập nhật danh mục xe trước khi tạo chuyến.']);
        }
    }

    /** Override all client-provided worker-route values with the approved route snapshot. */
    private function applyWorkerRouteSnapshot(array &$data): void
    {
        $route = Route::query()->with('vehicleTypeRates')->active()->find($data['route_id']);
        if (! $route || ! $route->customer_id || ! $route->shift_name || ! $route->default_pickup_time || ! $route->default_return_time) {
            throw ValidationException::withMessages(['route_id' => 'Tuyến chưa đầy đủ khách hàng, ca hoặc thời gian mặc định.']);
        }
        $typeId = $data['vehicle_type_id'] ?? null;
        if (! empty($data['vehicle_id'])) {
            $typeId = Vehicle::query()->active()->whereKey($data['vehicle_id'])->value('vehicle_type_id');
        }
        $rate = $route->vehicleTypeRates->firstWhere('vehicle_type_id', $typeId);
        if (! $rate) {
            throw ValidationException::withMessages(['vehicle_type_id' => 'Tuyến chưa có bảng giá cho loại xe đã chọn.']);
        }
        $data['vehicle_type_id'] = $typeId;
        $data['customer_id'] = $route->customer_id;
        $data['shift_name'] = $route->shift_name;
        $data['pickup_location'] = $route->pickup_location;
        $data['dropoff_location'] = $route->dropoff_location;
        $data['pickup_time'] = substr((string) $route->default_pickup_time, 0, 5);
        $data['return_time'] = substr((string) $route->default_return_time, 0, 5);
        $data['freight_amount'] = $rate->freight_amount;
        $data['driver_wage'] = $rate->driver_wage;
    }

    /** A hired driver is an external cost, never an internal-driver wage. */
    private function applyWorkerHiredDriverRules(array &$data): void
    {
        if (! $data['is_hired_driver']) {
            return;
        }

        $data['driver_id'] = null;
        $data['driver_wage'] = 0;
    }
}
