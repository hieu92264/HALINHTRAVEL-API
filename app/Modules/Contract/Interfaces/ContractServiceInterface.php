<?php

namespace App\Modules\Contract\Interfaces;

use App\Modules\Contract\DTOs\CreateContractData;
use App\Modules\Contract\DTOs\CreateContractFromQuotationData;
use App\Modules\Contract\DTOs\UpdateContractData;
use App\Modules\Contract\Models\Contract;

interface ContractServiceInterface
{
    public function getList(): array;

    public function getDetail(Contract $contract): array;

    public function store(CreateContractData $data): array;

    public function fromQuotation(CreateContractFromQuotationData $data): array;

    public function update(Contract $contract, UpdateContractData $data): array;

    public function delete(Contract $contract): void;

    public function activate(Contract $contract): array;

    public function complete(Contract $contract): array;

    public function cancel(Contract $contract): array;
}
