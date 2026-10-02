<?php

namespace App\Modules\MasterData\DTOs;

readonly class UpdateRouteRateData
{
    /** @param list<string> $provided */
    public function __construct(
        public ?int $routeId,
        public ?int $vehicleTypeId,
        public ?string $customerPrice,
        public ?string $driverWage,
        public ?string $effectiveFrom,
        public ?string $effectiveTo,
        public ?bool $isActive,
        public array $provided = [],
    ) {}

    /** @return array<string, bool|int|string|null> */
    public function toArray(): array
    {
        $data = [
            'route_id' => $this->routeId,
            'vehicle_type_id' => $this->vehicleTypeId,
            'customer_price' => $this->customerPrice,
            'driver_wage' => $this->driverWage,
            'effective_from' => $this->effectiveFrom,
            'effective_to' => $this->effectiveTo,
            'is_active' => $this->isActive,
        ];

        return array_intersect_key($data, array_flip($this->provided));
    }
}
