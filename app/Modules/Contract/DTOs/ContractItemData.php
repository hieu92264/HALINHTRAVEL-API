<?php

namespace App\Modules\Contract\DTOs;

use App\Shared\Enums\RentalServiceTypeEnum;

readonly class ContractItemData
{
    public function __construct(
        public ?int $routeId,
        public int $vehicleTypeId,
        public RentalServiceTypeEnum $serviceType,
        public int $quantity,
        public string $unitPrice,
        public string $driverWage,
        public ?string $pickupLocation,
        public ?string $dropoffLocation,
        public ?string $note,
    ) {}
}
