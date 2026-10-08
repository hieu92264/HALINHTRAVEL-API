<?php

namespace App\Modules\Dispatch\DTOs;

readonly class CompletionConfirmationData
{
    public function __construct(
        public string $customerAmount,
        public string $partnerVehicleCost,
        public string $externalDriverCost,
        public ?string $note,
    ) {}
}
