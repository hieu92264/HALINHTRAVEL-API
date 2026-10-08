<?php

namespace App\Modules\Rental\DTOs;

readonly class QuotationItemData
{
    public function __construct(
        public ?int $routeId,
        public int $vehicleTypeId,
        public ?string $description,
        public int $quantity,
        public string $unitPrice,
    ) {}
}
