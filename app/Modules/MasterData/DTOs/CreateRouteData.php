<?php

namespace App\Modules\MasterData\DTOs;

readonly class CreateRouteData
{
    public function __construct(
        public ?int $customerId,
        public string $name,
        public ?string $shiftName,
        public string $pickupLocation,
        public string $dropoffLocation,
        public ?string $defaultPickupTime,
        public ?string $defaultReturnTime,
        public ?string $estimatedDistanceKm,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'name' => $this->name,
            'shift_name' => $this->shiftName,
            'pickup_location' => $this->pickupLocation,
            'dropoff_location' => $this->dropoffLocation,
            'default_pickup_time' => $this->defaultPickupTime,
            'default_return_time' => $this->defaultReturnTime,
            'estimated_distance_km' => $this->estimatedDistanceKm,
        ];
    }
}
