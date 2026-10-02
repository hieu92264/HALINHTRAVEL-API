<?php

namespace App\Modules\MasterData\DTOs;

readonly class CreateVehicleTypeData
{
    public function __construct(
        public string $code,
        public string $name,
        public int $seats,
        public string $tour_driver_commission_rate,
    ) {}

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'seats' => $this->seats,
            'tour_driver_commission_rate' => $this->tour_driver_commission_rate,
        ];
    }
}
