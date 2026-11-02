<?php

namespace App\Modules\MasterData\DTOs;

use App\Shared\Enums\OwnershipTypeEnum;

readonly class CreateVehicleData
{
    public function __construct(
        public string $license_plate,
        public int $seat_count,
        public ?string $vehicle_model,
        public OwnershipTypeEnum $ownership_type,
        public ?int $partner_id,
        public ?string $note,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'license_plate' => $this->license_plate,
            'seat_count' => $this->seat_count,
            'vehicle_model' => $this->vehicle_model,
            'ownership_type' => $this->ownership_type->value,
            'partner_id' => $this->partner_id,
            'note' => $this->note,
        ];
    }
}
