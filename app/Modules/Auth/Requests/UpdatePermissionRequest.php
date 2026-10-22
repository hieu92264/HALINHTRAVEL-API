<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9-]*)+$/', Rule::unique('permissions', 'name')->ignore($this->route('permission'))->where('guard_name', 'api')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Mã quyền phải gồm các phần chữ thường, số hoặc dấu gạch ngang, cách nhau đúng một dấu chấm (ví dụ: customers.view).',
        ];
    }
}
