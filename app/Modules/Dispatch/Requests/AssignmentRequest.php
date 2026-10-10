<?php

namespace App\Modules\Dispatch\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')],
            'driver_id' => ['required', 'integer', Rule::exists('drivers', 'id')],
            'partner_id' => ['nullable', 'integer', Rule::exists('partners', 'id')],
            'replace_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
