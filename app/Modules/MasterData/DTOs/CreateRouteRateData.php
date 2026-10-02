<?php

namespace App\Modules\MasterData\DTOs;

readonly class CreateRouteRateData
{
    public function __construct(
        public int $routeId,
        public int $vehicleTypeId,
        public string $customerPrice,
        public string $driverWage,
        public string $effectiveFrom,
        public ?string $effectiveTo,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'route_id' => $this->routeId,
            'vehicle_type_id' => $this->vehicleTypeId,
            'customer_price' => $this->customerPrice,
            'driver_wage' => $this->driverWage,
            'effective_from' => $this->effectiveFrom,
            'effective_to' => $this->effectiveTo,
        ];
    }
}
