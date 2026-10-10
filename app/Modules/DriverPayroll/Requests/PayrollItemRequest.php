<?php

namespace App\Modules\DriverPayroll\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['meal_allowance' => ['sometimes', 'decimal:0,2', 'min:0'], 'other_allowance' => ['sometimes', 'decimal:0,2', 'min:0'], 'deduction_amount' => ['sometimes', 'decimal:0,2', 'min:0'], 'note' => ['sometimes', 'nullable', 'string', 'max:2000']];
    }
}
