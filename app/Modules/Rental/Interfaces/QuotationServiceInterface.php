<?php

namespace App\Modules\Rental\Interfaces;

use App\Modules\Rental\DTOs\CreateQuotationData;
use App\Modules\Rental\DTOs\UpdateQuotationData;
use App\Modules\Rental\Models\Quotation;

interface QuotationServiceInterface
{
    public function getList(): array;

    public function getDetail(Quotation $quotation): array;

    public function store(CreateQuotationData $data): array;

    public function update(Quotation $quotation, UpdateQuotationData $data): array;

    public function delete(Quotation $quotation): void;

    public function send(Quotation $quotation): array;

    public function expire(Quotation $quotation): array;

    public function responseSummary(string $token): array;

    public function acceptResponse(string $token): array;

    public function rejectResponse(string $token, ?string $note): array;
}
