<?php

namespace App\Modules\Rental\Interfaces;

use App\Modules\Rental\DTOs\CreateRentalRequestData;
use App\Modules\Rental\DTOs\UpdateRentalRequestData;
use App\Modules\Rental\Models\RentalRequest;

interface RentalRequestServiceInterface
{
    public function getList(): array;

    public function getDetail(RentalRequest $rentalRequest): array;

    public function store(CreateRentalRequestData $data): array;

    public function update(RentalRequest $rentalRequest, UpdateRentalRequestData $data): array;

    public function delete(int $id): array;

    public function markQuoted(int $id): array; // đã báo giá

    public function acceptQuoted(int $id): array; // chấp nhận

    public function rejectQuoted(int $id): array; // từ chối
}
