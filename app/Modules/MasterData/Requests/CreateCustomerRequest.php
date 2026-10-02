<?php

namespace App\Modules\MasterData\Requests;

use App\Shared\Enums\CustomerEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(CustomerEnum::class)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'cccd' => ['nullable', 'string', 'max:20', 'required_if:type,individual'],
            'tax_code' => ['nullable', 'string', 'max:30', 'required_if:type,company'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['sometimes', 'decimal:0,2'],
        ];
    }
}
