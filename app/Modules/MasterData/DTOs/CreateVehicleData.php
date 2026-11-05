<?php

namespace App\Modules\MasterData\DTOs;


readonly class CreateVehicleData
{
    public function __construct(
        public string $license_plate,
        public int $vehicle_type_id,
        public int $default_driver_id,
        public ?string $note,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'license_plate' => $this->license_plate,
            'vehicle_type_id' => $this->vehicle_type_id,
            'default_driver_id' => $this->default_driver_id,
            'note' => $this->note,
        ];
    }
}
