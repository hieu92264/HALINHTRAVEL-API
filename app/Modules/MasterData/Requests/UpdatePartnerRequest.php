<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\UpdatePartnerData;
use App\Shared\Enums\PartnerTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartnerRequest extends FormRequest
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
            'type' => ['sometimes', Rule::enum(PartnerTypeEnum::class)],
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'cccd' => ['sometimes', 'nullable', 'string', 'max:20'],
            'tax_code' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bank_account' => ['sometimes', 'nullable', 'string', 'max:100'],
            'opening_balance' => ['sometimes', 'decimal:0,2'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'loại đối tác',
            'name' => 'tên đối tác',
            'phone' => 'số điện thoại',
            'email' => 'email',
            'cccd' => 'CCCD',
            'tax_code' => 'mã số thuế',
            'address' => 'địa chỉ',
            'bank_name' => 'tên ngân hàng',
            'bank_account' => 'số tài khoản',
            'opening_balance' => 'công nợ đầu kỳ',
            'is_active' => 'trạng thái hoạt động',
        ];
    }

    public function toDTO(): UpdatePartnerData
    {
        $data = $this->validated();

        return new UpdatePartnerData(
            type: array_key_exists('type', $data) ? PartnerTypeEnum::from($data['type']) : null,
            name: $data['name'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            cccd: $data['cccd'] ?? null,
            tax_code: $data['tax_code'] ?? null,
            address: $data['address'] ?? null,
            bank_name: $data['bank_name'] ?? null,
            bank_account: $data['bank_account'] ?? null,
            opening_balance: array_key_exists('opening_balance', $data) ? (string) $data['opening_balance'] : null,
            is_active: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            provided: array_keys($data),
        );
    }
}
