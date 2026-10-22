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
        return [
            'user_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'remember_me' => ['boolean', 'nullable'],
        ];
    }

    /** @return array<string, mixed> */
    public function credentials(): array
    {
        $data = $this->validated();
        $login = $data['user_name'];
        unset($data['user_name']);

        $data[filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'user_name'] = $login;

        return $data;
    }
}
