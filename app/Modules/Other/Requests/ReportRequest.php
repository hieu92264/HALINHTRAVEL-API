<?php

namespace App\Modules\Other\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'format' => ['sometimes', Rule::in(['json', 'xlsx', 'docx'])],
            'basis' => ['sometimes', Rule::in(['accrual', 'cash'])],
            'route_id' => ['sometimes', 'integer'],
            'customer_id' => ['sometimes', 'integer'],
            'vehicle_id' => ['sometimes', 'integer'],
            'driver_id' => ['sometimes', 'integer'],
            'payroll_id' => ['sometimes', 'integer', 'exists:payrolls,id'],
            'partner_id' => ['sometimes', 'integer'],
            'expense_type_id' => ['sometimes', 'integer'],
            'scope' => ['sometimes', Rule::in(['vehicle', 'trip', 'general'])],
            'status' => ['sometimes', 'string'],
            'payment_method' => ['sometimes', Rule::in(['cash', 'bank_transfer'])],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return $this->safe()->except(['format']);
    }
}
