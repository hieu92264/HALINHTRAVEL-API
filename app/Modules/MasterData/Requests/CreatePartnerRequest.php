<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\CreatePartnerData;
use App\Shared\Enums\PartnerTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(PartnerTypeEnum::class)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'cccd' => ['nullable', 'string', 'max:20'],
            'tax_code' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['sometimes', 'decimal:0,2'],
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
        ];
    }

    public function toDTO(): CreatePartnerData
    {
        $data = $this->validated();

        return new CreatePartnerData(
            type: array_key_exists('type', $data) ? PartnerTypeEnum::from($data['type']) : PartnerTypeEnum::OTHER,
            name: $data['name'],
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            cccd: $data['cccd'] ?? null,
            tax_code: $data['tax_code'] ?? null,
            address: $data['address'] ?? null,
            bank_name: $data['bank_name'] ?? null,
            bank_account: $data['bank_account'] ?? null,
            opening_balance: (string) ($data['opening_balance'] ?? '0'),
        );
    }
}
