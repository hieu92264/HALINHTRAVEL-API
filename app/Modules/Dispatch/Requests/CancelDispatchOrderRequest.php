<?php

namespace App\Modules\Dispatch\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelDispatchOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['note' => ['required', 'string', 'max:2000']];
    }
}
