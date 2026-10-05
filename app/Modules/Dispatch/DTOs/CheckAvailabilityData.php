<?php

namespace App\Modules\Dispatch\DTOs;

use App\Shared\Enums\OwnershipTypeEnum;

readonly class CheckAvailabilityData
{
    /**
     * @param  list<AvailabilityItemData>  $items
     */
    public function __construct(
        public string $startAt,
        public string $endAt,
        public array $items,
        public ?OwnershipTypeEnum $ownershipType,
        public ?int $partnerId,
    ) {}
}
