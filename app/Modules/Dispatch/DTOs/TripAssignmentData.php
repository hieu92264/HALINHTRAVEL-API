<?php

namespace App\Modules\Dispatch\DTOs;

readonly class TripAssignmentData
{
    public function __construct(
        public int $vehicleId,
        public int $driverId,
        public ?string $replaceReason = null,
    ) {}
}
