<?php

namespace App\Modules\Rental\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordQuotationResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accepted' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
