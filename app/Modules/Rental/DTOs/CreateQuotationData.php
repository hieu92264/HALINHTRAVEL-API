<?php

namespace App\Modules\Rental\DTOs;

readonly class CreateQuotationData
{
    /** @param list<QuotationItemData> $items */
    public function __construct(
        public ?int $rentalRequestId,
        public int $customerId,
        public string $quotationDate,
        public ?string $validUntil,
        public string $discountAmount,
        public ?string $paymentTerms,
        public array $items,
    ) {}
}
