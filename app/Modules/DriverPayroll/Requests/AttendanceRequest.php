<?php

namespace App\Modules\DriverPayroll\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['work_units' => ['sometimes', 'decimal:0,2', 'min:0.01'], 'rate' => ['sometimes', 'decimal:0,2', 'min:0']];
    }
}
