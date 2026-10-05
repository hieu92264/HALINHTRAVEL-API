<?php

namespace App\Modules\Rental\Interfaces;

interface RentalRequestServiceInterface
{
    public function getList(): array;
    public function getDetailById(int $id): array;
    public function store(): array;
    public function update(int $id, array $data): array;
    public function delete(int $id): array;
    public function markQuoted(int $id): array; //đã báo giá
    public function acceptQuoted(int $id): array; // chấp nhận
    public function rejectQuoted(int $id): array; // từ chối
}
