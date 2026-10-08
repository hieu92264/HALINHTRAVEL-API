<?php

namespace App\Modules\Contract\DTOs;

readonly class UpdateContractData
{
    /** @param array<string, mixed> $values @param list<ContractItemData>|null $items */
    public function __construct(public array $values, public ?array $items) {}
}
