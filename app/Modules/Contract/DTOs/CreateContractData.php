<?php

namespace App\Modules\Contract\DTOs;

use App\Shared\Enums\ContractTypeEnum;

readonly class CreateContractData
{
    /** @param list<ContractItemData> $items */
    public function __construct(
        public int $customerId,
        public ?int $rentalRequestId,
        public ?int $quotationId,
        public ContractTypeEnum $contractType,
        public ?string $signedDate,
        public string $effectiveFrom,
        public ?string $effectiveTo,
        public string $depositRequired,
        public ?string $paymentTerms,
        public ?string $terms,
        public array $items,
    ) {}
}
