<?php

namespace App\Modules\Dispatch\Interfaces;

use App\Modules\Dispatch\DTOs\CheckAvailabilityData;

interface AvailabilityServiceInterface
{
    /** @return array<string, mixed> */
    public function check(CheckAvailabilityData $data): array;
}
