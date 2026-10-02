<?php

namespace App\Modules\MasterData\Services;

use App\Modules\MasterData\DTOs\CreateExpenseTypeData;
use App\Modules\MasterData\DTOs\UpdateExpenseTypeData;
use App\Modules\MasterData\Interfaces\ExpenseTypeServiceInterface;
use App\Modules\MasterData\Models\ExpenseType;

class ExpenseTypeService implements ExpenseTypeServiceInterface
{
    public function expenseTypes(): array
    {
        return ExpenseType::query()
            ->orderBy('id')
            ->get()
            ->map(fn (ExpenseType $expenseType): array => $this->expenseType($expenseType))
            ->all();
    }

    public function expenseType(ExpenseType $expenseType): array
    {
        return [
            'id' => $expenseType->id,
            'code' => $expenseType->code,
            'name' => $expenseType->name,
            'scope' => $expenseType->scope?->value,
            'is_active' => $expenseType->is_active,
            'user_name_created' => $expenseType->user_name_created,
            'user_name_updated' => $expenseType->user_name_updated,
            'created_at' => $expenseType->created_at?->toISOString(),
            'updated_at' => $expenseType->updated_at?->toISOString(),
        ];
    }

    public function create(CreateExpenseTypeData $data): array
    {
        $expenseType = ExpenseType::create($data->toArray());

        return $this->expenseType($expenseType);
    }

    public function update(ExpenseType $expenseType, UpdateExpenseTypeData $data): array
    {
        $expenseType->fill($data->toArray())->save();

        return $this->expenseType($expenseType->fresh());
    }

    public function deactivate(ExpenseType $expenseType): void
    {
        $expenseType->forceFill(['is_active' => false])->save();
    }
}
