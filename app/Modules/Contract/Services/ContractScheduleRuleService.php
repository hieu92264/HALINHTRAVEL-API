<?php

namespace App\Modules\Contract\Services;

use App\Modules\Contract\DTOs\ContractScheduleDayData;
use App\Modules\Contract\DTOs\ContractScheduleRuleData;
use App\Modules\Contract\DTOs\GenerateTripSchedulesData;
use App\Modules\Contract\DTOs\UpdateContractScheduleRuleData;
use App\Modules\Contract\Interfaces\ContractScheduleRuleServiceInterface;
use App\Modules\Contract\Models\Contract;
use App\Modules\Contract\Models\ContractItem;
use App\Modules\Contract\Models\ContractScheduleDay;
use App\Modules\Contract\Models\ContractScheduleRule;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\ContractStatusEnum;
use App\Shared\Enums\TripScheduleStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContractScheduleRuleService implements ContractScheduleRuleServiceInterface
{
    public function getList(Contract $contract): array
    {
        return $contract->scheduleRules()
            ->withCount('tripSchedules')
            ->with($this->detailRelations())
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ContractScheduleRule $rule): array => $this->rule($rule))
            ->all();
    }

    public function getDetail(ContractScheduleRule $scheduleRule): array
    {
        return $this->rule($scheduleRule);
    }

    /** @throws \Throwable */
    public function store(Contract $contract, ContractScheduleRuleData $data): array
    {
        return DB::transaction(function () use ($contract, $data): array {
            $lockedContract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $this->assertActiveContract($lockedContract);
            $this->assertRuleDatesWithinContract($lockedContract, $data->effectiveFrom, $data->effectiveTo);
            $item = $this->validateRuleRelations(
                $lockedContract,
                $data->contractItemId,
                $data->routeId,
                $data->defaultVehicleId,
                $data->defaultDriverId,
            );

            $rule = ContractScheduleRule::create([
                'contract_item_id' => $item->id,
                'route_id' => $data->routeId,
                'effective_from' => $data->effectiveFrom,
                'effective_to' => $data->effectiveTo,
                'default_vehicle_id' => $data->defaultVehicleId,
                'default_driver_id' => $data->defaultDriverId,
                'note' => $data->note,
            ]);

            return $this->rule($rule);
        });
    }

    /** @throws \Throwable */
    public function update(ContractScheduleRule $scheduleRule, UpdateContractScheduleRuleData $data): array
    {
        return DB::transaction(function () use ($scheduleRule, $data): array {
            $rule = $this->lockedRule($scheduleRule);
            $this->assertEditable($rule);
            $values = $data->values;
            $itemId = $values['contract_item_id'] ?? $rule->contract_item_id;
            $routeId = array_key_exists('route_id', $values) ? $values['route_id'] : $rule->route_id;
            $vehicleId = array_key_exists('default_vehicle_id', $values) ? $values['default_vehicle_id'] : $rule->default_vehicle_id;
            $driverId = array_key_exists('default_driver_id', $values) ? $values['default_driver_id'] : $rule->default_driver_id;
            $from = $values['effective_from'] ?? $rule->effective_from->toDateString();
            $to = $values['effective_to'] ?? $rule->effective_to->toDateString();
            $contract = $this->contractForItem($rule->contract_item_id);

            $this->assertActiveContract($contract);
            $this->assertRuleDatesWithinContract($contract, (string) $from, (string) $to);
            $this->validateRuleRelations($contract, (int) $itemId, $routeId === null ? null : (int) $routeId, $vehicleId === null ? null : (int) $vehicleId, $driverId === null ? null : (int) $driverId);

            $rule->fill([
                ...$values,
                'contract_item_id' => $itemId,
                'route_id' => $routeId,
                'default_vehicle_id' => $vehicleId,
                'default_driver_id' => $driverId,
                'effective_from' => $from,
                'effective_to' => $to,
            ])->save();

            return $this->rule($rule->fresh());
        });
    }

    /** @throws \Throwable */
    public function delete(ContractScheduleRule $scheduleRule): void
    {
        DB::transaction(function () use ($scheduleRule): void {
            $rule = $this->lockedRule($scheduleRule);
            $this->assertEditable($rule);
            $this->assertActiveContract($this->contractForItem($rule->contract_item_id));
            $rule->forceFill(['is_active' => false])->save();
        });
    }

    /** @param list<ContractScheduleDayData> $days @throws \Throwable */
    public function replaceDays(ContractScheduleRule $scheduleRule, array $days): array
    {
        return DB::transaction(function () use ($scheduleRule, $days): array {
            $rule = $this->lockedRule($scheduleRule);
            $this->assertEditable($rule);
            $this->assertActiveContract($this->contractForItem($rule->contract_item_id));
            $rule->scheduleDays()->delete();
            $rule->scheduleDays()->createMany(array_map(
                static fn (ContractScheduleDayData $day): array => [
                    'weekday' => $day->weekday->value,
                    'pickup_time' => $day->pickupTime,
                    'return_time' => $day->returnTime,
                    'shift_name' => $day->shiftName,
                ],
                $days,
            ));

            return $this->rule($rule->fresh());
        });
    }

    /** @throws \Throwable */
    public function generateTripSchedules(ContractScheduleRule $scheduleRule, GenerateTripSchedulesData $data): array
    {
        return DB::transaction(function () use ($scheduleRule, $data): array {
            $rule = ContractScheduleRule::query()
                ->with(['scheduleDays', 'contractItem.contract', 'contractItem.route', 'route'])
                ->lockForUpdate()
                ->findOrFail($scheduleRule->id);
            if (! $rule->is_active) {
                abort(409, 'Quy tắc lịch đã ngừng hoạt động.');
            }
            $item = ContractItem::query()->lockForUpdate()->findOrFail($rule->contract_item_id);
            $contract = Contract::query()->lockForUpdate()->findOrFail($item->contract_id);
            $this->assertActiveContract($contract);
            $this->assertGenerationRange($contract, $rule, $data);

            if ($rule->scheduleDays->isEmpty()) {
                abort(422, 'Quy tắc lịch chưa có ngày chạy.');
            }

            /** @var Collection<string, Collection<int, ContractScheduleDay>> $daysByWeekday */
            $daysByWeekday = $rule->scheduleDays->groupBy(fn ($day): string => $day->weekday->value);
            $created = [];
            $skipped = [];
            $conflicts = [];
            $date = CarbonImmutable::parse($data->fromDate)->startOfDay();
            $toDate = CarbonImmutable::parse($data->toDate)->startOfDay();

            while ($date->lte($toDate)) {
                foreach ($daysByWeekday->get($date->format('D'), collect()) as $day) {
                    if ($day->return_time === null) {
                        abort(422, 'Không thể sinh lịch khi ngày chạy thiếu giờ về.');
                    }

                    $startAt = CarbonImmutable::parse($date->toDateString().' '.$day->pickup_time->format('H:i:s'));
                    $endAt = CarbonImmutable::parse($date->toDateString().' '.$day->return_time->format('H:i:s'));
                    $existing = TripSchedule::query()
                        ->where('contract_item_id', $item->id)
                        ->where('scheduled_start_at', $startAt->toDateTimeString())
                        ->lockForUpdate()
                        ->get();

                    $conflictingSchedules = $existing
                        ->filter(fn (TripSchedule $schedule): bool => $schedule->schedule_rule_id !== $rule->id);
                    if ($conflictingSchedules->isNotEmpty()) {
                        foreach ($conflictingSchedules as $schedule) {
                            $conflicts[] = $this->scheduleSummary($schedule);
                        }

                        continue;
                    }

                    $sameRuleSchedules = $existing
                        ->filter(fn (TripSchedule $schedule): bool => $schedule->schedule_rule_id === $rule->id)
                        ->values();
                    $requiredQuantity = max(1, (int) $item->quantity);
                    if ($sameRuleSchedules->count() >= $requiredQuantity) {
                        foreach ($sameRuleSchedules as $schedule) {
                            $skipped[] = $this->scheduleSummary($schedule);
                        }

                        continue;
                    }

                    $route = $rule->route_id !== null ? $rule->route : $item->route;
                    for ($index = $sameRuleSchedules->count(); $index < $requiredQuantity; $index++) {
                        $schedule = TripSchedule::create([
                            'schedule_no' => $this->nextScheduleNo($startAt),
                            'contract_id' => $contract->id,
                            'contract_item_id' => $item->id,
                            'schedule_rule_id' => $rule->id,
                            'service_type' => $item->service_type->value,
                            'route_id' => $route?->id,
                            'scheduled_start_at' => $startAt,
                            'scheduled_end_at' => $endAt,
                            'pickup_location' => $route?->pickup_location ?? $item->pickup_location,
                            'dropoff_location' => $route?->dropoff_location ?? $item->dropoff_location,
                            'journey' => null,
                            'required_vehicle_type_id' => $item->vehicle_type_id,
                            'status' => TripScheduleStatusEnum::PLANNED,
                            'note' => $day->shift_name ?? $rule->note,
                        ]);
                        $created[] = $this->scheduleSummary($schedule);
                    }
                }
                $date = $date->addDay();
            }

            return [
                'schedule_rule_id' => $rule->id,
                'created' => $created,
                'skipped' => $skipped,
                'conflicts' => $conflicts,
                'summary' => [
                    'created_count' => count($created),
                    'skipped_count' => count($skipped),
                    'conflicts_count' => count($conflicts),
                ],
            ];
        });
    }

    private function lockedRule(ContractScheduleRule $scheduleRule): ContractScheduleRule
    {
        return ContractScheduleRule::query()->lockForUpdate()->findOrFail($scheduleRule->id);
    }

    private function assertEditable(ContractScheduleRule $rule): void
    {
        if (! $rule->is_active) {
            abort(409, 'Quy tắc lịch đã ngừng hoạt động.');
        }
        if ($rule->tripSchedules()->exists()) {
            abort(409, 'Không thể thay đổi quy tắc đã sinh lịch chuyến.');
        }
    }

    private function assertActiveContract(Contract $contract): void
    {
        if ($contract->status !== ContractStatusEnum::ACTIVE || ! $contract->is_active) {
            abort(409, 'Chỉ hợp đồng đang active mới được quản lý quy tắc lịch.');
        }
    }

    private function assertRuleDatesWithinContract(Contract $contract, string $from, string $to): void
    {
        $fromDate = CarbonImmutable::parse($from)->startOfDay();
        $toDate = CarbonImmutable::parse($to)->startOfDay();
        if ($toDate->lt($fromDate)
            || $fromDate->lt(CarbonImmutable::parse($contract->effective_from)->startOfDay())
            || ($contract->effective_to !== null && $toDate->gt(CarbonImmutable::parse($contract->effective_to)->startOfDay()))) {
            abort(422, 'Hiệu lực quy tắc phải nằm trong hiệu lực hợp đồng.');
        }
    }

    private function assertGenerationRange(Contract $contract, ContractScheduleRule $rule, GenerateTripSchedulesData $data): void
    {
        $from = CarbonImmutable::parse($data->fromDate)->startOfDay();
        $to = CarbonImmutable::parse($data->toDate)->startOfDay();
        if ($from->lt(CarbonImmutable::parse($rule->effective_from)->startOfDay())
            || $to->gt(CarbonImmutable::parse($rule->effective_to)->startOfDay())
            || $from->lt(CarbonImmutable::parse($contract->effective_from)->startOfDay())
            || ($contract->effective_to !== null && $to->gt(CarbonImmutable::parse($contract->effective_to)->startOfDay()))) {
            abort(422, 'Khoảng ngày sinh lịch phải nằm trong hiệu lực quy tắc và hợp đồng.');
        }
    }

    private function contractForItem(int $itemId): Contract
    {
        $item = ContractItem::query()->with('contract')->findOrFail($itemId);

        return $item->contract;
    }

    private function validateRuleRelations(
        Contract $contract,
        int $itemId,
        ?int $routeId,
        ?int $vehicleId,
        ?int $driverId,
    ): ContractItem {
        $item = ContractItem::query()->whereKey($itemId)->where('contract_id', $contract->id)->first();
        if ($item === null) {
            abort(422, 'Hạng mục hợp đồng không thuộc hợp đồng đã chọn.');
        }

        if ($routeId !== null) {
            $route = Route::query()->whereKey($routeId)->where('is_active', true)->firstOrFail();
            if ($route->customer_id !== null && $route->customer_id !== $contract->customer_id) {
                abort(422, 'Tuyến không thuộc khách hàng của hợp đồng.');
            }
        }

        if ($vehicleId !== null) {
            $vehicle = Vehicle::query()->whereKey($vehicleId)->where('is_active', true)->firstOrFail();
            if ($vehicle->vehicle_type_id !== $item->vehicle_type_id) {
                abort(422, 'Xe mặc định không đúng loại xe của hạng mục hợp đồng.');
            }
        }

        if ($driverId !== null && ! Driver::query()->whereKey($driverId)->where('is_active', true)->exists()) {
            abort(422, 'Tài xế mặc định không hợp lệ hoặc đã ngừng hoạt động.');
        }

        return $item;
    }

    private function nextScheduleNo(CarbonImmutable $startAt): string
    {
        $prefix = 'LT'.$startAt->format('Ymd');
        $highest = TripSchedule::query()
            ->where('schedule_no', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('schedule_no')
            ->reduce(
                static fn (int $carry, string $number): int => preg_match('/^'.preg_quote($prefix, '/').'(\d{4,})$/', $number, $matches) === 1
                    ? max($carry, (int) $matches[1])
                    : $carry,
                0,
            );

        return $prefix.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @return array<string, int|string|null> */
    private function scheduleSummary(TripSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'schedule_no' => $schedule->schedule_no,
            'schedule_rule_id' => $schedule->schedule_rule_id,
            'scheduled_start_at' => $schedule->scheduled_start_at?->toISOString(),
            'scheduled_end_at' => $schedule->scheduled_end_at?->toISOString(),
            'status' => $schedule->status?->value,
        ];
    }

    /** @return list<string> */
    private function detailRelations(): array
    {
        return [
            'contractItem.contract:id,contract_no,customer_id,status',
            'contractItem.vehicleType:id,name',
            'route:id,customer_id,name,pickup_location,dropoff_location',
            'defaultVehicle:id,license_plate,vehicle_type_id',
            'defaultDriver:id,code,full_name',
            'scheduleDays',
        ];
    }

    /** @return array<string, mixed> */
    private function rule(ContractScheduleRule $rule): array
    {
        $rule->loadMissing($this->detailRelations());
        $tripSchedulesCount = $rule->trip_schedules_count ?? $rule->tripSchedules()->count();

        return [
            'id' => $rule->id,
            'contract_id' => $rule->contractItem?->contract_id,
            'contract_no' => $rule->contractItem?->contract?->contract_no,
            'contract_item_id' => $rule->contract_item_id,
            'vehicle_type_id' => $rule->contractItem?->vehicle_type_id,
            'vehicle_type_name' => $rule->contractItem?->vehicleType?->name,
            'route_id' => $rule->route_id,
            'route_name' => $rule->route?->name,
            'effective_from' => $rule->effective_from?->toDateString(),
            'effective_to' => $rule->effective_to?->toDateString(),
            'default_vehicle_id' => $rule->default_vehicle_id,
            'default_vehicle_license_plate' => $rule->defaultVehicle?->license_plate,
            'default_driver_id' => $rule->default_driver_id,
            'default_driver_code' => $rule->defaultDriver?->code,
            'default_driver_name' => $rule->defaultDriver?->full_name,
            'note' => $rule->note,
            'is_active' => $rule->is_active,
            'trip_schedules_count' => $tripSchedulesCount,
            'is_locked' => $tripSchedulesCount > 0,
            'days' => $rule->scheduleDays
                ->sortBy(fn ($day): string => $day->weekday->value.'|'.$day->pickup_time->format('H:i:s'))
                ->values()
                ->map(fn ($day): array => [
                    'id' => $day->id,
                    'weekday' => $day->weekday?->value,
                    'pickup_time' => $day->pickup_time?->format('H:i:s'),
                    'return_time' => $day->return_time?->format('H:i:s'),
                    'shift_name' => $day->shift_name,
                ])->all(),
        ];
    }
}
