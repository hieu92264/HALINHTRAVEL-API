<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\UpdateExpenseTypeData;
use App\Modules\MasterData\Models\ExpenseType;
use App\Shared\Enums\ExpenseTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseTypeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $expenseType = $this->route('expenseType');

        return [
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('expense_types', 'code')->ignore($expenseType instanceof ExpenseType ? $expenseType : null),
            ],
            'name' => ['sometimes', 'string', 'max:150'],
            'scope' => ['sometimes', Rule::enum(ExpenseTypeEnum::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'mã loại chi phí',
            'name' => 'tên loại chi phí',
            'scope' => 'phạm vi áp dụng',
            'is_active' => 'trạng thái hoạt động',
        ];
    }

    public function toDTO(): UpdateExpenseTypeData
    {
        $data = $this->validated();

        return new UpdateExpenseTypeData(
            code: $data['code'] ?? null,
            name: $data['name'] ?? null,
            scope: array_key_exists('scope', $data) ? ExpenseTypeEnum::from($data['scope']) : null,
            is_active: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            provided: array_keys($data),
        );
    }
}
