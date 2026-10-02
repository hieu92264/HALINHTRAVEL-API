<?php

namespace App\Modules\MasterData\DTOs;

use App\Shared\Enums\OwnershipTypeEnum;
use App\Shared\Enums\VehicleStatusEnum;

readonly class CreateVehicleData
{
    public function __construct(
        public string $licensePlate,
        public int $vehicleTypeId,
        public OwnershipTypeEnum $ownershipType,
        public ?int $partnerId,
        public ?string $brand,
        public ?string $model,
        public ?int $manufactureYear,
        public ?int $currentOdometer,
        public VehicleStatusEnum $vehicleStatus,
        public ?string $notes,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'license_plate' => $this->licensePlate,
            'vehicle_type_id' => $this->vehicleTypeId,
            'ownership_type' => $this->ownershipType->value,
            'partner_id' => $this->partnerId,
            'brand' => $this->brand,
            'model' => $this->model,
            'manufacture_year' => $this->manufactureYear,
            'current_odometer' => $this->currentOdometer,
            'vehicle_status' => $this->vehicleStatus->value,
            'notes' => $this->notes,
        ];
    }
}
