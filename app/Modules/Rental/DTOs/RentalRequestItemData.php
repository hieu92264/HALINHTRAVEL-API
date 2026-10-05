<?php

namespace App\Modules\Rental\DTOs;

readonly class RentalRequestItemData
{
    public function __construct(
        public int $vehicleTypeId,
        public int $quantity,
        public ?int $routeId,
        public ?string $note,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'vehicle_type_id' => $this->vehicleTypeId,
            'quantity' => $this->quantity,
            'route_id' => $this->routeId,
            'note' => $this->note,
        ];
    }
}
