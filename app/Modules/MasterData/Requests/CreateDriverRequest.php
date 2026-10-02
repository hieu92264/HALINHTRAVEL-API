<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\CreateDriverData;
use App\Shared\Enums\OwnershipTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDriverRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('type') === OwnershipTypeEnum::COMPANY->value) {
            $this->merge(['partner_id' => null]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_name' => ['nullable', 'string', 'max:100', Rule::exists('users', 'user_name'), Rule::unique('drivers', 'user_name')],
            'partner_id' => ['nullable', 'integer', Rule::exists('partners', 'id'), 'required_if:type,partner'],
            'type' => ['required', Rule::enum(OwnershipTypeEnum::class)],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'cccd' => ['nullable', 'string', 'max:20', Rule::unique('drivers', 'cccd')],
            'license_number' => ['required', 'string', 'max:50', Rule::unique('drivers', 'license_number')],
            'license_class' => ['required', 'string', 'max:20'],
            'license_issued_at' => ['nullable', 'date'],
            'license_expired_at' => ['nullable', 'date', 'after_or_equal:license_issued_at'],
            'base_salary' => ['sometimes', 'decimal:0,2', 'min:0'],
            'responsibility_allowance' => ['sometimes', 'decimal:0,2', 'min:0'],
            'joined_at' => ['nullable', 'date'],
            'left_at' => ['nullable', 'date', 'after_or_equal:joined_at'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_name' => 'tài khoản người dùng',
            'partner_id' => 'đối tác',
            'type' => 'loại tài xế',
            'full_name' => 'họ và tên',
            'phone' => 'số điện thoại',
            'cccd' => 'CCCD',
            'license_number' => 'số bằng lái',
            'license_class' => 'hạng bằng lái',
            'license_issued_at' => 'ngày cấp bằng lái',
            'license_expired_at' => 'ngày hết hạn bằng lái',
            'base_salary' => 'lương cơ bản',
            'responsibility_allowance' => 'phụ cấp trách nhiệm',
            'joined_at' => 'ngày vào làm',
            'left_at' => 'ngày nghỉ việc',
        ];
    }

    public function toDTO(): CreateDriverData
    {
        $data = $this->validated();

        return new CreateDriverData(
            userName: $data['user_name'] ?? null,
            partnerId: $data['partner_id'] ?? null,
            type: OwnershipTypeEnum::from($data['type']),
            fullName: $data['full_name'],
            phone: $data['phone'] ?? null,
            cccd: $data['cccd'] ?? null,
            licenseNumber: $data['license_number'],
            licenseClass: $data['license_class'],
            licenseIssuedAt: $data['license_issued_at'] ?? null,
            licenseExpiredAt: $data['license_expired_at'] ?? null,
            baseSalary: (string) ($data['base_salary'] ?? '0'),
            responsibilityAllowance: (string) ($data['responsibility_allowance'] ?? '0'),
            joinedAt: $data['joined_at'] ?? null,
            leftAt: $data['left_at'] ?? null,
        );
    }
}
