<?php

namespace App\Modules\MasterData\Interfaces;

use App\Modules\MasterData\DTOs\CreateVehicleTypeData;
use App\Modules\MasterData\DTOs\UpdateVehicleTypeData;
use App\Modules\MasterData\Models\VehicleType;

interface VehicleTypeServiceInterface
{
    /** @return list<array<string, mixed>> */
    public function vehicleTypes(): array;

    /** @return list<array{id: int, name: string}> */
    public function options(): array;

    /** @return array<string, mixed> */
    public function vehicleType(VehicleType $vehicleType): array;

    /** @return array<string, mixed> */
    public function create(CreateVehicleTypeData $data): array;

    /** @return array<string, mixed> */
    public function update(VehicleType $vehicleType, UpdateVehicleTypeData $data): array;

    public function deactivate(VehicleType $vehicleType): void;
}
