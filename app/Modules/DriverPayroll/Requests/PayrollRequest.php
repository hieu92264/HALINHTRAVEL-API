<?php

namespace App\Modules\DriverPayroll\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return ['month' => [$required, 'integer', 'between:1,12'], 'year' => [$required, 'integer', 'between:2000,2100'], 'from_date' => [$required, 'date'], 'to_date' => [$required, 'date', 'after_or_equal:from_date']];
    }
}
