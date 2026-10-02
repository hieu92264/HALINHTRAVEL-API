<?php

namespace App\Modules\MasterData\Interfaces;

use App\Modules\MasterData\DTOs\CreateVehicleData;
use App\Modules\MasterData\DTOs\UpdateVehicleData;
use App\Modules\MasterData\Models\Vehicle;

interface VehicleServiceInterface
{
    /** @return list<array<string, mixed>> */
    public function vehicles(): array;

    /** @return array<string, mixed> */
    public function vehicle(Vehicle $vehicle): array;

    /** @return array<string, mixed> */
    public function create(CreateVehicleData $data): array;

    /** @return array<string, mixed> */
    public function update(Vehicle $vehicle, UpdateVehicleData $data): array;

    public function deactivate(Vehicle $vehicle): void;
}
