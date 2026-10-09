<?php

namespace App\Modules\Dispatch\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelTripScheduleRequest extends FormRequest
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
