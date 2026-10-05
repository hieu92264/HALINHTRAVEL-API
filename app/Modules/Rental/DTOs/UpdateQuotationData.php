<?php

namespace App\Modules\Rental\DTOs;

readonly class UpdateQuotationData
{
    /** @param array<string, mixed> $values @param list<QuotationItemData>|null $items */
    public function __construct(public array $values, public ?array $items) {}
}
