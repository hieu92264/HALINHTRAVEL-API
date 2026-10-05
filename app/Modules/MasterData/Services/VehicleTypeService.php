<?php

namespace App\Modules\MasterData\Services;

use App\Modules\MasterData\DTOs\CreateVehicleTypeData;
use App\Modules\MasterData\DTOs\UpdateVehicleTypeData;
use App\Modules\MasterData\Interfaces\VehicleTypeServiceInterface;
use App\Modules\MasterData\Models\VehicleType;

class VehicleTypeService implements VehicleTypeServiceInterface
{
    public function vehicleTypes(): array
    {
        return VehicleType::query()
            ->orderBy('id')
            ->get()
            ->map(fn (VehicleType $vehicleType): array => $this->vehicleType($vehicleType))
            ->all();
    }

    public function options(): array
    {
        return VehicleType::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (VehicleType $vehicleType): array => [
                'id' => $vehicleType->id,
                'name' => $vehicleType->name,
            ])
            ->all();
    }

    public function vehicleType(VehicleType $vehicleType): array
    {
        return [
            'id' => $vehicleType->id,
            'code' => $vehicleType->code,
            'name' => $vehicleType->name,
            'seats' => $vehicleType->seats,
            'tour_driver_commission_rate' => $vehicleType->tour_driver_commission_rate,
            'is_active' => $vehicleType->is_active,
            'user_name_created' => $vehicleType->user_name_created,
            'user_name_updated' => $vehicleType->user_name_updated,
            'created_at' => $vehicleType->created_at?->toISOString(),
            'updated_at' => $vehicleType->updated_at?->toISOString(),
        ];
    }

    public function create(CreateVehicleTypeData $data): array
    {
        $vehicleType = VehicleType::create($data->toArray());

        return $this->vehicleType($vehicleType);
    }

    public function update(VehicleType $vehicleType, UpdateVehicleTypeData $data): array
    {
        $vehicleType->fill($data->toArray())->save();

        return $this->vehicleType($vehicleType->fresh());
    }

    public function deactivate(VehicleType $vehicleType): void
    {
        $vehicleType->forceFill(['is_active' => false])->save();
    }
}
