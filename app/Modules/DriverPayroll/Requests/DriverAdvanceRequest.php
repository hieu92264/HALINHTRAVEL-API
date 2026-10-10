<?php

namespace App\Modules\DriverPayroll\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return ['driver_id' => [$required, 'integer', 'exists:drivers,id'], 'advance_date' => [$required, 'date'], 'amount' => [$required, 'decimal:0,2', 'min:0'], 'description' => ['sometimes', 'nullable', 'string', 'max:500']];
    }
}
