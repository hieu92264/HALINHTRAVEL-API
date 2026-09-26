<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $loginInput = $this->input('user_name');

        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        $column = $isEmail ? 'email' : 'user_name';
        return [
            $column => [
                'required',
                'string',
                "exists:users,{$column}"
            ],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'remember_me' => ['boolean', 'nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_name.exists' => 'Tài khoản hoặc email này không tồn tại trong hệ thống.',
        ];
    }
}

