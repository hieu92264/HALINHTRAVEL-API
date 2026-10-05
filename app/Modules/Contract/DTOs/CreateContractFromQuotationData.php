<?php

namespace App\Modules\Contract\DTOs;

use App\Shared\Enums\ContractTypeEnum;

readonly class CreateContractFromQuotationData
{
    public function __construct(
        public int $quotationId,
        public ContractTypeEnum $contractType,
        public ?string $signedDate,
        public string $effectiveFrom,
        public ?string $effectiveTo,
        public string $depositRequired,
        public ?string $paymentTerms,
        public ?string $terms,
    ) {}
}
