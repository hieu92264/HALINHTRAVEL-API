<?php

namespace App\Modules\MasterData\DTOs;

readonly class UpdateRouteData
{
    /**
     * @param  list<string>  $provided
     */
    public function __construct(
        public ?int $customerId,
        public ?string $name,
        public ?string $shiftName,
        public ?string $pickupLocation,
        public ?string $dropoffLocation,
        public ?string $defaultPickupTime,
        public ?string $defaultReturnTime,
        public ?string $estimatedDistanceKm,
        public ?bool $isActive,
        public array $provided = [],
    ) {}

    /** @return array<string, int|string|bool|null> */
    public function toArray(): array
    {
        $data = [
            'customer_id' => $this->customerId,
            'name' => $this->name,
            'shift_name' => $this->shiftName,
            'pickup_location' => $this->pickupLocation,
            'dropoff_location' => $this->dropoffLocation,
            'default_pickup_time' => $this->defaultPickupTime,
            'default_return_time' => $this->defaultReturnTime,
            'estimated_distance_km' => $this->estimatedDistanceKm,
            'is_active' => $this->isActive,
        ];

        return array_intersect_key($data, array_flip($this->provided));
    }
}
