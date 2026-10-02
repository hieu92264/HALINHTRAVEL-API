<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\Models\Customer;
use App\Shared\Enums\CustomerEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCustomerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(CustomerEnum::class)],
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'cccd' => ['sometimes', 'nullable', 'string', 'max:20'],
            'tax_code' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'contact_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'opening_balance' => ['sometimes', 'decimal:0,2'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('type')) {
                return;
            }

            $customer = $this->route('customer');

            if (! $customer instanceof Customer) {
                return;
            }

            $type = $this->input('type', $customer->type?->value);
            $cccd = $this->has('cccd') ? $this->input('cccd') : $customer->cccd;
            $taxCode = $this->has('tax_code') ? $this->input('tax_code') : $customer->tax_code;

            if ($type === CustomerEnum::INDIVIDUAL->value && blank($cccd)) {
                $validator->errors()->add('cccd', 'Trường CCCD là bắt buộc đối với khách hàng cá nhân.');
            }

            if ($type === CustomerEnum::COMPANY->value && blank($taxCode)) {
                $validator->errors()->add('tax_code', 'Trường mã số thuế là bắt buộc đối với khách hàng doanh nghiệp.');
            }
        });
    }
}
