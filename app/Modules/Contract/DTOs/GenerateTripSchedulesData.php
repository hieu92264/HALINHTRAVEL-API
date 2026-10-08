<?php

namespace App\Modules\Contract\DTOs;

readonly class GenerateTripSchedulesData
{
    public function __construct(
        public string $fromDate,
        public string $toDate,
    ) {}
}
