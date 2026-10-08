<?php

namespace App\Modules\Contract\DTOs;

readonly class ContractScheduleRuleData
{
    public function __construct(
        public int $contractItemId,
        public ?int $routeId,
        public string $effectiveFrom,
        public string $effectiveTo,
        public ?int $defaultVehicleId,
        public ?int $defaultDriverId,
        public ?string $note,
    ) {}
}
