<?php

namespace App\Modules\Finance\Requests;

use App\Shared\Enums\ExpenseScopeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'scope' => [$required, Rule::enum(ExpenseScopeEnum::class)],
            'vehicle_id' => ['sometimes', 'nullable', 'integer', 'exists:vehicles,id'],
            'dispatch_order_id' => ['sometimes', 'nullable', 'integer', 'exists:dispatch_orders,id'],
            'partner_id' => ['sometimes', 'nullable', 'integer', 'exists:partners,id'],
            'driver_id' => ['sometimes', 'nullable', 'integer', 'exists:drivers,id'],
            'expense_date' => [$required, 'date'],
            'amount' => [$required, 'decimal:0,2', 'min:0'],
            'payment_method' => ['sometimes', 'nullable', 'string', 'max:30'],
            'document_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
