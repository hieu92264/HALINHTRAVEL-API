<?php

namespace App\Modules\Rental\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectQuotationResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:2000']];
    }
}
