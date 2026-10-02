<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\CreateExpenseTypeData;
use App\Shared\Enums\ExpenseTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateExpenseTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('expense_types', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'scope' => ['required', Rule::enum(ExpenseTypeEnum::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'mã loại chi phí',
            'name' => 'tên loại chi phí',
            'scope' => 'phạm vi áp dụng',
        ];
    }

    public function toDTO(): CreateExpenseTypeData
    {
        $data = $this->validated();

        return new CreateExpenseTypeData(
            code: $data['code'],
            name: $data['name'],
            scope: ExpenseTypeEnum::from($data['scope']),
        );
    }
}
