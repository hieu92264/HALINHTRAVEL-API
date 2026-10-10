<?php

namespace App\Modules\Dispatch\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReturnCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['review_note' => ['required', 'string', 'max:2000']];
    }
}
