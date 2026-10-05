<?php

namespace App\Modules\Rental\DTOs;

use App\Shared\Enums\RentalServiceTypeEnum;

readonly class UpdateRentalRequestData
{
    public function __construct(
        public ?int $customer_id,
        public ?string $source,
        public ?string $requested_at,
        public ?RentalServiceTypeEnum $service_type,
        public ?string $pickup_location,
        public ?string $dropoff_location,
        public ?string $start_at,
        public ?string $end_at,
        public ?string $note,
        /** @var list<RentalRequestItemData>|null */
        public ?array $items,

        /** @var list<string> */
        public array $provided = [],
    ) {}

    /** @return array<string, int|string|array<array<string, int|string|null>>|null> */
    public function toArray(): array
    {
        $data = [
            'customer_id' => $this->customer_id,
            'source' => $this->source,
            'requested_at' => $this->requested_at,
            'service_type' => $this->service_type?->value,
            'pickup_location' => $this->pickup_location,
            'dropoff_location' => $this->dropoff_location,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'note' => $this->note,
            'items' => $this->items === null ? null : array_map(
                static fn (RentalRequestItemData $item): array => $item->toArray(),
                $this->items,
            ),
        ];

        return array_intersect_key(
            $data,
            array_flip($this->provided),
        );
    }
}
