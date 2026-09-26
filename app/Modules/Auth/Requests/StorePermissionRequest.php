<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9-]*)+$/', Rule::unique('permissions', 'name')->where('guard_name', 'api')],
        ];
    }
}
