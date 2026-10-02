<?php

namespace App\Modules\MasterData\Interfaces;

use App\Modules\MasterData\DTOs\CreateCustomerData;
use App\Modules\MasterData\DTOs\UpdateCustomerData;
use App\Modules\MasterData\Models\Customer;

interface CustomerServiceInterface
{
    /** @return list<array<string, mixed>> */
    public function customers(): array;

    /** @return array<string, mixed> */
    public function customer(Customer $customer): array;

    /** @return array<string, mixed> */
    public function create(CreateCustomerData $data): array;

    /** @return array<string, mixed> */
    public function update(Customer $customer, UpdateCustomerData $data): array;

    public function deactivate(Customer $customer): void;
}
