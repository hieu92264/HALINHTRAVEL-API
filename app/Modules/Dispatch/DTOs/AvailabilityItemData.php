<?php

namespace App\Modules\Dispatch\DTOs;

readonly class AvailabilityItemData
{
    public function __construct(
        public int $vehicleTypeId,
        public int $quantity,
    ) {}
}
