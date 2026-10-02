<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\UpdateDriverData;
use App\Modules\MasterData\Models\Driver;
use App\Shared\Enums\OwnershipTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDriverRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }

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
        $driver = $this->route('driver');

        return [
            'user_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::exists('users', 'user_name'),
                Rule::unique('drivers', 'user_name')->ignore($driver instanceof Driver ? $driver : null),
            ],
            'partner_id' => ['sometimes', 'nullable', 'integer', Rule::exists('partners', 'id')],
            'type' => ['sometimes', Rule::enum(OwnershipTypeEnum::class)],
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'cccd' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('drivers', 'cccd')->ignore($driver instanceof Driver ? $driver : null),
            ],
            'license_number' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('drivers', 'license_number')->ignore($driver instanceof Driver ? $driver : null),
            ],
            'license_class' => ['sometimes', 'string', 'max:20'],
            'license_issued_at' => ['sometimes', 'nullable', 'date'],
            'license_expired_at' => ['sometimes', 'nullable', 'date'],
            'base_salary' => ['sometimes', 'decimal:0,2', 'min:0'],
            'responsibility_allowance' => ['sometimes', 'decimal:0,2', 'min:0'],
            'joined_at' => ['sometimes', 'nullable', 'date'],
            'left_at' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny([
                'type',
                'partner_id',
                'license_issued_at',
                'license_expired_at',
                'joined_at',
                'left_at',
            ])) {
                return;
            }

            $driver = $this->route('driver');
            $type = $this->input('type', $driver instanceof Driver ? $driver->type?->value : null);
            $partnerId = $this->has('partner_id') ? $this->input('partner_id') : ($driver instanceof Driver ? $driver->partner_id : null);

            if ($type === OwnershipTypeEnum::PARTNER->value && $partnerId === null) {
                $validator->errors()->add('partner_id', 'Trường đối tác là bắt buộc khi loại tài xế là đối tác.');
            }

            $licenseIssuedAt = $this->input('license_issued_at', $driver instanceof Driver ? $driver->license_issued_at?->toDateString() : null);
            $licenseExpiredAt = $this->input('license_expired_at', $driver instanceof Driver ? $driver->license_expired_at?->toDateString() : null);

            if ($licenseIssuedAt !== null && $licenseExpiredAt !== null && strtotime($licenseExpiredAt) < strtotime($licenseIssuedAt)) {
                $validator->errors()->add('license_expired_at', 'Ngày hết hạn bằng lái phải bằng hoặc sau ngày cấp bằng lái.');
            }

            $joinedAt = $this->input('joined_at', $driver instanceof Driver ? $driver->joined_at?->toDateString() : null);
            $leftAt = $this->input('left_at', $driver instanceof Driver ? $driver->left_at?->toDateString() : null);

            if ($joinedAt !== null && $leftAt !== null && strtotime($leftAt) < strtotime($joinedAt)) {
                $validator->errors()->add('left_at', 'Ngày nghỉ việc phải bằng hoặc sau ngày vào làm.');
            }
        });
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
            'is_active' => 'trạng thái hoạt động',
        ];
    }

    public function toDTO(): UpdateDriverData
    {
        $data = $this->validated();

        return new UpdateDriverData(
            userName: $data['user_name'] ?? null,
            partnerId: $data['partner_id'] ?? null,
            type: array_key_exists('type', $data) ? OwnershipTypeEnum::from($data['type']) : null,
            fullName: $data['full_name'] ?? null,
            phone: $data['phone'] ?? null,
            cccd: $data['cccd'] ?? null,
            licenseNumber: $data['license_number'] ?? null,
            licenseClass: $data['license_class'] ?? null,
            licenseIssuedAt: $data['license_issued_at'] ?? null,
            licenseExpiredAt: $data['license_expired_at'] ?? null,
            baseSalary: array_key_exists('base_salary', $data) ? (string) $data['base_salary'] : null,
            responsibilityAllowance: array_key_exists('responsibility_allowance', $data) ? (string) $data['responsibility_allowance'] : null,
            joinedAt: $data['joined_at'] ?? null,
            leftAt: $data['left_at'] ?? null,
            isActive: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            provided: array_keys($data),
        );
    }
}
