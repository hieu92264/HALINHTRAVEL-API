<?php

namespace App\Modules\MasterData\DTOs;


readonly class UpdateVehicleData
{
    /** @param list<string> $provided */
    public function __construct(
        public ?string $license_plate,
        public ?int $vehicle_type_id,
        public ?int $default_driver_id,
        public ?string $note,
        public ?bool $is_active,
        public array $provided = [],
    ) {}

    /** @return array<string, int|string|bool|null> */
    public function toArray(): array
    {
        $data = [
            'license_plate' => $this->license_plate,
            'vehicle_type_id' => $this->vehicle_type_id,
            'default_driver_id' => $this->default_driver_id,
            'note' => $this->note,
            'is_active' => $this->is_active,
        ];

        return array_intersect_key($data, array_flip($this->provided));
    }
}
