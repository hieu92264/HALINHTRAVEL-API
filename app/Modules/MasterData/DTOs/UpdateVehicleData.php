<?php

namespace App\Modules\MasterData\DTOs;

use App\Shared\Enums\OwnershipTypeEnum;

readonly class UpdateVehicleData
{
    /** @param list<string> $provided */
    public function __construct(
        public ?string $license_plate,
        public ?int $seat_count,
        public ?string $vehicle_model,
        public ?OwnershipTypeEnum $ownership_type,
        public ?int $partner_id,
        public ?string $note,
        public ?bool $is_active,
        public array $provided = [],
    ) {}

    /** @return array<string, int|string|bool|null> */
    public function toArray(): array
    {
        $data = [
            'license_plate' => $this->license_plate,
            'seat_count' => $this->seat_count,
            'vehicle_model' => $this->vehicle_model,
            'ownership_type' => $this->ownership_type?->value,
            'partner_id' => $this->partner_id,
            'note' => $this->note,
            'is_active' => $this->is_active,
        ];

        return array_intersect_key($data, array_flip($this->provided));
    }
}
