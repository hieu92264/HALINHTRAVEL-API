<?php

namespace App\Modules\Dispatch\DTOs;

readonly class CompletionReportData
{
    public function __construct(
        public string $actualStartAt,
        public string $actualEndAt,
        public int $startOdometer,
        public int $endOdometer,
        public ?string $actualDistanceKm,
        public ?string $waitingHours,
        public ?string $note,
    ) {}
}
