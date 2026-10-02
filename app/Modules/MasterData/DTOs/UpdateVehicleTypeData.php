<?php

namespace App\Modules\MasterData\DTOs;

readonly class UpdateVehicleTypeData
{
    /** @param list<string> $provided */
    public function __construct(
        public ?string $code,
        public ?string $name,
        public ?int $seats,
        public ?string $tour_driver_commission_rate,
        public ?bool $is_active,
        public array $provided = [],
    ) {}

    /** @return array<string, bool|int|string|null> */
    public function toArray(): array
    {
        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'seats' => $this->seats,
            'tour_driver_commission_rate' => $this->tour_driver_commission_rate,
            'is_active' => $this->is_active,
        ];

        return array_intersect_key($data, array_flip($this->provided));
    }
}
