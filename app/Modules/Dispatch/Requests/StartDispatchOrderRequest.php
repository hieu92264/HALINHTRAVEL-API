<?php

namespace App\Modules\Dispatch\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartDispatchOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_start_at' => ['required', 'date'],
            'start_odometer' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string'],
        ];
    }
}
