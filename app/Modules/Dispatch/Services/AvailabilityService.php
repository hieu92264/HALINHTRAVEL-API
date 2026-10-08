<?php

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\DTOs\AvailabilityItemData;
use App\Modules\Dispatch\DTOs\CheckAvailabilityData;
use App\Modules\Dispatch\Interfaces\AvailabilityServiceInterface;
use App\Modules\Dispatch\Models\TripAssignment;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use App\Shared\Enums\OwnershipTypeEnum;
use App\Shared\Enums\TripScheduleStatusEnum;
use App\Shared\Enums\VehicleStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AvailabilityService implements AvailabilityServiceInterface
{
    public function check(CheckAvailabilityData $data): array
    {
        $startAt = CarbonImmutable::parse($data->startAt);
        $endAt = CarbonImmutable::parse($data->endAt);
        $vehicleTypeIds = array_map(
            static fn (AvailabilityItemData $item): int => $item->vehicleTypeId,
            $data->items,
        );

        $busyAssignments = $this->busyAssignments($startAt, $endAt);
        $busyVehicleIds = $busyAssignments->pluck('vehicle_id')->filter()->unique()->values();
        $busyDriverIds = $busyAssignments->pluck('driver_id')->filter()->unique()->values();

        $vehicles = $this->availableVehicles($data, $vehicleTypeIds, $busyVehicleIds);
        $drivers = $this->availableDrivers($data, $startAt, $endAt, $busyDriverIds);

        $vehicleCapacity = array_map(
            fn (AvailabilityItemData $item): array => $this->vehicleCapacityFor($item, $vehicles),
            $data->items,
        );
        $requiredDriverCount = array_sum(array_map(
            static fn (AvailabilityItemData $item): int => $item->quantity,
            $data->items,
        ));
        $driverCapacity = $this->driverCapacity($drivers, $requiredDriverCount);

        return [
            'checked_at' => now()->toISOString(),
            'start_at' => $startAt->toISOString(),
            'end_at' => $endAt->toISOString(),
            'filters' => [
                'ownership_type' => $data->ownershipType?->value,
                'partner_id' => $data->partnerId,
            ],
            'vehicle_capacities' => $vehicleCapacity,
            'driver_capacity' => $driverCapacity,
            'can_fulfill' => collect($vehicleCapacity)->every('is_sufficient')
                && $driverCapacity['is_sufficient'],
        ];
    }

    /** @return Collection<int, TripAssignment> */
    private function busyAssignments(CarbonImmutable $startAt, CarbonImmutable $endAt): Collection
    {
        return TripAssignment::query()
            ->select(['id', 'vehicle_id', 'driver_id'])
            ->where('is_current', true)
            ->whereHas('tripSchedule', function (Builder $query) use ($startAt, $endAt): void {
                $query
                    ->whereNotIn('status', [
                        TripScheduleStatusEnum::COMPLETED->value,
                        TripScheduleStatusEnum::CANCELLED->value,
                    ])
                    ->where('scheduled_start_at', '<', $endAt)
                    ->where('scheduled_end_at', '>', $startAt);
            })
            ->get();
    }

    /**
     * @param  list<int>  $vehicleTypeIds
     * @param  Collection<int, int>  $busyVehicleIds
     * @return Collection<int, Vehicle>
     */
    private function availableVehicles(
        CheckAvailabilityData $data,
        array $vehicleTypeIds,
        Collection $busyVehicleIds,
    ): Collection {
        return Vehicle::query()
            ->with(['vehicleType:id,name', 'partner:id,name'])
            ->where('is_active', true)
            ->whereIn('vehicle_type_id', $vehicleTypeIds)
            ->where('vehicle_status', VehicleStatusEnum::AVAILABLE->value)
            ->when($data->ownershipType, fn (Builder $query, OwnershipTypeEnum $type): Builder => $query->where('ownership_type', $type->value))
            ->when($data->partnerId, fn (Builder $query, int $partnerId): Builder => $query->where('partner_id', $partnerId))
            ->when($busyVehicleIds->isNotEmpty(), fn (Builder $query): Builder => $query->whereNotIn('id', $busyVehicleIds))
            ->orderBy('license_plate')
            ->get();
    }

    /**
     * @param  Collection<int, int>  $busyDriverIds
     * @return Collection<int, Driver>
     */
    private function availableDrivers(
        CheckAvailabilityData $data,
        CarbonImmutable $startAt,
        CarbonImmutable $endAt,
        Collection $busyDriverIds,
    ): Collection {
        return Driver::query()
            ->with('partner:id,name')
            ->where('is_active', true)
            ->whereDate('joined_at', '<=', $startAt->toDateString())
            ->where(function (Builder $query) use ($startAt): void {
                $query->whereNull('left_at')->orWhereDate('left_at', '>', $startAt->toDateString());
            })
            ->where(function (Builder $query) use ($endAt): void {
                $query->whereNull('license_expired_at')->orWhereDate('license_expired_at', '>=', $endAt->toDateString());
            })
            ->when($data->ownershipType, fn (Builder $query, OwnershipTypeEnum $type): Builder => $query->where('type', $type->value))
            ->when($data->partnerId, fn (Builder $query, int $partnerId): Builder => $query->where('partner_id', $partnerId))
            ->when($busyDriverIds->isNotEmpty(), fn (Builder $query): Builder => $query->whereNotIn('id', $busyDriverIds))
            ->orderBy('code')
            ->get();
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     * @return array<string, mixed>
     */
    private function vehicleCapacityFor(AvailabilityItemData $item, Collection $vehicles): array
    {
        $candidates = $vehicles
            ->where('vehicle_type_id', $item->vehicleTypeId)
            ->values();
        $companyCount = $candidates->where('ownership_type', OwnershipTypeEnum::COMPANY)->count();
        $partnerCount = $candidates->where('ownership_type', OwnershipTypeEnum::PARTNER)->count();

        return [
            'vehicle_type_id' => $item->vehicleTypeId,
            'vehicle_type_name' => $candidates->first()?->vehicleType?->name
                ?? VehicleType::query()->whereKey($item->vehicleTypeId)->value('name'),
            'required_quantity' => $item->quantity,
            'company_available_count' => $companyCount,
            'partner_available_count' => $partnerCount,
            'available_count' => $candidates->count(),
            'is_sufficient' => $candidates->count() >= $item->quantity,
            'candidates' => $candidates->map(fn (Vehicle $vehicle): array => [
                'id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'ownership_type' => $vehicle->ownership_type?->value,
                'partner_id' => $vehicle->partner_id,
                'partner_name' => $vehicle->partner?->name,
            ])->all(),
        ];
    }

    /**
     * @param  Collection<int, Driver>  $drivers
     * @return array<string, mixed>
     */
    private function driverCapacity(Collection $drivers, int $requiredDriverCount): array
    {
        $companyCount = $drivers->where('type', OwnershipTypeEnum::COMPANY)->count();
        $partnerCount = $drivers->where('type', OwnershipTypeEnum::PARTNER)->count();

        return [
            'required_quantity' => $requiredDriverCount,
            'company_available_count' => $companyCount,
            'partner_available_count' => $partnerCount,
            'available_count' => $drivers->count(),
            'is_sufficient' => $drivers->count() >= $requiredDriverCount,
            'candidates' => $drivers->map(fn (Driver $driver): array => [
                'id' => $driver->id,
                'code' => $driver->code,
                'full_name' => $driver->full_name,
                'phone' => $driver->phone,
                'ownership_type' => $driver->type?->value,
                'partner_id' => $driver->partner_id,
                'partner_name' => $driver->partner?->name,
                'license_expired_at' => $driver->license_expired_at?->toDateString(),
            ])->all(),
        ];
    }
}
