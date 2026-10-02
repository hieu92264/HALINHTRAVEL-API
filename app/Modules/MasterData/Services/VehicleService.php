<?php

namespace App\Modules\MasterData\Services;

use App\Modules\MasterData\DTOs\CreateVehicleData;
use App\Modules\MasterData\DTOs\UpdateVehicleData;
use App\Modules\MasterData\Interfaces\VehicleServiceInterface;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\OwnershipTypeEnum;

class VehicleService implements VehicleServiceInterface
{
    public function vehicles(): array
    {
        return Vehicle::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Vehicle $vehicle): array => $this->vehicle($vehicle))
            ->all();
    }

    public function vehicle(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'license_plate' => $vehicle->license_plate,
            'vehicle_type_id' => $vehicle->vehicle_type_id,
            'ownership_type' => $vehicle->ownership_type?->value,
            'partner_id' => $vehicle->partner_id,
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'manufacture_year' => $vehicle->manufacture_year,
            'current_odometer' => $vehicle->current_odometer,
            'vehicle_status' => $vehicle->vehicle_status?->value,
            'notes' => $vehicle->notes,
            'is_active' => $vehicle->is_active,
            'user_name_created' => $vehicle->user_name_created,
            'user_name_updated' => $vehicle->user_name_updated,
            'created_at' => $vehicle->created_at?->toISOString(),
            'updated_at' => $vehicle->updated_at?->toISOString(),
        ];
    }

    public function create(CreateVehicleData $data): array
    {
        return $this->vehicle(Vehicle::create($data->toArray()));
    }

    public function update(Vehicle $vehicle, UpdateVehicleData $data): array
    {
        $changes = $data->toArray();
        $ownershipType = $data->ownershipType ?? $vehicle->ownership_type;

        if ($ownershipType === OwnershipTypeEnum::COMPANY) {
            $changes['partner_id'] = null;
        }

        if (array_key_exists('is_active', $changes)) {
            $vehicle->forceFill(['is_active' => $changes['is_active']]);
            unset($changes['is_active']);
        }

        $vehicle->fill($changes)->save();

        return $this->vehicle($vehicle->fresh());
    }

    public function deactivate(Vehicle $vehicle): void
    {
        $vehicle->forceFill(['is_active' => false])->save();
    }
}
