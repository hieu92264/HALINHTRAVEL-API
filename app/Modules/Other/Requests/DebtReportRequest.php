<?php

namespace App\Modules\Other\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DebtReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['from_date' => ['sometimes', 'nullable', 'date'], 'to_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:from_date']];
    }
}
