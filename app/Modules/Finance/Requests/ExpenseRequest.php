<?php

namespace App\Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'expense_type_id' => [$required, 'integer', 'exists:expense_types,id'],
            'vehicle_id' => ['sometimes', 'nullable', 'integer', 'exists:vehicles,id'],
            'expense_date' => [$required, 'date'],
            'amount' => [$required, 'decimal:0,2', 'min:0'],
            'payment_method' => ['sometimes', 'nullable', 'string', 'max:30'],
            'recipient_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'paid_by' => ['sometimes', 'nullable', 'string', 'max:255'],
            'hired_driver_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
