<?php

namespace App\Modules\MasterData\Interfaces;

use App\Modules\MasterData\DTOs\CreateExpenseTypeData;
use App\Modules\MasterData\DTOs\UpdateExpenseTypeData;
use App\Modules\MasterData\Models\ExpenseType;

interface ExpenseTypeServiceInterface
{
    /** @return list<array<string, mixed>> */
    public function expenseTypes(): array;

    /** @return array<string, mixed> */
    public function expenseType(ExpenseType $expenseType): array;

    /** @return array<string, mixed> */
    public function create(CreateExpenseTypeData $data): array;

    /** @return array<string, mixed> */
    public function update(ExpenseType $expenseType, UpdateExpenseTypeData $data): array;

    public function deactivate(ExpenseType $expenseType): void;
}
