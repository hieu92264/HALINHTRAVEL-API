<?php

namespace App\Modules\Other\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OperationalReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => ['sometimes', 'nullable', 'date'],
            'to_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['sometimes', 'nullable', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])],
            'customer_id' => ['sometimes', 'nullable', 'integer', 'exists:customers,id'],
            'route_id' => ['sometimes', 'nullable', 'integer', 'exists:routes,id'],
            'driver_id' => ['sometimes', 'nullable', 'integer', 'exists:drivers,id'],
            'license_plate' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }
}
