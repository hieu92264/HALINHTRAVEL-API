<?php

namespace App\Modules\Dispatch\DTOs;

readonly class TripScheduleData
{
    /** @param array<string, mixed> $values */
    public function __construct(public array $values) {}
}
